<?php
/**
 * Sidekick plugin for Craft CMS
 *
 * Your AI companion for rapid Craft CMS development.
 *
 * @author    Double Secret Agency
 * @link      https://plugins.doublesecretagency.com/
 * @copyright Copyright (c) 2025 Double Secret Agency
 */

namespace doublesecretagency\sidekick\services;

use Craft;
use craft\base\Element;
use craft\helpers\App;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use doublesecretagency\sidekick\constants\AiModel;
use doublesecretagency\sidekick\constants\Chat;
use doublesecretagency\sidekick\helpers\SystemPrompt;
use doublesecretagency\sidekick\models\ChatMessage;
use doublesecretagency\sidekick\models\FunctionCall;
use doublesecretagency\sidekick\models\SkillResponse;
use doublesecretagency\sidekick\models\ToolFunction;
use doublesecretagency\sidekick\Sidekick;
use GuzzleHttp\Client as GuzzleClient;
use OpenAI;
use OpenAI\Client;
use OpenAI\Responses\Responses\CreateStreamedResponse;
use OpenAI\Responses\Responses\Output\OutputFunctionToolCall;
use OpenAI\Responses\Responses\Output\OutputMessage;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionException;
use Throwable;
use yii\base\Component;
use yii\base\Exception;

/**
 * Class OpenAIService
 *
 * Handles communication with the AI assistant.
 */
class OpenAIService extends Component
{
    /**
     * @const Maximum length of the name for tool functions.
     */
    private const MAX_NAME_LENGTH = 64;

    /**
     * @const Length of the hash for tool functions.
     */
    private const HASH_LENGTH = 6;

    /**
     * @var string The API key for OpenAI.
     */
    private string $_apiKey;

    /**
     * @var Client The OpenAI client for making API requests.
     */
    private Client $_openAiClient;

    /**
     * @var array List of skills hashes.
     */
    public array $skillSetsHash = [];

    /**
     * @var int The last time a message was sent.
     */
    private int $_lastMessageTime = 0;

    /**
     * @var int Index of the current thinking message.
     */
    private int $_thinkingIndex = 0;

    /**
     * Initializes the service.
     *
     * @throws Exception
     */
    public function init(): void
    {
        parent::init();

        // Retrieve the OpenAI API key from plugin settings or environment variables
        $this->_apiKey = App::parseEnv(Sidekick::getInstance()?->getSettings()->openAiApiKey ?? '');

        // Set the AI client
        $this->_setAiClient();

        // Compile the available skills
        $this->_compileSkills();
    }

    // ========================================================================= //

    /**
     * Set the AI client.
     *
     * @throws Exception
     */
    private function _setAiClient(): void
    {
        // If the OpenAI client is already set, bail
        if (isset($this->_openAiClient)) {
            return;
        }

        // Get link to the plugin settings page
        $settingsUrl = UrlHelper::cpUrl('settings/plugins/sidekick');

        // If API key is not set, throw an exception
        if (!$this->_apiKey) {
            $error = "OpenAI API key is not set. Please [set the API key]({$settingsUrl}) to continue.";
            Craft::error($error, __METHOD__);
            throw new Exception($error);
        }

        // Create a new OpenAI client
        $this->_openAiClient = OpenAI::factory()
            ->withApiKey($this->_apiKey)
            ->withHttpClient(new GuzzleClient([
                'timeout' => 0
            ]))
            ->make();
    }

    /**
     * Compile the available skills.
     */
    private function _compileSkills(): void
    {
        // If skill sets hash has already been generated, bail
        if ($this->skillSetsHash) {
            return;
        }

        // Loop through each tool class
        foreach (Sidekick::getInstance()?->getSkills() as $skillSet) {

            // Split the tool class into parts
            $nameParts = explode('\\', $skillSet);

            // Remove the last part of the class name
            array_pop($nameParts);

            // Recombine the namespace
            $namespace = implode('\\', $nameParts);

            // Generate a truncated hash of the namespace
            $hash = $this->_generateHash($namespace);

            // Store the hash and namespace
            $this->skillSetsHash[$hash] = $namespace;

        }
    }

    // ========================================================================= //

    /**
     * Parse the conversation for the OpenAI thread.
     *
     * @return array
     */
    private function _parseConversation(): array
    {
        // Get the existing conversation
        $conversation = Sidekick::getInstance()?->chat->getConversation();

        // Initialize parsed conversation
        $parsedConversation = [];

        // Loop through each message in the conversation
        foreach ($conversation as $i => $message) {

            // If message is a function call
            if ($message instanceof FunctionCall) {

                // Parse the function call
                $parsedConversation[] = [
                    'type'      => 'function_call',
                    'call_id'   => $message->callId,
                    'name'      => $message->name,
                    'arguments' => $message->arguments,
                ];

                // Parse the function call output
                $parsedConversation[] = [
                    'type'    => 'function_call_output',
                    'call_id' => $message->callId,
                    'output'  => $message->output,
                ];

            } else if ($message instanceof ChatMessage) {

                // If message is an error
                if (ChatMessage::ERROR === $message->role) {
                    // Consider error to be a user message
                    $parsedConversation[] = [
                        'role' => ChatMessage::USER,
                        'content' => "SYSTEM ERROR: {$message->message}",
                    ];
                } else {
                    // Else convert object to array
                    $parsedConversation[] = [
                        'role' => $message->role,
                        'content' => $message->message,
                    ];
                }

            }

        }

        // Return the parsed conversation
        return $parsedConversation;
    }

    /**
     * Send a message to the AI assistant and handle the response.
     */
    public function streamResponses(): void
    {
        // If API key is not set
        if (!$this->_apiKey) {
            // Output error message
            (new ChatMessage([
                'role' => ChatMessage::ERROR,
                'message' => "OpenAI API key is not set."
            ]))
                ->log(__METHOD__)
                ->toChatHistory()
                ->toChatWindow();
            // Bail
            return;
        }

        // Don't re-run by default
        // unless a tool call is made
        $rerun = false;

        // Initialize detection of message pauses
        $lastThinkingMessageTime = time();
        $this->_lastMessageTime = time();
        $this->_thinkingIndex = 0;
        $thinkingInterval = 7; // seconds

        // Initialize heartbeat
        $heartbeatCounter = 0;

        // Number of cycles between heartbeats
        $heartbeatCycles = 5;

        // Get the SSE service
        $sse = Sidekick::getInstance()->sse;

        // Attempt to run the stream
        try {

            // Configure the streaming response
            $streamConfig = [
                'stream' => true, // Stream responses for SSE
                'model' => AiModel::DEFAULT, // TEMP: Lock to default model
                'instructions' => SystemPrompt::getPrompt(),
                'tools' => $this->_getTools(),
                'input' => $this->_parseConversation(),
            ];

            // Create a new streaming response
            $stream = $this->_openAiClient->responses()->createStreamed($streamConfig);

            // Loop over streaming responses
            /** @var CreateStreamedResponse $response */
            foreach ($stream as $response) {

                // If the SSE connection has been aborted
                if (connection_aborted()) {

                    // Log error message
                    (new ChatMessage([
                        'role' => ChatMessage::ERROR,
                        'message' => "SSE connection aborted by the client."
                    ]))
                        ->log(__METHOD__)
                        ->toChatHistory();

                    // Exits the foreach loop
                    break;
                }

                // Send a heartbeat to keep the SSE connection alive
                if (++$heartbeatCounter % $heartbeatCycles === 0) {
                    $sse->sendHeartbeat();
                }

                // Get the current time
                $currentTime = time();

                // How long has it been since the last message?
                $sinceLastMessage  = $currentTime - $this->_lastMessageTime;
                $sinceLastThinking = $currentTime - $lastThinkingMessageTime;

                // If things have been quiet for too long
                if (
                    $sinceLastMessage >= $thinkingInterval &&
                    $sinceLastThinking >= $thinkingInterval
                ) {

                    // Reset the last thinking message time
                    $lastThinkingMessageTime = $currentTime;

                    // Get index of the last thinking message
                    $lastIndex = count(Chat::THINKING_MESSAGES) - 1;

                    // Get the next thinking message, sticking with the last one if we go too far
                    $message = Chat::THINKING_MESSAGES[min($this->_thinkingIndex, $lastIndex)];

                    // If not at the last message
                    if ($this->_thinkingIndex < $lastIndex) {
                        // Increment to the next message
                        $this->_thinkingIndex++;
                    }

                    // Send a "thinking" message to the chat window
                    (new ChatMessage([
                        'role' => ChatMessage::SYSTEM,
                        'message' => $message
                    ]))
                        ->log(__METHOD__)
                        ->toChatHistory()
                        ->toChatWindow();
                }

                // Switch based on the event type
                switch ($response->event) {

                    // Success (text or function call)
                    case 'response.output_item.done':
                        $rerun = $this->_handleItemDone($response);
                        break;

                    // Failed (or incomplete)
                    case 'response.incomplete':
                    case 'response.failed':
//                    case 'error':
                        $this->_handleFailure($response);
                        // Break the whole loop
                        break 2;

                    // Ignore everything else
                    default:
                        break;
                }

            }

        } catch (Throwable $e) {

            // Output error message
            (new ChatMessage([
                'role' => ChatMessage::ERROR,
                'message' => $e->getMessage(),
            ]))
                ->log(__METHOD__)
                ->toChatHistory()
                ->toChatWindow();

        }

        try {

            // Save all project config changes
            Craft::$app->getProjectConfig()->saveModifiedConfigData();

        } catch (Throwable $e) {

            // Output error message
            (new ChatMessage([
                'role' => ChatMessage::ERROR,
                'message' => "Problem updating the project config. You may need to rebuild the project config manually."
            ]))
                ->log(__METHOD__)
                ->toChatHistory()
                ->toChatWindow();

        }

        // If needed, start the next stream from where we left off
        if ($rerun) {
            // Start the next stream from where we left off
            $this->streamResponses();
        }
    }

    // ========================================================================= //

    /**
     * Handle a fully received item (either plain text or a function call).
     *
     * @param CreateStreamedResponse $response
     * @return bool
     */
    private function _handleItemDone(CreateStreamedResponse $response): bool
    {
        // Get the item from the response
        $item = ($response->response->item ?? null);

        // If no item, bail
        if (!$item) {
            return false;
        }

        // If the item is a plain text message
        if ($item->type === 'message') {
            // Handle a text response
            $this->_handleTextResponse($item);
            // Don't re-run the stream
            return false;
        }

        // Handle a tool call item
        $this->_handleToolCall($item);
        // Re-run the stream
        return true;
    }

    /**
     * Handle a text response from the AI.
     *
     * @param OutputMessage $item
     */
    private function _handleTextResponse(OutputMessage $item): void
    {
        // If the SSE connection has been aborted
        if (connection_aborted()) {
            // Log error message
            (new ChatMessage([
                'role' => ChatMessage::ERROR,
                'message' => "Unable to append reply, SSE connection aborted."
            ]))
                ->log(__METHOD__)
                ->toChatHistory();
            // Bail
            return;
        }

        // Get the AI response
        $aiResponse = ($item->content[0]->text ?? null);

        // Compile AI response message
        if ($aiResponse) {
            // Respond with the AI response
            $responseMessage = [
                'role' => ChatMessage::ASSISTANT,
                'message' => $aiResponse
            ];
        } else {
            // Respond with an error message
            $responseMessage = [
                'role' => ChatMessage::ERROR,
                'message' => "No AI response received."
            ];
        }

        // Append AI response to the chat history
        (new ChatMessage($responseMessage))
            ->log(__METHOD__)
            ->toChatHistory()
            ->toChatWindow();
    }

    /**
     * Handle a failure response from the AI.
     *
     * @param CreateStreamedResponse $response
     */
    private function _handleFailure(CreateStreamedResponse $response): void
    {
        // Get the error message
        $error = (
            $response->response->lastError->message ??
            "An unknown error occurred. [{$response->event}]"
        );

        // Output error message
        (new ChatMessage([
            'role' => ChatMessage::ERROR,
            'message' => "Run unsuccessful. {$error}"
        ]))
            ->log(__METHOD__)
            ->toChatHistory()
            ->toChatWindow();
    }

    /**
     * Handle a tool call response from the AI.
     *
     * @param OutputFunctionToolCall $item
     */
    private function _handleToolCall(OutputFunctionToolCall $item): void
    {
        // If not a tool call, bail
        if ('function_call' !== $item->type) {
            return;
        }

        try {

            // Run the tool function with provided arguments
            $skillResponse = (new ToolFunction($item->name))->run($item->arguments);

            // If the tool response was not successful, throw an exception
            if (!$skillResponse->success) {
                throw new Exception($skillResponse->message ?? 'An unknown error occurred.');
            }

            // Append the tool output to the chat history
            (new ChatMessage([
                'role' => ChatMessage::SYSTEM,
                'message' => ($skillResponse->message ?? '[missing tool message]')
            ]))
                ->log(__METHOD__)
                ->toChatHistory()
                ->toChatWindow();

            // If the tool response contains data
            if ($skillResponse->response) {

                // Append output to the chat history
                (new FunctionCall([
                    'callId'    => $item->callId,
                    'name'      => $item->name,
                    'arguments' => $item->arguments,
                    'output'    => $skillResponse->response
                ]))
                    ->log(__METHOD__)
                    ->toChatHistory();

            }

        } catch (Throwable $e) {

            // Get the error message and stack trace
            $message    = $e->getMessage();
            $stackTrace = $e->getTraceAsString();

            // Append the error to the chat history
            (new ChatMessage([
                'role' => ChatMessage::ERROR,
                'message' => $message
            ]))
                ->log(__METHOD__)
                ->toChatHistory()
                ->toChatWindow();

            // Append the error and stack trace as the function output
            (new FunctionCall([
                'callId'    => $item->callId,
                'name'      => $item->name,
                'arguments' => $item->arguments,
                'output'    => "{$message}\n\n{$stackTrace}"
            ]))
                ->log(__METHOD__)
                ->toChatHistory();

        }

        // Reset the thinking index and last message time
        $this->_thinkingIndex = 0;
        $this->_lastMessageTime = time();
    }

    // ========================================================================= //

    /**
     * Available functions for the API to call.
     *
     * @return array[]
     * @throws ReflectionException
     * @throws Exception
     */
    private function _getTools(): array
    {
        // Initialize available tools
        $tools = [
//            [
//                'type' => 'code_interpreter',
//            ],
//            [
//                'type' => 'file_search',
//                'file_search' => [
//                    'max_num_results' => 50,
//                ]
//            ]
        ];

        /**
         * TODO: Add MCP tool support here.
         */

        // Loop through each tool class
        foreach (Sidekick::getInstance()?->getSkills() as $skillSet) {

            // Get available tool functions
            $toolFunctions = (new $skillSet())->getToolFunctions();

            // Create a new instance of the DocBlockFactory
            $docFactory = DocBlockFactory::createInstance();

            // Loop through each tool function
            foreach ($toolFunctions as $toolFunction) {

                // Get the method's docblock
                $docBlock = $docFactory->create($toolFunction->getDocComment());

                // Get the method details
                $functionName = $toolFunction->getName();
                $summary = $docBlock->getSummary();
                $description = $docBlock->getDescription();

                // Merge summary into description
                $description = "{$summary}\n\n{$description}";

                // Get the method's parameters
                $params = $docBlock->getTagsByName('param');

                // Initialize the properties array
                $properties = [];

                // Loop through each parameter
                foreach ($params as $param) {
                    /** @var Param $param */

                    // Get the parameter details
                    $paramName = $param->getVariableName();
                    $paramType = (string) $param->getType();
                    $paramDesc = $param->getDescription()?->render();

                    // Add the parameter to the properties array
                    $properties[$paramName] = [
                        'type' => $paramType,
                        'description' => $paramDesc,
                    ];

                }

                // Split the tool class into parts
                $nameParts = explode('\\', $skillSet);

                // Get the last part of the class name
                $className = array_pop($nameParts);

                // Recombine the namespace
                $namespace = implode('\\', $nameParts);

                // Generate a truncated hash of the namespace
                $hash = $this->_generateHash($namespace);

                // Generate a unique full name for the tool
                $fullName = "{$hash}-{$className}-{$functionName}";

                // Calculate the maximum length for the tool name
                $maxLength = (self::MAX_NAME_LENGTH - self::HASH_LENGTH - 2); // Includes 2 dashes

                // If the name is too long, throw an exception
                if (self::MAX_NAME_LENGTH < strlen($fullName)) {
                    throw new Exception("The tool name (class + method) of `{$className}::{$functionName}` exceeds the maximum length of {$maxLength} total characters.");
                }

                // Add the function to the list of tools
                $tools[] = $this->_toolFunction($fullName, $description, $properties);
            }

        }

        // Return all available tools
        return $tools;
    }

    /**
     * Generate a hash from the given namespace.
     *
     * @param string $namespace
     * @return string
     */
    private function _generateHash(string $namespace): string
    {
        // Return a truncated hash of the namespace
        return substr(md5($namespace), 0, self::HASH_LENGTH);
    }

    /**
     * Compile a tool function.
     *
     * @param string $name
     * @param string $description
     * @param array $parameters
     * @return array
     */
    private function _toolFunction(string $name, string $description, array $parameters = []): array
    {
        // If no parameters, set properties to an empty object
        $properties = ($parameters ?: new \stdClass());

        // Return the tool function
        return [
            'type' => 'function',
            'strict' => true,
            'name' => $name,
            'description' => $description,
            'parameters' => [
                'type' => 'object',
                'properties' => $properties,
                'additionalProperties' => false, // For strict mode
                'required' => array_keys($parameters), // All parameters required
            ],
        ];
    }

    // ========================================================================= //

    /**
     * Get the entire conversation.
     *
     * @return array
     */
    public function getConversation(): array
    {
        return Sidekick::getInstance()?->chat->getConversation();
    }

    /**
     * Generate a greeting message.
     *
     * @return ChatMessage
     */
    public function getGreetingMessage(): ChatMessage
    {
        // Get all greeting options
        $options = Chat::GREETING_OPTIONS;

        // Select a random greeting
        $greetingText = $options[array_rand($options)];

        // Create and return a new assistant message
        return new ChatMessage([
            'role' => ChatMessage::ASSISTANT,
            'message' => $greetingText
        ]);
    }

    // ========================================================================= //

    /**
     * Summarize the element for the "AI Summary" field type.
     *
     * @param Element $element
     * @param string $instructions
     * @return string
     */
    public function summarizeElement(Element $element, string $instructions): string
    {
        // Compress the element data
        $elementData = Json::encode($element);

        /*
         * @TODO: Permit different column types.
         *        Copy how it's done in the Plain Text field.
         *        Max length would be based on selected column type.
         */

        // Compile the input for the AI
        $input = <<<INPUT
# Instructions
{$instructions}

## Maximum Response Length
**IMPORTANT:** Unless otherwise specified, the absolute maximum length of your response must be 240 characters or fewer. Longer text will cause an error when the field is saved.

# Craft CMS Element
{$elementData}
INPUT;

        // Perform the AI query
        $response = $this->_openAiClient->responses()->create([
//            'model' => 'gpt-4o-mini', // Less creative, faster, cheaper
            'model' => 'o4-mini', // More creative, slower, more expensive
            'input' => $input,
        ]);

        // Get only messages from the response output
        $responseOutputs = array_filter($response->output, static function ($item) {
            return $item->type === 'message';
        });

        // Get the first message
        $message = reset($responseOutputs);

        // Return the response output
        return ($message->content[0]->text ?? '');
    }
}

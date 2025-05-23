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
use doublesecretagency\sidekick\constants\Session;
use doublesecretagency\sidekick\helpers\SystemPrompt;
use doublesecretagency\sidekick\models\ChatMessage;
use doublesecretagency\sidekick\models\SkillResponse;
use doublesecretagency\sidekick\Sidekick;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use OpenAI;
use OpenAI\Client;
use OpenAI\Contracts\Resources\ThreadsRunsContract;
use OpenAI\Responses\StreamResponse;
use OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use OpenAI\Responses\Threads\Runs\ThreadRunResponseRequiredAction;
use OpenAI\Responses\Threads\Runs\ThreadRunResponseRequiredActionFunctionToolCall;
use OpenAI\Responses\Threads\Runs\ThreadRunStreamResponse;
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
     * @var string|null The assistant ID.
     */
    private ?string $_assistantId = null;

    /**
     * @var string|null The thread ID.
     */
    private ?string $_threadId = null;

    /**
     * @var array List of skills hashes.
     */
    public array $skillSetsHash = [];

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
        // Get link to the plugin settings page
        $settingsUrl = UrlHelper::cpUrl('settings/plugins/sidekick');

        // If API key is not set, throw an exception
        if (!$this->_apiKey) {
            $error = "OpenAI API key is not set. Please [set the API key]({$settingsUrl}) to continue.";
            Craft::error($error, __METHOD__);
            throw new Exception($error);
        }

        // If the OpenAI client is already set, bail
        if (isset($this->_openAiClient)) {
            return;
        }

        // Create a new OpenAI client
        $this->_openAiClient = OpenAI::factory()
            ->withApiKey($this->_apiKey)
            ->withHttpClient(new GuzzleClient([
                'timeout' => 0,
                'headers' => [
                    'OpenAI-Beta' => 'assistants=v2'
                ]
            ]))
            ->make();
    }

    // ========================================================================= //

    /**
     * Summarize the element.
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

        // Compile the content for the AI
        $content = <<<CONTENT
# Instructions
{$instructions}

## Maximum Response Length
**IMPORTANT:** Unless otherwise specified, the absolute maximum length of your response must be 240 characters or fewer. Longer text will cause an error when the field is saved.

# Craft CMS Element
{$elementData}
CONTENT;

        // Perform the AI query
        $result = $this->_openAiClient->chat()->create([
            'model' => 'o4-mini',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $content
                ],
            ],
        ]);

        // Return the AI response
        return ($result->choices[0]->message->content ?? '');
    }

    // ========================================================================= //

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
     * Get the assistant ID.
     *
     * @return string|null
     * @throws Exception
     */
    private function _getAssistantId(): ?string
    {
        // If assistant ID is already set, return it
        if ($this->_assistantId) {
            return $this->_assistantId;
        }

        try {
            // Get the session service
            $session = Craft::$app->getSession();

            // If assistant ID exists in the session, return it
            if ($assistantId = $session->get(Session::ASSISTANT_ID)) {
                return $assistantId;
            }

            // Get the selected AI model from the session
//            $model = Craft::$app->getSession()->get(Session::AI_MODEL, AiModel::DEFAULT);
            $model = AiModel::DEFAULT; // TEMP: Lock to default model

            // Create a new assistant
            $assistant = $this->_openAiClient->assistants()->create([
                'model' => $model,
                'name' => 'Sidekick',
                'instructions' => SystemPrompt::getPrompt(),
                'tools' => $this->_getTools()
            ]);

            // Store the assistant ID
            $this->_assistantId = $assistant->id;

            // Store the assistant ID in the session
            $session->set(Session::ASSISTANT_ID, $assistant->id);

            // Track the assistant
            Craft::info("Created a new assistant: {$assistant->id}", __METHOD__);

            // Return the assistant ID
            return $assistant->id;

        } catch (\Exception $e) {

            // Log and throw the error
            $error = "Unable to create a new assistant. {$e->getMessage()}";
            Craft::error($error, __METHOD__);
            throw new Exception($error);

        }
    }

    /**
     * Get the thread ID.
     *
     * @return string|null
     * @throws Exception
     */
    private function _getThreadId(): ?string
    {
        // If thread ID is already set, return it
        if ($this->_threadId) {
            return $this->_threadId;
        }

        try {
            // Get the session service
            $session = Craft::$app->getSession();

            // If thread ID exists in the session, return it
            if ($threadId = $session->get(Session::THREAD_ID)) {
                return $threadId;
            }

            // Create a new thread
            $thread = $this->_openAiClient->threads()->create([]);

            // Store the thread ID
            $this->_threadId = $thread->id;

            // Store the thread ID in the session
            $session->set(Session::THREAD_ID, $thread->id);

            // Track the assistant
            Craft::info("Created a new thread: {$thread->id}", __METHOD__);

            // Return the thread ID
            return $thread->id;

        } catch (\Exception $e) {

            // Log and throw the error
            $error = "Unable to create a new thread. {$e->getMessage()}";
            Craft::error($error, __METHOD__);
            throw new Exception($error);

        }
    }

    // ========================================================================= //

    /**
     * Initialize the thread.
     *
     * @return void
     * @throws Exception
     */
    private function _initThread(): void
    {
        // Get the assistant and thread IDs
        $this->_assistantId = $this->_getAssistantId();
        $this->_threadId    = $this->_getThreadId();
    }

    // ========================================================================= //

    /**
     * Append a message to the current thread.
     *
     * @param array $message
     * @return void
     * @throws Exception
     */
    public function addMessage(array $message): void
    {
        // If API key is not set, throw an exception
        if (!$this->_apiKey) {
            $error = "OpenAI API key is not set.";
            Craft::error($error, __METHOD__);
            throw new Exception($error);
        }

        // Track the message
        Craft::info("Appending message to the OpenAI conversation.", __METHOD__);

        try {

            // Initialize the thread
            $this->_initThread();

            // Append message to the existing thread
            $this->_openAiClient->threads()->messages()->create($this->_threadId, $message);

        } catch (RequestException|\Exception $e) {

            // Log and throw the error
            $error = $e->getMessage();

            // If trying to add a new message to an active thread
            if (str_contains($error, "Can't add messages to thread")) {
                $error = 'We encountered some turbulence. You may need to clear the conversation and start over.';
            }

            Craft::error($error, __METHOD__);
            throw new Exception($error);

        }
    }

    /**
     * Run the thread.
     *
     * @throws Exception
     */
    public function runThread(): void
    {
        /** @var ThreadRunResponse $run */

        // Get the runs service
        $service = $this->_openAiClient->threads()->runs();

        // Create a new streaming run
        $stream = $service->createStreamed($this->_getThreadId(), [
            'assistant_id' => $this->_getAssistantId(),
        ]);

        // The thread is running
        $running = true;

        // Attempt to run the thread
        try {

            // While the run is not completed
            do {

                // Loop through the stream
                foreach ($stream as $response) {

                    /** @var ThreadRunStreamResponse $response */

                    // If not a delta event
                    if ('thread.message.delta' !== $response->event) {
                        // Log the response event
                        (new ChatMessage([
                            'role' => ChatMessage::TOOL,
                            'message' => "[{$response->event}]",
                        ]))
                            ->log();
                    }

                    // Switch based on the event type
                    switch ($response->event) {
                        case 'thread.run.created':
                        case 'thread.run.queued':
                        case 'thread.run.completed':
                            // Set run and continue looping
                            $run = $response->response;
                            break;
                        case 'thread.run.cancelling':
                            // Set run
                            $run = $response->response;
                            // Output error message
                            (new ChatMessage([
                                'role' => ChatMessage::ERROR,
                                'message' => 'Run is being cancelled for some reason.'
                            ]))
                                ->log()
                                ->toChatHistory()
                                ->toChatWindow();
                            // Continue
                            break;
                        case 'thread.run.expired':
                        case 'thread.run.cancelled':
                        case 'thread.run.failed':
                            // The thread is no longer running
                            $running = false;
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
                                ->log()
                                ->toChatHistory()
                                ->toChatWindow();
                            // Break the whole loop
                            break 3;
                        case 'thread.run.requires_action':
                            // Handle the required action
                            $stream = $this->_handleRequiredAction($run, $response, $service);
                            // Break the loop
                            break;
                    }
                }

            // Until the run is completed
            } while ($run->status !== 'completed');

            // The thread is no longer running
            $running = false;

            // Get the latest assistant message
            $reply = $this->_getLatestAssistantMessage();

            // Append reply to the chat history
            (new ChatMessage($reply))
                ->log()
                ->toChatHistory()
                ->toChatWindow();

        } catch (\Exception $e) {

            // Get the error message
            $message = $e->getMessage();

            // If message contains "Unable to read from stream"
            if (str_contains($message, 'Unable to read from stream')) {
                $message = 'Sorry, something has timed out. You may need to clear the conversation and start over.';
            }

            // Compile error message
            $error = new ChatMessage([
                'role' => ChatMessage::ERROR,
                'message' => $message,
            ]);

            // Log error and append to chat
            $error
                ->log()
                ->toChatHistory()
                ->toChatWindow();

            // If the thread is not running
            if (!$running) {
                // Append the error to the OpenAI thread
                $error->toOpenAiThread();
            }
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
                ->log()
                ->toChatHistory()
                ->toChatWindow();

        }
    }

    // ========================================================================= //

    /**
     * Handle the required action for the thread run.
     *
     * @param ThreadRunResponse $run
     * @param ThreadRunStreamResponse $response
     * @param ThreadsRunsContract $service
     * @return StreamResponse
     * @throws Exception
     */
    private function _handleRequiredAction(ThreadRunResponse $run, ThreadRunStreamResponse $response, ThreadsRunsContract $service): StreamResponse
    {
        /** @var ThreadRunResponseRequiredAction $requiredAction */
        $requiredAction = $response->response->requiredAction;

        // If the required action is not a tool output submission
        if ('submit_tool_outputs' !== $requiredAction->type) {
            // Cancel the run and throw an exception
            $service->cancel($run->threadId, $run->id);
            throw new Exception("Unknown required action type: {$requiredAction->type}");
        }

        // Initialize an array to store all tool outputs
        $allToolOutputs = [];

        // Loop through each tool call
        foreach ($requiredAction->submitToolOutputs->toolCalls as $toolCall) {

            try {

                // If the tool call is not a function, throw an exception
                if ('function' !== $toolCall->type) {
                    throw new Exception("Unknown tool type: {$toolCall->type}");
                }

                // Run the tool
                $skillResponse = $this->_runTool($toolCall);

                // If the tool response was not successful, throw an exception
                if (!$skillResponse->success) {
                    throw new Exception($skillResponse->message ?? 'An unknown error occurred.');
                }

                // Append the tool output to the chat history
                (new ChatMessage([
                    'role' => ChatMessage::TOOL,
                    'message' => ($skillResponse->message ?? '[missing tool message]')
                ]))
                    ->log()
                    ->toChatHistory()
                    ->toChatWindow();

                // Set the tool output
                $toolOutput = ($skillResponse->response ?? $skillResponse->message);

            } catch (\Exception $e) {

                // Append the error to the chat history
                (new ChatMessage([
                    'role' => ChatMessage::ERROR,
                    'message' => ($e->getMessage())
                ]))
                    ->log()
                    ->toChatHistory()
                    ->toChatWindow();

                // Get the error message and stack trace
                $message = $e->getMessage();
                $stackTrace = $e->getTraceAsString();

                // Set the tool output
                $toolOutput = "{$message}\n\n{$stackTrace}";

            }

            // Add the tool output to the array
            $allToolOutputs[] = [
                'tool_call_id' => $toolCall->id,
                'output' => $toolOutput
            ];
        }

        // Submit the tool outputs back to the OpenAI thread
        return $service->submitToolOutputsStreamed($run->threadId, $run->id, [
            'tool_outputs' => $allToolOutputs,
        ]);
    }

    /**
     * Run the specified tool call.
     *
     * @param ThreadRunResponseRequiredActionFunctionToolCall $toolCall
     * @return SkillResponse
     * @throws Exception
     */
    private function _runTool(ThreadRunResponseRequiredActionFunctionToolCall $toolCall): SkillResponse
    {
        try {
            // Get the function name and arguments
            $fullName = $toolCall->function->name;
            $args = json_decode($toolCall->function->arguments, true);

            // Split the full name into parts
            $nameParts = explode('-', $fullName);

            // Convert hash to namespace
            $nameParts[0] = ($this->skillSetsHash[$nameParts[0]] ?? $nameParts[0]);

            // Get the method and class names
            $method = array_pop($nameParts);
            $class = implode('\\', $nameParts);

            // If the tool function does not exist, throw an exception
            if (!method_exists($class, $method)) {
                throw new Exception("Tool method does not exist: {$class}::{$method}");
            }

            // Call the tool function
            return $class::$method(...$args);

        } catch (\Exception $e) {

            // Return error message
            return new SkillResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);

        }
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
            [
                'type' => 'code_interpreter',
            ],
            [
                'type' => 'file_search',
                'file_search' => [
                    'max_num_results' => 50,
                ]
            ]
        ];

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
            'function' => [
                'name' => $name,
                'description' => $description,
                'strict' => true,
                'parameters' => [
                    'type' => 'object',
                    'properties' => $properties,
                    'additionalProperties' => false, // For strict mode
                    'required' => array_keys($parameters), // All parameters required
                ],
            ]
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

    /**
     * Get the latest assistant message.
     *
     * @return array
     */
    private function _getLatestAssistantMessage(): array
    {
        try {

            // Get the last message from the thread
            $messages = $this->_openAiClient->threads()->messages()->list($this->_threadId, ['limit' => 1]);

            // Get the last message from the thread
            $lastMessage = $messages->data[0];

            // If the last message was not from the assistant
            if (ChatMessage::ASSISTANT !== $lastMessage->role) {
                // Return an error message
                return [
                    'role' => ChatMessage::ERROR,
                    'message' => 'Unable to load last assistant message.'
                ];
            }

            // Get reply from the assistant
            $reply = $lastMessage->content[0]->text->value;

            // Return the assistant's reply
            return [
                'role' => ChatMessage::ASSISTANT,
                'message' => $reply,
            ];

        } catch (RequestException|\Exception $e) {

            // Return the error message
            return [
                'role' => ChatMessage::ERROR,
                'message' => $e->getMessage(),
            ];

        }
    }
}

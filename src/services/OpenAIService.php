<?php

namespace doublesecretagency\sidekick\services;

use Craft;
use craft\helpers\App;
use doublesecretagency\sidekick\constants\AiModel;
use doublesecretagency\sidekick\constants\Chat;
use doublesecretagency\sidekick\constants\Session;
use doublesecretagency\sidekick\events\DefineExtraToolsEvent;
use doublesecretagency\sidekick\helpers\Templates;
use doublesecretagency\sidekick\helpers\SystemPrompt;
use doublesecretagency\sidekick\models\ChatMessage;
use doublesecretagency\sidekick\Sidekick;
use GuzzleHttp\Exception\RequestException;
use OpenAI;
use OpenAI\Client;
use OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use OpenAI\Responses\Threads\Runs\ThreadRunResponseRequiredAction;
use OpenAI\Responses\Threads\Runs\ThreadRunResponseRequiredActionFunctionToolCall;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
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
     * @event DefineExtraToolsEvent The event that is triggered when defining extra tools for the AI assistant.
     */
    public const EVENT_DEFINE_EXTRA_TOOLS = 'defineExtraTools';

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
     * Initializes the service.
     *
     * @throws Exception
     */
    public function init(): void
    {
        parent::init();

        // Retrieve the OpenAI API key from plugin settings or environment variables
        $this->_apiKey = App::parseEnv(Sidekick::$plugin->getSettings()->openAiApiKey ?? '');

        // If API key is not set, throw an exception
        if (!$this->_apiKey) {
            $error = "OpenAI API key is not set.";
            Craft::error($error, __METHOD__);
            throw new Exception($error);
        }

        // If the OpenAI client is not already set
        if (!isset($this->_openAiClient)) {
            // Create a new OpenAI client
            $this->_openAiClient = OpenAI::client($this->_apiKey);
        }
    }

    // ========================================================================= //

    /**
     * Retrieves the current Craft CMS version.
     *
     * @return string
     */
    public function getCurrentCraftVersion(): string
    {
        return Craft::$app->version;
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
            $model = Craft::$app->getSession()->get(Session::AI_MODEL, AiModel::DEFAULT);

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
            Craft::error($error, __METHOD__);
            throw new Exception($error);

        }
    }

    /**
     * Run the thread.
     *
     * @return array
     * @throws Exception
     */
    public function runThread(): array
    {
        /** @var ThreadRunResponse $run */

        // Get the runs service
        $service = $this->_openAiClient->threads()->runs();

        // Create a new streaming run
        $stream = $service->createStreamed($this->_getThreadId(), [
            'assistant_id' => $this->_getAssistantId(),
        ]);

        // Initialize any tool messages
        $toolMessages = [];

        // Attempt to run the thread
        try {

            // While the run is not completed
            do {

                // Loop through the stream
                foreach ($stream as $response) {

                    // Switch based on the event type
                    switch ($response->event) {
                        case 'thread.run.created':
                        case 'thread.run.queued':
                        case 'thread.run.completed':
                        case 'thread.run.cancelling':
                            // Set run and continue looping
                            $run = $response->response;
                            break;
                        case 'thread.run.expired':
                        case 'thread.run.cancelled':
                        case 'thread.run.failed':
                            // Break the whole loop
                            break 3;
                        case 'thread.run.requires_action':

                            /** @var ThreadRunResponseRequiredAction $requiredAction */
                            $requiredAction = $response->response->requiredAction;

                            // If the required action is not to submit tool outputs
                            if ('submit_tool_outputs' !== $requiredAction->type) {
                                // Cancel the run and throw an exception
                                $service->cancel($run->threadId, $run->id);
                                throw new Exception("Unknown required action type: {$requiredAction->type}");
                            }

                            // Initialize an array to collect all tool outputs
                            $allToolOutputs = [];

                            // Loop through the tool calls
                            foreach ($requiredAction->submitToolOutputs->toolCalls as $toolCall) {

                                // If the tool type is not a function
                                if ('function' !== $toolCall->type) {
                                    // Cancel the run and throw an exception
                                    $service->cancel($run->threadId, $run->id);
                                    throw new Exception("Unknown tool type: {$toolCall->type}");
                                }

                                // Run the tool
                                $results = $this->_runTool($toolCall);

                                // If the tool results were not successful
                                if (!$results['success']) {
                                    // Cancel the run and throw an exception
                                    $service->cancel($run->threadId, $run->id);
                                    throw new Exception($results['error'] ?? 'An unknown error occurred.');
                                }

                                // Append the message
                                $toolMessages[] = $results['message'];

                                // Collect the tool output
                                $allToolOutputs[] = [
                                    'tool_call_id' => $toolCall->id,
                                    'output' => $results['output'],
                                ];
                            }

                            // Submit all tool outputs at once
                            $stream = $service->submitToolOutputsStreamed($run->threadId, $run->id, [
                                'tool_outputs' => $allToolOutputs,
                            ]);

                            // Break the loop
                            break;
                    }
                }

            // Until the run is completed
            } while ($run->status !== 'completed');

        } catch (\Exception $e) {

            // Compile error message
            $message = [
                'role' => ChatMessage::ERROR,
                'content' => $e->getMessage(),
            ];

            // Send back to the OpenAI thread
            (new ChatMessage($message))
                ->addToOpenAiThread();

            // Append error message
            $toolMessages[] = $message;

        }


        /**
         * If the $toolMessages array contains ANY ERROR messages,
         * we will need to re-run the thread (perhaps recursively?)
         * to get the follow-up response.
         *
         * We may need to return all messages together (from both runs).
         *
         * We may need to include a counter to prevent infinite loops.
         */








        // Return all tool messages
        return $toolMessages;
    }

    // ========================================================================= //

    /**
     * Run the specified tool call.
     *
     * @param ThreadRunResponseRequiredActionFunctionToolCall $toolCall
     * @return array
     * @throws Exception
     */
    private function _runTool(ThreadRunResponseRequiredActionFunctionToolCall $toolCall): array
    {
        try {
            // Get the function name and arguments
            $fullName = $toolCall->function->name;
            $args = json_decode($toolCall->function->arguments, true);

            // Split the full name into parts
            $nameParts = explode('-', $fullName);

            // Get the method and class names
            $method = array_pop($nameParts);
            $class = implode('\\', $nameParts);

            // If the tool function does not exist, throw an exception
            if (!method_exists($class, $method)) {
                throw new Exception("Tool method does not exist: {$class}::{$method}");
            }

            // Call the tool function
            $results = $class::$method(...$args);

            // Whether the results were successful
            $success = ($results['success'] ?? false);

            // Set output to the success or error message
            $output = ($results['message'] ?? "An unknown error occurred.");

            // Compile the tool message
            $message = [
                'role' => ($success ? ChatMessage::TOOL : ChatMessage::ERROR),
                'content' => $output,
            ];

        } catch (\Exception $e) {

            // The results were not successful
            $success = false;

            // Set output to the exception message
            $output = $e->getMessage();

            // Compile the error message
            $message = [
                'role' => ChatMessage::ERROR,
                'content' => $output,
            ];

        }

        // Return the message and output
        return [
            'success' => $success,
            'message' => $message,
            'output' => $output,
        ];
    }

    // ========================================================================= //

    /**
     * Available functions for the API to call.
     *
     * @return array[]
     * @throws ReflectionException
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

        // Initialize the tool set with native tools
        $toolSet = [Templates::class];

        // Give plugins/modules a chance to add custom tools
        if ($this->hasEventHandlers(self::EVENT_DEFINE_EXTRA_TOOLS)) {
            // Create a new DefineExtraToolsEvent
            $event = new DefineExtraToolsEvent();
            // Trigger the event
            $this->trigger(self::EVENT_DEFINE_EXTRA_TOOLS, $event);
            // Append any extra tools to the tool set
            $toolSet = array_merge($toolSet, $event->extraTools);
        }

        // Loop through each tool class
        foreach ($toolSet as $toolClass) {

            // Get all class methods
            $toolFunctions = (new ReflectionClass($toolClass))->getMethods(ReflectionMethod::IS_PUBLIC);

            // Create a new instance of the DocBlockFactory
            $docFactory = DocBlockFactory::createInstance();

            // Loop through each tool function
            foreach ($toolFunctions as $toolFunction) {

                // Get the method's docblock
                $docBlock = $docFactory->create($toolFunction->getDocComment());

                // Get the method details
                $name = $toolFunction->getName();
                $description = $docBlock->getSummary();

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

                // Generate a unique full name for the tool
                $fullName = str_replace('\\', '-', $toolClass)."-{$name}";

                // Add the function to the list of tools
                $tools[] = $this->_toolFunction($fullName, $description, $properties);
            }

        }

        // Return all available tools
        return $tools;
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
        return Sidekick::$plugin->chat->getConversation();
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
            'content' => $greetingText
        ]);
    }

    /**
     * Get the latest assistant message.
     *
     * @return array
     */
    public function getLatestAssistantMessage(): array
    {
        try {

            // Get the last message from the thread
            $messages = $this->_openAiClient->threads()->messages()->list($this->_threadId, ['limit' => 1]);

            // Get the last message from the thread
            $lastMessage = $messages->data[0];

            // If the last message was not from the assistant
            if (ChatMessage::ASSISTANT !== $lastMessage->role) {
                // Return an empty array
                return [];
            }

            // Get reply from the assistant
            $reply = $lastMessage->content[0]->text->value;

            // Return the assistant's reply
            return [
                'role' => ChatMessage::ASSISTANT,
                'content' => $reply,
            ];

        } catch (RequestException|\Exception $e) {

            // Return the error message
            return [
                'role' => ChatMessage::ERROR,
                'content' => $e->getMessage(),
            ];

        }
    }
}

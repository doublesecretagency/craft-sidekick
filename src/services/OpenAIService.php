<?php

namespace doublesecretagency\sidekick\services;

use Craft;
use craft\helpers\App;
use doublesecretagency\sidekick\constants\AiModel;
use doublesecretagency\sidekick\constants\Chat;
use doublesecretagency\sidekick\constants\Session;
use doublesecretagency\sidekick\helpers\ApiTools;
use doublesecretagency\sidekick\helpers\SystemPrompt;
use doublesecretagency\sidekick\models\ChatMessage;
use doublesecretagency\sidekick\Sidekick;
use GuzzleHttp\Exception\RequestException;
use OpenAI;
use OpenAI\Client;
use OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use OpenAI\Responses\Threads\Runs\ThreadRunResponseRequiredAction;
use OpenAI\Responses\Threads\Runs\ThreadRunResponseRequiredActionFunctionToolCall;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
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
        // Get the runs service
        $service = $this->_openAiClient->threads()->runs();

        // Create a new streaming run
        $stream = $service->createStreamed($this->_getThreadId(), [
            'assistant_id' => $this->_getAssistantId(),
        ]);

        // Initialize any tool messages
        $toolMessages = [];

        /** @var ThreadRunResponse $run */

        // While the run is not completed
        do {
            // Loop through the stream
            foreach ($stream as $response) {

//$response->event; // 'thread.run.created' | 'thread.run.in_progress' | .....
//$response->response; // ThreadResponse | ThreadRunResponse | ThreadRunStepResponse | ThreadRunStepDeltaResponse | ThreadMessageResponse | ThreadMessageDeltaResponse

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
                        // Set run and break the loop
                        $run = $response->response;
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
                                throw new Exception($results['output']);
                            }

                            // Append the message
                            $toolMessages[] = $results['message'];

                            // Overwrite the stream with the new stream started by submitting the tool outputs
                            $stream = $service->submitToolOutputsStreamed($run->threadId, $run->id, [
                                'tool_outputs' => [
                                    [
                                        'tool_call_id' => $toolCall->id,
                                        'output' => $results['output'],
                                    ]
                                ],
                            ]);

                        }

                        break;
                }
            }

        // Until the run is completed
        } while ($run->status !== 'completed');

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
            $name = $toolCall->function->name; // 'createFile' | 'readFile' | 'updateFile' | 'deleteFile'
            $args = json_decode($toolCall->function->arguments, true);

            // If the tool function does not exist, throw an exception
            if (!method_exists(ApiTools::class, $name)) {
                throw new Exception("Tool method does not exist: {$name}");
            }

            // Call the tool function
            $results = ApiTools::$name($args);

            // Whether the results were successful
            $success = ($results['success'] ?? false);

            // Set output to the success or error message
            $output = ($results['message'] ?? "An unknown error occurred.");

            // Compile the tool message
            $message = [
                'role' => ($success ? 'tool' : 'error'),
                'content' => $output,
            ];

        } catch (\Exception $e) {

            // The results were not successful
            $success = false;

            // Set output to the exception message
            $output = $e->getMessage();

            // Compile the error message
            $message = [
                'role' => 'error',
                'content' => $output,
            ];

        }

        // If not an error message
        if ('error' !== $message['role']) {
            /**
             * Error messages get logged and
             * added to the chat history
             * later, when the error is thrown.
             */
            // Append the message to the chat history
            (new ChatMessage($message))
                ->log()
                ->addToChatHistory();
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

        // Get all methods from the ApiTools class
        $toolFunctions = (new ReflectionClass(ApiTools::class))->getMethods();

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

                // Get the parameter details
                $paramName = $param->getVariableName();
                $paramType = (string) $param->getType();
                $paramDesc = $param->getDescription()->render();

                // Add the parameter to the properties array
                $properties[$paramName] = [
                    'type' => $paramType,
                    'description' => $paramDesc,
                ];

            }

            // Add the function to the list of tools
            $tools[] = $this->_toolFunction($name, $description, $properties);
        }

        // Return all available tools
        return $tools;
    }

    /**
     * Compile a tool function.
     *
     * @param string $name
     * @param string $description
     * @param array $properties
     * @return array
     */
    private function _toolFunction(string $name, string $description, array $properties = []): array
    {
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
                    'required' => array_keys($properties), // All properties required
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
            'role' => 'assistant',
            'content' => $greetingText
        ]);
    }

    /**
     * Get the latest assistant message.
     *
     * @return array
     * @throws Exception
     */
    public function getLatestAssistantMessage(): array
    {
        try {

            // Get the latest messages from the thread
            $messages = $this->_openAiClient->threads()->messages()->list($this->_threadId, [
//                'after' => 'obj_foo',
            ]);

            // Get reply from the assistant
            $reply = $messages->data[0]->content[0]->text->value;

            // Return the assistant's reply
            return [
                'role' => 'assistant',
                'content' => $reply,
            ];

        } catch (RequestException|\Exception $e) {

            // Log and throw the error
            $error = $e->getMessage();
            Craft::error($error, __METHOD__);
            throw new Exception($error);

        }
    }
}

<?php

namespace doublesecretagency\sidekick\services;

use Craft;
use craft\helpers\App;
use doublesecretagency\sidekick\constants\AiModel;
use doublesecretagency\sidekick\constants\Chat;
use doublesecretagency\sidekick\constants\Session;
use doublesecretagency\sidekick\helpers\ChatHistory;
use doublesecretagency\sidekick\helpers\SystemPrompt;
use doublesecretagency\sidekick\models\ChatMessage;
use doublesecretagency\sidekick\Sidekick;
use GuzzleHttp\Exception\RequestException;
use OpenAI;
use OpenAI\Client;
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
            Craft::error('OpenAI API key is not set.', __METHOD__);
            throw new Exception('OpenAI API key is not set.');
        }

        // If the OpenAI client is not already set
        if (!isset($this->_openAiClient)) {
            // Create a new OpenAI client
            $this->_openAiClient = OpenAI::client($this->_apiKey);
        }
    }

//    /**
//     * Sets the OpenAI client for making API requests.
//     *
//     * @param Client $client The OpenAI client.
//     */
//    public function setOpenAiClient(Client $client): void
//    {
//        $this->_openAiClient = $client;
//    }

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
//                'tools' => $this->_getTools()
//                'tools' => [
//                    [
//                        'type' => 'code_interpreter',
//                    ],
//                ]
            ]);

            // Store the assistant ID
            $this->_assistantId = $assistant['id'];

            // Store the assistant ID in the session
            $session->set(Session::ASSISTANT_ID, $assistant->id);

            // Return the assistant ID
            return $assistant->id;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the thread ID.
     *
     * @return string|null
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
            $this->_threadId = $thread['id'];

            // Store the thread ID in the session
            $session->set(Session::THREAD_ID, $thread->id);

            // Return the thread ID
            return $thread->id;

        } catch (\Exception $e) {
            return null;
        }
    }

    // ========================================================================= //

    /**
     * Send a message to the API.
     *
     * @param ChatMessage $message
     * @return array
     * @throws Exception
     */
    public function sendMessage(ChatMessage $message): array
    {
        // If API key is not set, log and throw an exception
        if (!$this->_apiKey) {
            $error = 'OpenAI API key is not set.';
            Craft::error($error, __METHOD__);
            throw new Exception($error);
        }

        try {
            // Get the assistant and thread IDs
            $this->_assistantId = $this->_getAssistantId();
            $this->_threadId    = $this->_getThreadId();

            // Add a message to the thread
            $this->_openAiClient->threads()->messages()->create($this->_threadId, [
                'role' => 'user',
                'content' => $message->content,
            ]);

            // Run the thread
            $this->_runThread();

            $messages = $this->_openAiClient->threads()->messages()->list($this->_threadId, [
//                'limit' => 10,
            ]);

            // Get reply from the assistant
            $reply = $messages->data[0]->content[0]->text->value;

            // Create the greeting message
            $r = Sidekick::$plugin->openAi->newAssistantMessage($reply);

            // Append it to the chat history
            $r->appendToChatHistory();

            // Return the results
            return [
                'success' => true,
                'messages' => [$r],
            ];

        } catch (RequestException|\Exception $e) {

            // Log and return the error
            $error = $e->getMessage();
            Craft::error($error, __METHOD__);
            return [
                'success' => false,
                'error' => $error,
            ];

        }
    }

    /**
     * Run the thread.
     *
     * @return void
     * @throws Exception
     */
    private function _runThread(): void
    {
        // Get the runs service
        $service = $this->_openAiClient->threads()->runs();

        // Create a new streaming run
        $stream = $service->createStreamed($this->_threadId, [
            'assistant_id' => $this->_assistantId,
        ]);

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

                        throw new Exception('Time to implement tools!');

//                        // Overwrite the stream with the new stream started by submitting the tool outputs
//                        $stream = $service->submitToolOutputsStreamed($run->threadId, $run->id, [
//                            'tool_outputs' => [
//                                [
//                                    'tool_call_id' => 'call_KSg14X7kZF2WDzlPhpQ168Mj',
//                                    'output' => '12',
//                                ]
//                            ],
//                        ]);
                        break;
                }
            }
        } while ($run->status !== 'completed');

        // ...
    }

    // ========================================================================= //

    /**
     * Available functions for the API to call.
     *
     * @return array[]
     */
    private function _getTools(): array
    {
        return [
//            [
//                'type' => "function",
//                'function' => [
//                    'name' => "get_delivery_date",
//                    'description' => "Get the delivery date for a customer's order. Call this whenever you need to know the delivery date, for example when a customer asks 'Where is my package'",
//                    'parameters' => [
//                        'type' => "object",
//                        'properties' => [
//                            'order_id' => [
//                                'type' => "string",
//                                'description' => "The customer's order ID.",
//                            ],
//                        ],
//                        'required' => ["order_id"],
//                        'additionalProperties' => false,
//                    ],
//                ]
//            ]
        ];
    }

    // ========================================================================= //

    /**
     * Create a new system message.
     *
     * @param string $content
     * @return ChatMessage
     */
    public function newSystemMessage(string $content): ChatMessage
    {
        return new ChatMessage('system', $content);
    }

    /**
     * Create a new user message.
     *
     * @param string $content
     * @return ChatMessage
     */
    public function newUserMessage(string $content): ChatMessage
    {
        return new ChatMessage('user', $content);
    }

    /**
     * Create a new assistant message.
     *
     * @param string $content
     * @return ChatMessage
     */
    public function newAssistantMessage(string $content): ChatMessage
    {
        return new ChatMessage('assistant', $content);
    }

    /**
     * Create a new tool message.
     *
     * @param string $content
     * @return ChatMessage
     */
    public function newToolMessage(string $content): ChatMessage
    {
        return new ChatMessage('tool', $content);
    }

    // ========================================================================= //

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
        return $this->newAssistantMessage($greetingText);
    }

    /**
     * Get the entire conversation.
     *
     * @return array
     */
    public function getConversation(): array
    {
        return ChatHistory::getConversation();
    }
}

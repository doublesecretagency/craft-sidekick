<?php

namespace doublesecretagency\sidekick\services;

use Craft;
use craft\helpers\App;
use doublesecretagency\sidekick\constants\Chat;
use doublesecretagency\sidekick\constants\Session;
use doublesecretagency\sidekick\helpers\ChatHistory;
use doublesecretagency\sidekick\models\api\Message;
use doublesecretagency\sidekick\models\api\Run;
use doublesecretagency\sidekick\models\ChatMessage;
use doublesecretagency\sidekick\Sidekick;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
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
     * @var string The endpoint URL for OpenAI's Assistants API.
     */
    private string $_apiUrl = 'https://api.openai.com';

    /**
     * @var Client The HTTP client for making API requests.
     */
    private Client $_httpClient;

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

        // Initialize the HTTP client if not already set
        if (!isset($this->_httpClient)) {
            $this->_httpClient = new Client();
        }
    }

    /**
     * Sets the HTTP client for making API requests.
     *
     * @param Client $client The HTTP client.
     */
    public function setHttpClient(Client $client): void
    {
        $this->_httpClient = $client;
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
     * Call the OpenAI API.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $payload
     * @return array
     */
    public function callApi(string $method, string $endpoint, array $payload = []): array
    {
        // Configure the API endpoint URL
        $url = "{$this->_apiUrl}/{$endpoint}";

        try {

            // Set the default options
            $options = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$this->_apiKey}",
                    'OpenAI-Beta' => 'assistants=v2',
                ]
            ];

            // If the method is POST
            if ($method === 'post') {
                // Set the JSON payload
                $options['json'] = $payload;
            }

            // Send the API request
            $response = $this->_httpClient->$method($url, $options);

            // Get the response status code and reason
            $status = $response->getStatusCode();
            $reason = $response->getReasonPhrase();

            // If the API request fails
            if ($status !== 200) {
                // Log an error and return false
                $error = "Request failed: [{$status}] {$reason}";
                Craft::error($error, __METHOD__);
                return [
                    'success' => false,
                    'error' => $error,
                ];
            }

            // Extract the actual results from the API response
            $results = json_decode($response->getBody()->getContents(), true);

            // Make a copy of the results
            $truncated = $results;

            // Whether the item is a process object
            $processObject = in_array($truncated['object'], ['assistant','thread.run']);

            // Truncate the assistant instructions
            if ($processObject) {
                $truncated['instructions'] = 'You are an assistant that helps manage Twig templates and module files for a Craft CMS website...';
            }

            // Log the results
            Craft::info("API response: ".json_encode($truncated), __METHOD__);

            // Return results successfully
            return [
                'success' => true,
                'results' => $results,
            ];

        } catch (GuzzleException|\Exception $e) {

            // Log and return the error
//            $error = "OpenAI API Request Exception: {$e->getMessage()}";
            $error = $e->getMessage();
            Craft::error($error, __METHOD__);
            return [
                'success' => false,
                'error' => $error,
            ];

        }

    }

    // ========================================================================= //

    /**
     * Get the API object ID.
     *
     * @param string $class
     * @param string $sessionId
     * @return string|null
     */
    private function _getApiObjectId(string $class, string $sessionId): ?string
    {
        try {
            // Get the session service
            $session = Craft::$app->getSession();

            // If object ID exists in the session, return it
            if ($objectId = $session->get($sessionId)) {
                return $objectId;
            }

            // Prepend the namespace
            $class = "doublesecretagency\\sidekick\\models\\api\\{$class}";

            // Create the API object
            $object = new $class();

            // Store the object ID in the session
            $session->set($sessionId, $object->id);

            // Return the object ID
            return $object->id;

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
            $this->_assistantId = $this->_assistantId ?? $this->_getApiObjectId('Assistant', Session::ASSISTANT_ID);
            $this->_threadId    = $this->_threadId    ?? $this->_getApiObjectId('Thread', Session::THREAD_ID);

            // Add a message to the thread
            new Message($this->_threadId, [
                'role' => 'user',
                'content' => $message->content,
            ]);

            // Create a run
            $run = new Run($this->_threadId, [
                'assistant_id' => $this->_assistantId,
//                'additional_instructions' => "Additional information for processing the request.",
            ]);

            // Poll for run completion
            $run->waitForCompletion();

            // Get messages
            $response = $this->callApi('get', "v1/threads/{$this->_threadId}/messages");

            // If the API call was not successful
            if (!($response['success'] ?? false)) {
                $error = ($response['error'] ?? "Unknown error.");
                Craft::error($error, __METHOD__);
                throw new Exception($error);
            }

            // Get all messages
            $messages = ($response['results']['data'] ?? []);

            // Get the first message
            $reply = ($messages[0]['content'][0]['text']['value'] ?? 'No message found.');

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
//            $error = "OpenAI API Exception: {$e->getMessage()}";
            $error = $e->getMessage();
            Craft::error($error, __METHOD__);
            return [
                'success' => false,
                'error' => $error,
            ];

        }
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

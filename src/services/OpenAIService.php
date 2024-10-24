<?php

namespace doublesecretagency\sidekick\services;

use Craft;
use craft\helpers\App;
use doublesecretagency\sidekick\constants\Constants;
use doublesecretagency\sidekick\helpers\ChatHistory;
use doublesecretagency\sidekick\helpers\SystemPrompt;
use doublesecretagency\sidekick\models\Message;
use doublesecretagency\sidekick\Sidekick;
use GuzzleHttp\Exception\GuzzleException;
use yii\base\Component;
use yii\base\Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

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
    private string $apiKey;

    /**
     * @var string The endpoint URL for OpenAI's chat completion.
     */
    private string $apiEndpoint = 'https://api.openai.com/v1/chat/completions';

    /**
     * @var Client The HTTP client for making API requests.
     */
    protected Client $httpClient;

    /**
     * Initializes the service.
     *
     * @throws Exception
     */
    public function init(): void
    {
        parent::init();

        // Retrieve the OpenAI API key from plugin settings or environment variables
        $this->apiKey = App::parseEnv(Sidekick::$plugin->getSettings()->openAiApiKey ?? '');

        // If API key is not set, throw an exception
        if (!$this->apiKey) {
            Craft::error('OpenAI API key is not set.', __METHOD__);
            throw new Exception('OpenAI API key is not set.');
        }

        // Initialize the HTTP client if not already set
        if (!isset($this->httpClient)) {
            $this->httpClient = new Client();
        }
    }

    /**
     * Sets the HTTP client for making API requests.
     *
     * @param Client $client The HTTP client.
     */
    public function setHttpClient(Client $client): void
    {
        $this->httpClient = $client;
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
     * Send the entire conversation to the API.
     *
     * @return array
     * @throws Exception
     * @throws GuzzleException
     */
    public function sendMessage(): array
    {
        // If API key is not set, log and throw an exception
        if (!$this->apiKey) {
            $error = 'OpenAI API key is not set.';
            Craft::error($error, __METHOD__);
            throw new Exception($error);
        }

        // Initialize messages with the system prompt
        $systemMessage = [
            [
                'role' => 'system',
                'content' => SystemPrompt::getPrompt(),
            ]
        ];

        // Append entire conversation
        $messages = array_merge($systemMessage, ChatHistory::getConversation());

        // Prepare the request payload
        $payload = [
            'model' => $apiRequest['model'] ?? Constants::DEFAULT_AI_MODEL,
            'messages' => $messages,
//            'tools' => $this->_getTools(),
            'max_tokens' => 1500,
            'temperature' => 0.2,
            'n' => 1,
            'stop' => null,
        ];

        try {
            // Send the API request
            $response = $this->httpClient->post($this->apiEndpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'json' => $payload,
            ]);

            // Get the response status code and reason
            $status = $response->getStatusCode();
            $reason = $response->getReasonPhrase();

            // If the API request fails
            if ($status !== 200) {
                // Log an error and return false
                $error = "OpenAI API Request failed: [{$status}] {$reason}";
                Craft::error($error, __METHOD__);
                return [
                    'success' => false,
                    'error' => $error,
                ];
            }

            // Extract the actual results from the API response
            $results = json_decode($response->getBody()->getContents(), true);

            // Log the results
            Craft::info("API response: ".json_encode($results), __METHOD__);

            // Return results successfully
            return [
                'success' => true,
                'results' => $results,
            ];

        } catch (RequestException $e) {

            // Log and return the error
            $error = "OpenAI API Request Exception: {$e->getMessage()}";
            Craft::error($error, __METHOD__);
            return [
                'success' => false,
                'error' => $error,
            ];

        } catch (\Exception $e) {

            // Log and return the error
            $error = "OpenAI API Exception: {$e->getMessage()}";
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
     * @return Message
     */
    public function newSystemMessage(string $content): Message
    {
        return new Message('system', $content);
    }

    /**
     * Create a new user message.
     *
     * @param string $content
     * @return Message
     */
    public function newUserMessage(string $content): Message
    {
        return new Message('user', $content);
    }

    /**
     * Create a new assistant message.
     *
     * @param string $content
     * @return Message
     */
    public function newAssistantMessage(string $content): Message
    {
        return new Message('assistant', $content);
    }

    /**
     * Create a new tool message.
     *
     * @param string $content
     * @return Message
     */
    public function newToolMessage(string $content): Message
    {
        return new Message('tool', $content);
    }

    // ========================================================================= //

    /**
     * Generate a greeting message.
     *
     * @return Message
     */
    public function getGreetingMessage(): Message
    {
        // Get all greeting options
        $options = Constants::GREETING_OPTIONS;

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

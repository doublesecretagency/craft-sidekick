<?php

namespace doublesecretagency\sidekick\services;

use Craft;
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
     * @var string The complex system prompt guiding the AI assistant.
     */
    private string $systemPrompt = '';

    /**
     * @var array The list of system prompt files to compile.
     */
    private array $systemPromptFiles = [
        'introduction.md',
        'general-guidelines.md',
        'actions.md',
        'formatting-style.md',
        'security-compliance.md',
        'examples.md',
    ];

    /**
     * Initializes the service.
     *
     * Loads the system prompt from a Markdown file.
     *
     * @throws Exception if the system prompt file cannot be read.
     */
    public function init()
    {
        parent::init();

        // Retrieve the OpenAI API key from plugin settings or environment variables
        $this->apiKey = Sidekick::$plugin->getSettings()->openAiApiKey ?? '';

        if (empty($this->apiKey)) {
            Craft::error('OpenAI API key is not set.', __METHOD__);
            throw new Exception('OpenAI API key is not set.');
        }

        // Compile the system prompt
        $this->_compileSystemPrompt();
    }

    /**
     * Compiles the system prompt from multiple Markdown files.
     *
     * @throws Exception if the system prompt file cannot be read.
     */
    private function _compileSystemPrompt(): void
    {
        // Get the path to the Sidekick plugin
        $path = Craft::getAlias('@sidekick');

        // Loop through each prompt file
        foreach ($this->systemPromptFiles as $file) {

            // Load the content of each prompt file
            $filePath = "{$path}/prompts/{$file}";

            // Ensure the file exists
            if (file_exists($filePath)) {
                // Load the file content
                $content = file_get_contents($filePath);
                // Ensure there's a line break between sections
                $this->systemPrompt .= "{$content}\n\n";
            } else {
                // Handle missing files if necessary
                Craft::warning("Prompt file not found: {$filePath}", __METHOD__);
            }

        }

        // If no prompt content was loaded, throw an exception
        if (!$this->systemPrompt) {
            $error = "Unable to compile the system prompt.";
            Craft::error($error, __METHOD__);
            throw new Exception($error);
        }

        // Log that the system prompt has been compiled
        Craft::info("Compiled system prompt.", __METHOD__);
    }

    /**
     * Retrieves the current Craft CMS version.
     *
     * @return string
     */
    public function getCurrentCraftVersion(): string
    {
        return Craft::$app->version;
    }

    /**
     * Calls the AI assistant's chat completion API.
     *
     * @param array $apiRequest The API request payload.
     * @return array The API response.
     * @throws GuzzleException
     */
    public function callChatCompletion(array $apiRequest): array
    {
        $client = new Client();

        // Extract additional context if available
        $additionalContext = $apiRequest['additionalContext'] ?? null;
        unset($apiRequest['additionalContext']);

        // Initialize messages with the system prompt
        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt],
        ];

        // Append conversation messages
        if (isset($apiRequest['messages']) && is_array($apiRequest['messages'])) {
            $messages = array_merge($messages, $apiRequest['messages']);
        }

        // If additional context is provided, prepend it to the messages
        if ($additionalContext && is_array($additionalContext)) {
            foreach ($additionalContext as $contextItem) {
                $filePath = $contextItem['filePath'];
                $content = $contextItem['content'];
                $contextMessage = "The file {$filePath} has the following content:\n\n{$content}";
                array_splice($messages, 1, 0, [['role' => 'system', 'content' => $contextMessage]]);
                Craft::info("Added additional context to messages: {$contextMessage}", __METHOD__);
            }
        }

        // Prepare the request payload
        $payload = [
            'model' => $apiRequest['model'] ?? 'gpt-4',
            'messages' => $messages,
            'max_tokens' => 1500,
            'temperature' => 0.2,
            'n' => 1,
            'stop' => null,
        ];

        // Log the API request payload
        Craft::info("Sending API request with payload: " . json_encode($payload), __METHOD__);

        try {
            $response = $client->post($this->apiEndpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'json' => $payload,
            ]);

            $responseBody = json_decode($response->getBody()->getContents(), true);
            Craft::info("Received API response: " . json_encode($responseBody), __METHOD__);

            if (isset($responseBody['choices'][0]['message']['content'])) {
                $content = $responseBody['choices'][0]['message']['content'];
                Craft::info("Assistant's raw response content: {$content}", __METHOD__);

                // Validate the response to ensure only allowed commands are present
                $validatedContent = $this->validateResponse($content);
                Craft::info("Validated assistant response content: {$validatedContent}", __METHOD__);

                return [
                    'success' => true,
                    'results' => $validatedContent,
                ];
            } else {
                Craft::error('Invalid response structure from OpenAI.', __METHOD__);
                return [
                    'success' => false,
                    'error' => 'Invalid response structure from OpenAI.',
                ];
            }
        } catch (RequestException $e) {
            Craft::error('OpenAI API Request Exception: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => 'OpenAI API Request Exception: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            Craft::error('OpenAI API Exception: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => 'OpenAI API Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Validates the assistant's response to ensure it's a valid JSON or regular message.
     *
     * @param string $content The assistant's response content.
     * @return string The validated content.
     */
    private function validateResponse(string $content): string
    {
        // Attempt to decode JSON
        $decodedJson = json_decode($content, true);

        // If not JSON, return content as is
        if (json_last_error() !== JSON_ERROR_NONE) {
            Craft::info("Assistant's response is not JSON, returning as is.", __METHOD__);
            return $content;
        }

        // If any required keys are missing, return content as is
        if (!isset($decodedJson['operation'], $decodedJson['filePath'])) {
            Craft::warning("JSON response missing required keys.", __METHOD__);
            return $content;
        }

        // It's valid JSON with required keys
        Craft::info("Assistant's response is valid JSON with required keys.", __METHOD__);
        return $content;
    }
}

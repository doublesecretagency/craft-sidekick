<?php

namespace doublesecretagency\sidekick\services;

use Craft;
use doublesecretagency\sidekick\Sidekick;
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

        // Load the system prompt from the Markdown file
        $systemPromptPath = Craft::getAlias('@sidekick') . '/prompts/start-chat.md';

        if (!file_exists($systemPromptPath)) {
            Craft::error("System prompt file not found at {$systemPromptPath}.", __METHOD__);
            throw new Exception("System prompt file not found at {$systemPromptPath}.");
        }

        $systemPromptContent = file_get_contents($systemPromptPath);

        if ($systemPromptContent === false) {
            Craft::error("Failed to read system prompt file at {$systemPromptPath}.", __METHOD__);
            throw new Exception("Failed to read system prompt file at {$systemPromptPath}.");
        }

        $this->systemPrompt = $systemPromptContent;
        Craft::info("Loaded system prompt from {$systemPromptPath}", __METHOD__);
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
            'temperature' => 0.7,
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
     * Validates the assistant's response to ensure only supported commands are present.
     *
     * @param string $content The assistant's response content.
     * @return string The validated content with unsupported commands removed.
     */
    private function validateResponse(string $content): string
    {
        // Define allowed commands
        $allowedCommands = ['CREATE_FILE', 'UPDATE_FILE', 'DELETE_FILE'];

        // Regex to match any command enclosed in square brackets
        $pattern = '#\[(\w+)(?:\s.*?)?\]#';
        Craft::info("Validating assistant response with pattern: {$pattern}", __METHOD__);
        preg_match_all($pattern, $content, $matches);

        foreach ($matches[1] as $command) {
            if (!in_array($command, $allowedCommands)) {
                // Log the unrecognized command
                Craft::warning("Unrecognized file operation command detected and removed: [{$command}]", __METHOD__);

                // Remove the unsupported command from the content
                // Also remove the content within the command if it's a block command
                if (in_array($command, ['CREATE_FILE', 'UPDATE_FILE'])) {
                    // Remove both the opening and closing tags along with the content
                    $blockPattern = '#\[' . preg_quote($command, '#') . '(?:\s.*?)?\].*?\[/'. preg_quote($command, '#') .'\]#s';
                    Craft::info("Removing block command with pattern: {$blockPattern}", __METHOD__);
                    $content = preg_replace($blockPattern, '', $content);
                } elseif ($command === 'DELETE_FILE') {
                    // Remove self-closing DELETE_FILE commands
                    $selfClosingPattern = '#\[' . preg_quote($command, '#') . '(?:\s.*?)?/\]#s';
                    Craft::info("Removing self-closing command with pattern: {$selfClosingPattern}", __METHOD__);
                    $content = preg_replace($selfClosingPattern, '', $content);
                } else {
                    // For any other unsupported command, remove just the command
                    $simplePattern = '#\[' . preg_quote($command, '#') . '(?:\s.*?)?\]#s';
                    Craft::info("Removing unsupported command with pattern: {$simplePattern}", __METHOD__);
                    $content = preg_replace($simplePattern, '', $content);
                }
            }
        }

        return $content;
    }
}

<?php

namespace doublesecretagency\sidekick\services;

use Craft;
use doublesecretagency\sidekick\helpers\ActionsHelper;
use doublesecretagency\sidekick\Sidekick;
use GuzzleHttp\Exception\GuzzleException;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
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
     * @var Client The HTTP client for making API requests.
     */
    protected Client $httpClient;

    /**
     * @var array The list of system prompt files to compile.
     */
    private array $systemPromptFiles = [
        'introduction.md',
        'general-guidelines.md',
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

    /**
     * Sets the system prompt.
     *
     * @param string $prompt The system prompt.
     */
    public function setSystemPrompt(string $prompt): void
    {
        $this->systemPrompt = $prompt;
    }

    /**
     * Compiles the system prompt from multiple Markdown files.
     *
     * @throws Exception if the system prompt file cannot be read.
     */
    private function _compileSystemPrompt(): void
    {
        // Get the path to the Sidekick plugin
        $path = Craft::getAlias('@doublesecretagency/sidekick');

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

        // Append the actions documentation to the system prompt
        $this->appendActionsDocs();

        // Log that the system prompt has been compiled
        Craft::info("Compiled system prompt.", __METHOD__);
    }

    /**
     * Appends the actions documentation to the system prompt.
     */
    public function appendActionsDocs(): void
    {
        // Get the actions service
        $actionsService = Sidekick::$plugin->actions;

        // Get all methods from the ActionsHelper class
        $methods = (new ReflectionClass(ActionsHelper::class))->getMethods();

        // Create a new instance of the DocBlockFactory
        $docFactory = DocBlockFactory::createInstance();

        // Get the path to the Sidekick plugin
        $path = Craft::getAlias('@doublesecretagency/sidekick');

        // Load the content of the actions documentation
        $filePath = "{$path}/prompts/actions.md";

        // If the file doesn't exist, bail
        if (!file_exists($filePath)) {
            return;
        }

        // Load the actions documentation
        $actionsDocumentation = file_get_contents($filePath);

        // Initialize the actions documentation
        $listOfActions = '';

        // Loop through each method
        foreach ($methods as $method) {

            // Get the method name
            $action = $method->getName();

            // Skip methods that aren't valid actions
            if (!in_array($action, $actionsService->getValidActions(), true)) {
                continue;
            }

            // Get the method's doc comment
            $docComment = $method->getDocComment();

            // If no doc comment is present, skip this method
            if (!$docComment) {
                continue;
            }

            // Create a new DocBlock instance
            $docBlock = $docFactory->create($docComment);

            // Get the summary and description
            $summary = $docBlock->getSummary();
            $description = $docBlock->getDescription();

            // Append the action documentation to the system prompt
            $listOfActions .= "\n{$summary}\n\n{$description}\n";
        }

        // Replace the placeholder with the actions documentation
        $actionsDocumentation = str_replace('{listOfActions}', $listOfActions, $actionsDocumentation);

        // Append the actions documentation to the system prompt
        $this->systemPrompt .= $actionsDocumentation;
    }

    /**
     * Get the system prompt.
     *
     * @return string
     */
    public function getSystemPrompt(): string
    {
        return $this->systemPrompt;
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
        $client = $this->httpClient;

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
                $validatedContent = $this->_validateResponse($content);
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
    private function _validateResponse(string $content): string
    {
        // Attempt to decode JSON
        $decodedJson = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Assistant's response is not JSON; treat it as a natural language response
            Craft::info("Assistant's response is not JSON. Treating as natural language response.", __METHOD__);
            return $content;
        }

        // Check for 'actions' key in JSON
        if (!isset($decodedJson['actions']) || !is_array($decodedJson['actions'])) {
            Craft::warning("Assistant's JSON response missing 'actions' key.", __METHOD__);
            return $content;
        }

        // Validate each action
        $validActions = Sidekick::$plugin->actions->getValidActions();

        foreach ($decodedJson['actions'] as $action) {
            if (!isset($action['action']) || !in_array($action['action'], $validActions)) {
                Craft::warning("Invalid or unsupported action: " . ($action['action'] ?? 'undefined'), __METHOD__);
                return "I'm sorry, but one of the actions you requested is not supported.";
            }
        }

        // If all validations pass, return the original content
        return $content;
    }
}

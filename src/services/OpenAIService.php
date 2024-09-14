<?php

namespace doublesecretagency\sidekick\services;

use Craft;
use craft\elements\Asset;
use doublesecretagency\sidekick\Sidekick;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use yii\base\Component;
use yii\base\Exception;

/**
 * Class OpenAIService
 *
 * Handles communication with the OpenAI API for generating content.
 */
class OpenAIService extends Component
{
    // OpenAI API endpoint
    private string $apiEndpoint = 'https://api.openai.com/v1/chat/completions';

    // OpenAI API key
    private string $apiKey;

    /**
     * Initializes the service.
     */
    public function init(): void
    {
        parent::init();

        // Get the API key from plugin settings
        $this->apiKey = Sidekick::$plugin->getSettings()->openAiApiKey ?? '';

        // Throw an error if API key is not set
        if (empty($this->apiKey)) {
            throw new Exception('OpenAI API key is not set.');
        }
    }

    /**
     * @param array $payload
     * @return array
     * @throws GuzzleException
     */
    public function callChatCompletion(array $payload): array
    {
        try {
            $client = new Client();

            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $body = json_decode($response->getBody(), true);

            if (isset($body['choices'][0]['message']['content'])) {
                return [
                    'success' => true,
                    'results' => trim($body['choices'][0]['message']['content']),
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to generate completion.',
                ];
            }
        } catch (\Exception $e) {
            Craft::error('Error fetching OpenAI completion: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => 'Error fetching OpenAI completion: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sends a prompt to OpenAI and retrieves the response.
     *
     * @param string $prompt
     * @param int $maxTokens
     * @param float $temperature
     * @return array
     */
    public function getCompletion(string $prompt, int $maxTokens = 150, float $temperature = 0.7): array
    {
        try {
            // Prepare the Guzzle client
            $client = new Client();

            // Send a POST request to the OpenAI API
            $response = $client->post($this->apiEndpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an assistant that helps generate and modify Twig templates for a Craft CMS website.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'max_tokens' => $maxTokens,
                    'temperature' => $temperature,
                ],
            ]);

            // Parse the response
            $body = json_decode($response->getBody(), true);

            // Check if the response contains a result
            if (isset($body['choices'][0]['message']['content'])) {
                return [
                    'success' => true,
                    'results' => trim($body['choices'][0]['message']['content']),
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to generate completion.',
                ];
            }

        } catch (\Exception $e) {
            Craft::error('Error fetching OpenAI completion: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => 'Error fetching OpenAI completion: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generates alt text for a given image asset using OpenAI.
     *
     * @param Asset $asset
     * @return string
     * @throws Exception
     */
    public function generateAltText(Asset $asset): string
    {
        // Ensure the asset is an image
        if ($asset->kind !== 'image') {
            throw new Exception('The provided asset is not an image.');
        }

        // Prepare the prompt for OpenAI
        $prompt = "Generate a descriptive and SEO-friendly alt tag for the image located at the following URL: " . $asset->getUrl();

        // Call the OpenAI API to generate the alt text
        $response = $this->getCompletion($prompt, 60, 0.5);

        // If the call to OpenAI was successful
        if ($response['success']) {
            return $response['results'];
        }

        // If the call to OpenAI failed, throw an error
        throw new Exception('Failed to generate alt text: ' . $response['error']);
    }

    /**
     * Generates code snippets for Twig templates using OpenAI.
     *
     * @param string $description The description provided by the user.
     * @param string $operation The type of operation (insert, replace, append, prepend, conditional).
     * @return string|null The generated code snippet or null on failure.
     */
    public function generateTwigSnippet(string $description, string $operation): ?string
    {
        // Define predefined prompts based on the operation
        $prompts = [
            'insert' => "Generate a Twig code snippet to {$description}. Provide the code only without explanations.",
            'replace' => "Generate a Twig code snippet to {$description}. Replace the existing code. Provide the code only without explanations.",
            'append' => "Generate a Twig code snippet to {$description}. Append it to the existing template. Provide the code only without explanations.",
            'prepend' => "Generate a Twig code snippet to {$description}. Prepend it to the existing template. Provide the code only without explanations.",
            'conditional' => "Generate a Twig code snippet to {$description}. Include necessary conditions. Provide the code only without explanations.",
        ];

        if (!isset($prompts[$operation])) {
            Craft::error("Invalid operation type: {$operation}", __METHOD__);
            return null;
        }

        $prompt = $prompts[$operation];

        // Get the completion from OpenAI
        $response = $this->getCompletion($prompt, 200, 0.7);

        if ($response['success']) {
            return $response['results'];
        }

        Craft::error('Failed to generate Twig snippet: ' . $response['error'], __METHOD__);
        return null;
    }
}

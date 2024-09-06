<?php

namespace doublesecretagency\sidekick\services;

use Craft;
use craft\elements\Asset;
use doublesecretagency\sidekick\Sidekick;
use GuzzleHttp\Client;
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
     * Sends a prompt to OpenAI and retrieves the response.
     *
     * @param string $prompt
     * @param int $maxTokens
     * @param float $temperature
     * @return array
     * @throws Exception
     */
    public function getCompletion(string $prompt, int $maxTokens = 100, float $temperature = 0.7): array
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
                    'model' => 'gpt-4o-2024-05-13',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an assistant that generates image descriptions for alt text.',
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
                    'results' => $body['choices'][0]['message']['content'],
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
        $prompt = "Describe the image at the following URL as an alt text: " . $asset->getUrl();

        // Call the OpenAI API to generate the alt text
        $response = $this->getCompletion($prompt);

        // If the call to OpenAI was successful
        if ($response['success']) {
            return $response['results'];
        }

        // If the call to OpenAI failed, throw an error
        throw new Exception('Failed to generate alt text: ' . $response['error']);
    }

}

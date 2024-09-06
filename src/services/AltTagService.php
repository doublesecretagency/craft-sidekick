<?php

namespace doublesecretagency\sidekick\services;

use Craft;
use craft\elements\Asset;
use yii\base\Component;

/**
 * Class AltTagService
 *
 * Handles the generation of alt text for images using the OpenAI API.
 */
class AltTagService extends Component
{

    /** @var OpenAIService */
    private OpenAIService $openAIService;

    /**
     * Initializes the service.
     */
    public function init(): void
    {
        parent::init();

        // Get the OpenAI service instance
        $this->openAIService = Craft::$app->get('openAIService');
    }

    /**
     * Generates an alt tag for the given asset.
     *
     * @param Asset $asset
     * @return string|null
     */
    public function generateAltText(Asset $asset): ?string
    {
        // Generate prompt based on the asset's metadata
        $prompt = $this->buildPrompt($asset);

        // Get the alt text from OpenAI
        return $this->openAIService->getCompletion($prompt);
    }

    /**
     * Builds a prompt to send to OpenAI for alt text generation.
     *
     * @param Asset $asset
     * @return string
     */
    private function buildPrompt(Asset $asset): string
    {
        // Use the filename as a starting point
        $prompt = "Generate a descriptive alt tag for an image named '{$asset->filename}'.";

        // Include image dimensions if available
        if ($asset->width && $asset->height) {
            $prompt .= " The image dimensions are {$asset->width}x{$asset->height}.";
        }

        // Add context or additional details if available
        // (Here you can extend the prompt with more information as needed)

        return $prompt;
    }

    /**
     * Saves the generated alt text to the asset's `alt` field.
     *
     * @param Asset $asset
     * @param string $altText
     * @return bool
     */
    public function saveAltText(Asset $asset, string $altText): bool
    {
        // Assign the alt text to the asset's alt field
        $asset->alt = $altText;

        // Save the asset
        return Craft::$app->getElements()->saveElement($asset);
    }

}

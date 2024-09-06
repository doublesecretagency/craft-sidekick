<?php

namespace doublesecretagency\sidekick\services;

use Craft;
use craft\elements\Entry;
use craft\models\Section;
use yii\base\Component;

/**
 * Class DummyDataService
 *
 * Handles the seeding of dummy data into sections.
 */
class DummyDataService extends Component
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
     * Seeds dummy entries into the given section.
     *
     * @param Section $section
     * @param int $count
     * @return bool
     */
    public function seedDummyData(Section $section, int $count = 10): bool
    {
        // Generate the dummy data for the section
        for ($i = 0; $i < $count; $i++) {
            $entry = new Entry();
            $entry->sectionId = $section->id;
            $entry->typeId = $section->getEntryTypes()[0]->id;
            $entry->title = $this->generateDummyTitle();
            $entry->setFieldValues([
                'body' => $this->generateDummyContent(),
            ]);

            // Save the entry
            if (!Craft::$app->getElements()->saveElement($entry)) {
                Craft::error("Failed to save dummy entry to section {$section->name}", __METHOD__);
                return false;
            }
        }

        return true;
    }

    /**
     * Generates a dummy title using OpenAI.
     *
     * @return string|null
     */
    private function generateDummyTitle(): ?string
    {
        $prompt = "Generate a creative and catchy title for a blog post.";
        return $this->openAIService->getCompletion($prompt);
    }

    /**
     * Generates dummy content using OpenAI.
     *
     * @return string|null
     */
    private function generateDummyContent(): ?string
    {
        $prompt = "Generate a paragraph of random content suitable for a blog post.";
        return $this->openAIService->getCompletion($prompt);
    }

}

<?php

namespace doublesecretagency\sidekick\skills;

use Craft;
use craft\elements\Entry;
use doublesecretagency\sidekick\models\SkillResponse;
use Throwable;

class Entries
{
    /**
     * Create a new entry.
     *
     * @param string $title A creatively and randomly generated title for the entry.
     * @param string $jsonConfig A JSON string containing the configuration for the entry.
     * @return SkillResponse
     */
    public static function createEntry(string $title, string $jsonConfig): SkillResponse
    {
        // Configure the new entry
        $entry = new Entry();
        $entry->sectionId = 1;
        $entry->typeId = 1;
        $entry->title = $title;

        // Optionally set additional field values:
        // $entry->setFieldValue('fieldHandle', 'Field Value');

        // Attempt to save the element
        try {
            // If unable to save the entry, return an error response
            if (!Craft::$app->elements->saveElement($entry)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to create entry: " . implode(', ', $entry->getErrorSummary(true)),
                ]);
            }
        } catch (Throwable $e) {
            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to create the entry. {$e->getMessage()}",
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Entry \"{$title}\" has been created. [{$jsonConfig}]",
//            'response' => $config,
        ]);
    }
}

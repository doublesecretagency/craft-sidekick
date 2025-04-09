<?php
/**
 * Sidekick plugin for Craft CMS
 *
 * Your AI companion for rapid Craft CMS development.
 *
 * @author    Double Secret Agency
 * @link      https://plugins.doublesecretagency.com/
 * @copyright Copyright (c) 2025 Double Secret Agency
 */

namespace doublesecretagency\sidekick\skills\read;

use Craft;
use craft\elements\Entry;
use craft\helpers\Json;
use doublesecretagency\sidekick\models\SkillResponse;
use Throwable;

class Entries
{
    /**
     * Create a new entry.
     *
     * If you do not have a clear understanding of which sections exist, call the `getSections` skill first.
     *
     * The `$jsonConfig` parameter may contain any of the following data:
     *
     * {
     *     "sectionId": 1, // Required
     *     "typeId": 1, // Required
     *     "title": "My New Entry", // Required
     *     "slug": "my-new-entry",
     *     "status": "live",
     * }
     *
     * @param string $jsonConfig A JSON string containing the configuration for the entry.
     * @return SkillResponse
     */
    public static function createEntry(string $jsonConfig): SkillResponse
    {
        // Decode the JSON configuration
        $data = Json::decode($jsonConfig);

        // Configure the new entry
        $entry = new Entry();
        $entry->sectionId = ($data['sectionId'] ?? 1);
        $entry->typeId = ($data['typeId'] ?? 1);
        $entry->title = ($data['title'] ?? 'New Entry');

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
            'message' => "Entry \"{$entry->title}\" has been created.",
//            'response' => $config,
        ]);
    }
}

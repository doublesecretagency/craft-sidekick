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

namespace doublesecretagency\sidekick\skills;

use Craft;
use craft\elements\Entry;
use craft\helpers\Json;
use doublesecretagency\sidekick\helpers\ElementsHelper;
use doublesecretagency\sidekick\models\SkillResponse;
use Throwable;
use yii\base\Exception;

/**
 * @category Entries
 */
class Entries extends BaseSkillSet
{
    /**
     * Get basic information (id, title, slug) about all entries.
     *
     * Optionally specify a section handle to filter the results.
     *
     * @param string $sectionHandle Optional handle of the section to filter by. Set to empty string to get all entries.
     * @return SkillResponse
     */
    public static function getEntriesOverview(string $sectionHandle): SkillResponse
    {
        // Initialize the query
        $query = Entry::find()->select(['id', 'title', 'slug']);

        // If a section handle is provided
        if ($sectionHandle) {
            // Filter the query by that section
            $query->section($sectionHandle);
        }

        // Get all entries
        $entries = $query->all();

        // Initialize results array
        $results = [];

        // Loop over each entry
        foreach ($entries as $entry) {
            // Append title & slug to results
            $results[] = [
                'id'    => $entry->id,
                'title' => $entry->title,
                'slug'  => $entry->slug,
            ];
        }

        // Optionally append section handle to error/success messages
        $inSection = ($sectionHandle ? " in section \"{$sectionHandle}\"" : '');

        // If no results
        if (!$results) {
            // Return error message
            return new SkillResponse([
                'success' => false,
                'message' => "No entries found{$inSection}."
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed basic info for all entries{$inSection}.",
            'response' => Json::encode($results)
        ]);
    }

    /**
     * Get an entry.
     *
     * @param string $entryId ID of the entry to retrieve.
     * @return SkillResponse
     */
    public static function getEntry(string $entryId): SkillResponse
    {
        // Get the entry by ID
        $entry = Craft::$app->getElements()->getElementById($entryId);

        // If no such entry exists
        if (!$entry) {
            // Return error message
            return new SkillResponse([
                'success' => false,
                'message' => "Can't find entry with the ID {$entryId}."
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Retrieved entry \"{$entry->title}\".",
            'response' => Json::encode($entry)
        ]);
    }

    /**
     * Create a new entry.
     *
     * If you do not have a clear understanding of which sections exist, call the `getSections` skill first.
     *
     * @param string $jsonConfig JSON-stringified configuration for the element. See the "Element Configs" instructions.
     * @return SkillResponse
     */
    public static function createEntry(string $jsonConfig): SkillResponse
    {
        // Configure the new entry
        $entry = new Entry();

        // Populate the element
        ElementsHelper::populateElement($entry, $jsonConfig);

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

    /**
     * Update an existing entry.
     *
     * @param string $entryId ID of the entry to update.
     * @param string $jsonConfig JSON-stringified configuration for the element. See the "Element Configs" instructions.
     * @return SkillResponse
     */
    public static function updateEntry(string $entryId, string $jsonConfig): SkillResponse
    {
        // Get the entry by ID
        $entry = Craft::$app->getElements()->getElementById($entryId);

        // If no such entry exists
        if (!$entry) {
            // Return error message
            return new SkillResponse([
                'success' => false,
                'message' => "Can't find entry with the ID {$entryId}."
            ]);
        }

        // Populate the element
        ElementsHelper::populateElement($entry, $jsonConfig);

        // Attempt to save the element
        try {
            // If unable to save the entry, return an error response
            if (!Craft::$app->elements->saveElement($entry)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to update entry: " . implode(', ', $entry->getErrorSummary(true)),
                ]);
            }
        } catch (Throwable $e) {
            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to update the entry. {$e->getMessage()}",
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Entry \"{$entry->title}\" has been updated.",
//            'response' => $config,
        ]);
    }

    /**
     * Delete an entry.
     *
     * ALWAYS ASK FOR CONFIRMATION!! This is a very destructive action.
     *
     * Force the user to re-enter the slug of the entry they are deleting.
     *
     * @param string $entryId ID of the entry to delete.
     * @return SkillResponse
     */
    public static function deleteEntry(string $entryId): SkillResponse
    {
        try {
            // Get the elements service
            $elements = Craft::$app->getElements();

            // Get the entry by ID
            $entry = $elements->getElementById($entryId);

            // If no such entry exists
            if (!$entry) {
                // Throw an error message
                throw new Exception("No matching entry found.");
            }

            // Delete the entry by its ID
            $elements->deleteElementById($entryId);

        } catch (Throwable $e) {

            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to delete entry {$entryId}. {$e->getMessage()}",
            ]);

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Successfully deleted entry \"{$entry->title}\".",
        ]);
    }
}

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
use craft\errors\ElementNotFoundException;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use doublesecretagency\sidekick\helpers\ElementsHelper;
use doublesecretagency\sidekick\helpers\SkillsHelper;
use doublesecretagency\sidekick\helpers\VersionHelper;
use doublesecretagency\sidekick\models\SkillResponse;
use Throwable;
use yii\base\Exception;

/**
 * @category Entries
 */
class Entries extends BaseSkillSet
{
    /**
     * Get basic information about all entries. Optionally specify a section to filter the results.
     *
     * Use this tool to get an overview of all entries in the system.
     * For details on a specific entry, use the `getEntry` tool afterward.
     *
     * @param string $sectionHandle Optional handle of the section to filter by. Set to empty string to get all entries.
     * @return SkillResponse
     * @throws Exception
     */
    public static function getAllEntries(string $sectionHandle): SkillResponse
    {
        // Initialize the query
        $query = Entry::find()->select([
            'id',
            'sectionId',
            'title',
            'slug'
        ]);

        // If a section handle is provided
        if ($sectionHandle) {

            // Get the section
            $section = VersionHelper::sectionsService()->getSectionByHandle($sectionHandle);

            // If no such section exists, throw an error
            if (!$section) {
                throw new Exception("No section found with the handle \"{$sectionHandle}\".");
            }

            // Filter the query by that section
            $query->section($sectionHandle);
        }

        // Get all entries (regardless of status)
        $entries = $query->status(null)->all();

        // Initialize results array
        $results = [];

        // Loop over each entry
        /** @var Entry $entry */
        foreach ($entries as $entry) {
            // Append basic details to results
            $results[] = [
                'id'        => $entry->id,
                'sectionId' => $entry->sectionId,
                'title'     => $entry->title,
                'slug'      => $entry->slug,
            ];
        }

        // By default, no section is specified,
        // so end the message with a period
        $inSection = '.';

        // If a section was specified
        if ($sectionHandle) {
            // Append section link to error/success messages
            $editUrl = UrlHelper::cpUrl("entries/{$section->handle}");
            $inSection = " in section > [$section->name]($editUrl)";
        }

        // If no results
        if (!$results) {
            // Return success message with no results
            return new SkillResponse([
                'success' => true,
                'message' => "No entries found{$inSection}"
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed basic info for all entries{$inSection}",
            'response' => SkillsHelper::toCsv($results)
        ]);
    }

    /**
     * Get an entry.
     *
     * If you don't know which entries exist, you may find it helpful to call the `getAllEntries` tool instead.
     * When doing so, if possible, you may also find it helpful to specify the section handle (if it is known).
     *
     * @param string $entryId ID of the entry to retrieve.
     * @return SkillResponse
     * @throws Exception
     */
    public static function getEntry(string $entryId): SkillResponse
    {
        // Get the entry by ID
        $entry = Craft::$app->getElements()->getElementById($entryId);

        // If no such entry exists
        if (!$entry) {
            throw new Exception("Can't find entry with the ID {$entryId}.");
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Entry read > [{$entry->title}]({$entry->getCpEditUrl()})",
            'response' => Json::encode($entry)
        ]);
    }

    /**
     * Create a new entry.
     *
     * If you do not have a clear understanding of which sections exist, call the `getAllSections` skill first.
     *
     * @param string $jsonConfig JSON-stringified configuration for the element. See the "Element Configs" instructions.
     * @return SkillResponse
     * @throws Exception
     * @throws Throwable
     * @throws ElementNotFoundException
     */
    public static function createEntry(string $jsonConfig): SkillResponse
    {
        // Configure the new entry
        $entry = new Entry();

        // Populate the element
        ElementsHelper::populateElement($entry, $jsonConfig);

        // If unable to save the entry, throw an exception
        if (!Craft::$app->elements->saveElement($entry)) {
            throw new Exception("Unable to create entry: " . implode(', ', $entry->getErrorSummary(true)));
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Entry created > [{$entry->title}]({$entry->getCpEditUrl()})",
//            'response' => $config,
        ]);
    }

    /**
     * Update an existing entry.
     *
     * @param string $entryId ID of the entry to update.
     * @param string $jsonConfig JSON-stringified configuration for the element. See the "Element Configs" instructions.
     * @return SkillResponse
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws Throwable
     */
    public static function updateEntry(string $entryId, string $jsonConfig): SkillResponse
    {
        // Get the entry by ID
        $entry = Craft::$app->getElements()->getElementById($entryId);

        // If no such entry exists
        if (!$entry) {
            throw new Exception("Can't find entry with the ID {$entryId}.");
        }

        // Populate the element
        ElementsHelper::populateElement($entry, $jsonConfig);

        // If unable to save the entry, throw an exception
        if (!Craft::$app->elements->saveElement($entry)) {
            throw new Exception("Unable to update entry: " . implode(', ', $entry->getErrorSummary(true)));
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Entry updated > [{$entry->title}]({$entry->getCpEditUrl()})",
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
     * @throws Exception
     * @throws Throwable
     */
    public static function deleteEntry(string $entryId): SkillResponse
    {
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

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Entry deleted > {$entry->title}",
        ]);
    }
}

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
use craft\errors\EntryTypeNotFoundException;
use craft\helpers\Json;
use craft\models\EntryType;
use craft\models\FieldLayout;
use doublesecretagency\sidekick\helpers\SkillsHelper;
use doublesecretagency\sidekick\helpers\VersionHelper;
use doublesecretagency\sidekick\models\SkillResponse;
use Throwable;
use yii\base\Exception;

/**
 * @category Entry Types
 */
class EntryTypes extends BaseSkillSet
{
    /**
     * @inheritdoc
     */
    protected function restrictedMethods(): array
    {
        // All methods available by default
        $restrictedMethods = [];

        // Get the general config settings
        $config = Craft::$app->getConfig()->getGeneral();

        // Methods unavailable when `allowAdminChanges` is false
        if (!$config->allowAdminChanges) {
            $restrictedMethods[] = 'createEntryType';
            $restrictedMethods[] = 'updateEntryType';
            $restrictedMethods[] = 'deleteEntryType';
        }

        // Methods unavailable in Craft 4
        if (VersionHelper::craftBetween('4.0.0', '5.0.0')) {
            $restrictedMethods[] = 'getAllEntryTypes';
            $restrictedMethods[] = 'createEntryType';
            $restrictedMethods[] = 'updateEntryType';
            $restrictedMethods[] = 'deleteEntryType';
        }

        // Return list of restricted methods
        return $restrictedMethods;
    }

    // ========================================================================= //

    /**
     * Get a complete list of existing entry types.
     *
     * If you are unfamiliar with the existing entry types, you MUST call this tool before creating, reading, updating, or deleting entry types.
     * Eagerly call this if an understanding of the current entry types is required.
     *
     * @return SkillResponse
     */
    public static function getAllEntryTypes(): SkillResponse
    {
        // Get all entry types
        $entryTypes = VersionHelper::sectionsService()->getAllEntryTypes();

        // Initialize results array
        $results = [];

        // Loop through each entry type
        foreach ($entryTypes as $entryType) {
            // Append data to results
            $results[] = [
                'id'            => $entryType->id,
                'fieldLayoutId' => $entryType->fieldLayoutId,
                'name'          => $entryType->name,
                'handle'        => $entryType->handle,
            ];
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed the existing entry types.",
            'response' => SkillsHelper::toCsv($results)
        ]);
    }

    /**
     * Create a new entry type.
     *
     * @param string $entryTypeConfig JSON-stringified configuration for the `EntryType` model.
     * @return SkillResponse
     * @throws Exception
     * @throws Throwable
     * @throws EntryTypeNotFoundException
     */
    public static function createEntryType(string $entryTypeConfig): SkillResponse
    {
        // Decode the JSON configurations
        $config = Json::decode($entryTypeConfig);

        // Create the field layout
        $layout = FieldLayout::createFromConfig($config['fieldLayout'] ?? []);

        // If layout is missing a type, throw an exception
        if (!$layout->type) {
            throw new Exception("Field layout is missing a type. Type should be specified in the configuration.");
        }

        // If unable to save the field layout, throw an exception
        if (!Craft::$app->getFields()->saveLayout($layout, false)) {
            $errors = implode(', ', $layout->getErrorSummary(true));
            throw new Exception("Unable to create field layout: {$errors}");
        }

        // Set the field layout in the configuration
        $config['fieldLayout'] = $layout;

        // Create the entry type
        $entryType = new EntryType($config);

        // If the entry type is not valid, throw an exception
        if (!$entryType->validate()) {
            $errors = implode(', ', $entryType->getErrorSummary(true));
            throw new Exception("Invalid entry type configuration: {$errors}");
        }

        // If unable to save the entry type, throw an exception
        if (!VersionHelper::sectionsService()->saveEntryType($entryType)) {
            $errors = implode(', ', $entryType->getErrorSummary(true));
            throw new Exception("Unable to create entry type: {$errors}");
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Entry type \"{$entryType['name']}\" with handle \"{$entryType['handle']}\" has been created.",
//            'response' => $config,
        ]);
    }

    /**
     * Update an existing entry type with a new configuration.
     *
     * Make sure you understand the EXISTING entry type configuration before updating.
     * If needed, you MUST call `getAllEntryTypes` to get the current configuration.
     *
     * For large updates, ask for confirmation before proceeding.
     *
     * @param string $entryTypeHandle Handle of the entry type to update.
     * @param string $newConfig JSON-stringified configuration for the entry type.
     * @return SkillResponse
     * @throws EntryTypeNotFoundException
     * @throws Exception
     * @throws Throwable
     */
    public static function updateEntryType(string $entryTypeHandle, string $newConfig): SkillResponse
    {
        // Get the entry type
        $entryType = VersionHelper::sectionsService()->getEntryTypeByHandle($entryTypeHandle);

        // If entry type doesn't exist, throw an exception
        if (!$entryType) {
            throw new Exception("Unable to update, entry type `{$entryTypeHandle}` does not exist.");
        }

        // Decode the JSON configuration
        $config = Json::decode($newConfig);

        // If the configuration was not valid JSON, throw an exception
        if (!is_array($config)) {
            throw new Exception("Invalid JSON provided for entry type configuration.");
        }

        // Update the entry type with the new configuration
        $entryType->name = ($config['name'] ?? $entryType->name);
        $entryType->handle = ($config['handle'] ?? $entryType->handle);

        // If unable to save the entry type, throw an exception
        if (!VersionHelper::sectionsService()->saveEntryType($entryType)) {
            $errors = implode(', ', $entryType->getErrorSummary(true));
            throw new Exception("Unable to update entry type: {$errors}");
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Entry type \"{$entryType->name}\" has been updated.",
//            'response' => $config,
        ]);
    }

    /**
     * Delete an entry type by its handle.
     *
     * ALWAYS ASK FOR CONFIRMATION!! This is a very destructive action.
     *
     * Force the user to re-enter the entry type handle they are deleting.
     *
     * Make sure you understand the EXISTING entry types before deleting.
     * If needed, you MUST call `getAllEntryTypes` to see which entry types exist.
     *
     * @param string $handle Entry type to delete.
     * @return SkillResponse
     * @throws Exception
     * @throws Throwable
     */
    public static function deleteEntryType(string $handle): SkillResponse
    {
        // Get the entries service
        $entriesService = VersionHelper::sectionsService();

        // Attempt to find the entry type by its handle
        $entryType = $entriesService->getEntryTypeByHandle($handle);

        // If the entry type doesn't exist, throw an exception
        if (!$entryType) {
            throw new Exception("Entry type \"{$handle}\" not found.");
        }

        // If unable to delete the entry type, throw an exception
        if (!$entriesService->deleteEntryType($entryType)) {
            $errors = implode(', ', $entryType->getErrorSummary(true));
            throw new Exception("Unable to delete entry type: {$errors}");
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Entry type \"{$entryType->name}\" has been deleted.",
//            'response' => $config,
        ]);
    }
}

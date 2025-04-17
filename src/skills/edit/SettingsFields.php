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

namespace doublesecretagency\sidekick\skills\edit;

use Craft;
use craft\base\FieldInterface;
use craft\helpers\Json;
use craft\models\FieldLayout;
use doublesecretagency\sidekick\models\SkillResponse;
use Throwable;

/**
 * @category Fields
 */
class SettingsFields
{
    /**
     * Create a new field.
     *
     * Ensure you understand which field types are available before creating a new field.
     * It's recommended to call `getAvailableFieldTypes` first, if you haven't already.
     *
     * Craft 4 only: Must specify a `group` or `groupId` in the configuration.
     * Call `getAvailableFieldGroups` to get a list of available field groups in Craft 4.
     *
     * @param string $fieldType Type of the field (from list of available field types). If not specified, ask for clarification.
     * @param string $fieldConfig JSON-stringified configuration for the field.
     * @return SkillResponse
     */
    public static function createField(string $fieldType, string $fieldConfig): SkillResponse
    {
        // Attempt to create and save the field
        try {

            // Decode the JSON configuration
            $config = Json::decode($fieldConfig);

            // If config is invalid, return an error response
            if (!$config || !is_array($config)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Invalid field configuration.",
                ]);
            }

            // Create the field
            /** @var FieldInterface $fieldType */
            $field = new $fieldType($config);

            // If unable to save the field, return an error response
            if (!Craft::$app->getFields()->saveField($field)) {
                $errors = implode(', ', $field->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to create field: {$errors}",
                ]);
            }

        } catch (Throwable $e) {

            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to create the field. {$e->getMessage()}",
            ]);

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Field \"{$field->name}\" with handle \"{$field->handle}\" of type \"{$fieldType}\" has been created.",
//            'response' => $config,
        ]);
    }

    /**
     * Update an existing field with a new configuration.
     *
     * Make sure you understand the EXISTING field configuration before updating.
     * If needed, you MUST call `getFieldDetails` to get the current configuration.
     *
     * For large updates, ask for confirmation before proceeding.
     *
     * @param string $fieldHandle Handle of the field to update.
     * @param string $newConfig JSON-stringified configuration for the field.
     * @return SkillResponse
     */
    public static function updateField(string $fieldHandle, string $newConfig): SkillResponse
    {
        // Attempt to update the field
        try {

            // Get the field
            $field = Craft::$app->getFields()->getFieldByHandle($fieldHandle);

            // If field doesn't exist, return an error response
            if (!$field) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Unable to update, field `{$fieldHandle}` does not exist.",
                ]);
            }

            // Decode the JSON configuration
            $config = Json::decode($newConfig);

            // Merge the new configuration with the existing field
            $field->setAttributes($config, false);

//            // Update the settings as well
//            $field->setSettings($config);

            // If unable to save the field, return an error response
            if (!Craft::$app->getFields()->saveField($field)) {
                $errors = implode(', ', $field->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to update field: {$errors}",
                ]);
            }

        } catch (Throwable $e) {

            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to update the field. {$e->getMessage()}",
            ]);

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Field \"{$field->name}\" has been updated.",
//            'response' => $config,
        ]);
    }

    /**
     * Delete a field by its handle.
     *
     * ALWAYS ASK FOR CONFIRMATION!! This is a very destructive action.
     *
     * Force the user to re-enter the field handle they are deleting.
     *
     * @param string $handle Field to delete.
     * @return SkillResponse
     */
    public static function deleteField(string $handle): SkillResponse
    {
        // Get the fields service
        $fieldsService = Craft::$app->getFields();

        // Attempt to find the field by its handle
        $field = Craft::$app->getFields()->getFieldByHandle($handle);

        // If the field doesn't exist, return an error response
        if (!$field) {
            return new SkillResponse([
                'success' => false,
                'message' => "Field \"{$handle}\" not found.",
            ]);
        }

        // Attempt to delete the field
        try {
            // If unable to mark field for deletion, return an error response
            if (!$fieldsService->deleteField($field)) {
                $errors = implode(', ', $field->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to delete field: {$errors}",
                ]);
            }
            // Actually delete the field
            $fieldsService->applyFieldDelete($field->uid);
        } catch (Throwable $e) {
            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to delete the field. {$e->getMessage()}",
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Field \"{$handle}\" has been deleted.",
//            'response' => $config,
        ]);
    }

    // ========================================================================= //

    /**
     * Create a new field layout.
     *
     * @param string $fieldLayoutConfig JSON-stringified configuration for the field layout. See the "Field Layout Configs" instructions.
     * @return SkillResponse
     */
    public static function createFieldLayout(string $fieldLayoutConfig): SkillResponse
    {
        // Decode the JSON configuration
        $config = Json::decode($fieldLayoutConfig);

        // If the configuration was not valid JSON, return an error response
        if (!is_array($config)) {
            return new SkillResponse([
                'success' => false,
                'message' => "Invalid JSON provided for field layout configuration.",
            ]);
        }

        // Attempt to create and save the field layout
        try {

            // Create the field layout
            $layout = FieldLayout::createFromConfig($config);

            // If unable to save the field layout, return an error response
            if (!Craft::$app->getFields()->saveLayout($layout, false)) {
                $errors = implode(', ', $layout->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to create field layout: {$errors}",
                ]);
            }

        } catch (Throwable $e) {

            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to create the field layout. {$e->getMessage()}",
            ]);

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Field layout has been created with ID {$layout->id}.",
//            'response' => $config,
        ]);
    }

    /**
     * Update an existing field layout with a new configuration.
     *
     * @param string $fieldLayoutId ID of the field layout to identify.
     * @param string $newConfig JSON-stringified configuration for the field layout. See the "Field Layout Configs" instructions.
     * @return SkillResponse
     */
    public static function updateFieldLayout(string $fieldLayoutId, string $newConfig): SkillResponse
    {
        // Get the existing field layout by ID
        $existingLayout = Craft::$app->getFields()->getLayoutById($fieldLayoutId);

        // If the layout doesn't exist, return an error response
        if (!$existingLayout) {
            return new SkillResponse([
                'success' => false,
                'message' => "Field layout with ID \"{$fieldLayoutId}\" not found.",
            ]);
        }

        // Decode the JSON configuration
        $config = Json::decode($newConfig);

        // If the configuration was not valid JSON, return an error response
        if (!is_array($config)) {
            return new SkillResponse([
                'success' => false,
                'message' => "Invalid JSON provided for field layout configuration.",
            ]);
        }

        // Attempt to update and save the field layout
        try {

            // Create the field layout
            $layout = FieldLayout::createFromConfig($config);

            // Set the ID and type of the existing layout
            $layout->id   = $existingLayout->id;
            $layout->type = $existingLayout->type;
            $layout->uid  = $existingLayout->uid;

            // If unable to save the field layout, return an error response
            if (!Craft::$app->getFields()->saveLayout($layout, false)) {
                $errors = implode(', ', $layout->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to update field layout: {$errors}",
                ]);
            }

        } catch (Throwable $e) {

            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to update the field layout. {$e->getMessage()}",
            ]);

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Updated field layout {$layout->id}.",
//            'response' => $config,
        ]);
    }
}

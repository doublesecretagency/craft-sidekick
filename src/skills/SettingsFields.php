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
use craft\base\FieldInterface;
use craft\helpers\Json;
use craft\models\FieldGroup;
use doublesecretagency\sidekick\models\SkillResponse;
use Throwable;

class SettingsFields
{
    /**
     * Get a complete list of available field types.
     *
     * If you are considering creating a new field, you MUST call this tool first.
     *
     * @return SkillResponse
     */
    public static function getAvailableFieldTypes(): SkillResponse
    {
        // Get available field types
        $availableFieldTypes = Craft::$app->getFields()->getAllFieldTypes();

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Checked available field types.",
            'response' => Json::encode($availableFieldTypes)
        ]);
    }

    /**
     * Get the details of a particular field type.
     *
     * If you are considering creating a new field, you MUST call this tool first.
     * You will typically run this tool AFTER the `getAvailableFieldTypes` tool.
     *
     * @param string $fieldType The field type to get details for.
     * @return SkillResponse
     */
    public static function getFieldTypeDetails(string $fieldType): SkillResponse
    {
        /** @var FieldInterface $fieldType */

        try {

            // Get details about a specific field type
            $fieldTypeDetails = (new $fieldType())->getSettings();

        } catch (Throwable $e) {

            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to get details of the `{$fieldType}` field type. {$e->getMessage()}",
            ]);

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed details of the `{$fieldType}` field type.",
            'response' => Json::encode($fieldTypeDetails)
        ]);
    }

    // ========================================================================= //

    /**
     * Get a complete list of existing fields.
     *
     * If you are unfamiliar with the existing fields, you MUST call this tool before creating, reading, updating, or deleting fields.
     * Eagerly call this if an understanding of the current fields is required.
     *
     * You may also find it helpful to call this tool before updating an Entry.
     *
     * @return SkillResponse
     */
    public static function getAllExistingFields(): SkillResponse
    {
        // Initialize fields
        $fields = [];

        // Fetch all fields
        $allFields = Craft::$app->getFields()->getAllFields();

        // Loop through each field and format the output
        foreach ($allFields as $field) {
            $fields[] = self::_catalogField($field);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed the existing fields.",
            'response' => Json::encode($fields)
        ]);
    }

    /**
     * Get the details of a specific existing field.
     *
     * If you don't know which fields exist, you MUST call the `getAllExistingFields` tool instead.
     *
     * @param string $fieldHandle Handle of the field to get details for.
     * @return SkillResponse
     */
    public static function getFieldDetails(string $fieldHandle): SkillResponse
    {
        // Get available field types
        $field = Craft::$app->getFields()->getFieldByHandle($fieldHandle);

        // If the field doesn't exist, return an error response
        if (!$field) {
            return new SkillResponse([
                'success' => false,
                'message' => "Field `{$fieldHandle}` was not found.",
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Checked details of field `{$fieldHandle}`.",
            'response' => Json::encode(self::_catalogField($field))
        ]);
    }

    // ========================================================================= //

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
     * @param string $config JSON-stringified configuration for the field.
     * @return SkillResponse
     */
    public static function createField(string $fieldType, string $config): SkillResponse
    {
        // Attempt to create and save the field
        try {

            // Decode the JSON configuration
            $config = Json::decodeIfJson($config);

            // Create the field
            /** @var FieldInterface $fieldType */
            $field = new $fieldType($config);

            // If unable to save the field, return an error response
            if (!Craft::$app->getFields()->saveField($field)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to create field: " . implode(', ', $field->getErrorSummary(true)),
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
            $config = Json::decodeIfJson($newConfig);

            // Merge the new configuration with the existing field
            $field->setAttributes($config, false);

//            // Update the settings as well
//            $field->setSettings($config);

            // If unable to save the field, return an error response
            if (!Craft::$app->getFields()->saveField($field)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to update field: " . implode(', ', $field->getErrorSummary(true)),
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
            // If unable to delete the field, return an error response
            if (!Craft::$app->getFields()->deleteField($field)) {
                $errors = implode(', ', $field->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to delete field: {$errors}",
                ]);
            }
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
     * Get a complete list of available field groups.
     *
     * ONLY AVAILABLE IN CRAFT 4.
     *
     * @return SkillResponse
     */
    public static function getAvailableFieldGroups(): SkillResponse
    {
        // If running Craft 5 or later, return success message to keep things moving
        if (self::_isCraft5()) {
            return new SkillResponse([
                'success' => true,
                'message' => "Ignoring field groups, not relevant after Craft 4.",
            ]);
        }

        // Get all field groups
        $fieldGroups = Craft::$app->getFields()->getAllGroups();

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Checked available field groups.",
            'response' => Json::encode($fieldGroups)
        ]);
    }

    /**
     * Create a new field group.
     *
     * ONLY AVAILABLE IN CRAFT 4.
     *
     * @param string $name Name of the field group.
     * @param string $handle Handle of the field group.
     * @return SkillResponse
     */
    public static function createFieldGroup(string $name, string $handle): SkillResponse
    {
        // If running Craft 5 or later, return success message to keep things moving
        if (self::_isCraft5()) {
            return new SkillResponse([
                'success' => true,
                'message' => "Ignoring field groups, not relevant after Craft 4.",
            ]);
        }

        // Attempt to create the field group
        try {

            // Create the field group
            $fieldGroup = new FieldGroup([
                'name' => $name,
            ]);

            // If unable to save the field group, return an error response
            if (!Craft::$app->getFields()->saveGroup($fieldGroup)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to create field group: " . implode(', ', $fieldGroup->getErrorSummary(true)),
                ]);
            }

        } catch (Throwable $e) {

            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to create the field group. {$e->getMessage()}",
            ]);

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Field group \"{$name}\" has been created.",
//            'response' => $config,
        ]);
    }

    // ========================================================================= //

    /**
     * Catalog a field.
     *
     * @param FieldInterface $field
     * @return array
     */
    private static function _catalogField(FieldInterface $field): array
    {
        return [
            'ID' => $field->id,
            'Name' => $field->name,
            'Handle' => $field->handle,
            'Instructions' => $field->instructions,
            'Group ID' => ($field->groupId ?? null),
            'Context' => $field->context,
            'Settings' => $field->getSettings()
        ];
    }

    // ========================================================================= //

    /**
     * Whether we're running Craft 5 or later.
     *
     * @return bool
     */
    private static function _isCraft5(): bool
    {
        // Check if we're running Craft 5 or later
        return version_compare(Craft::$app->getVersion(), '5.0.0', '>=');
    }
}

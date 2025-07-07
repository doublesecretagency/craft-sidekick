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
use craft\models\FieldLayout;
use doublesecretagency\sidekick\helpers\FieldLayoutHelper;
use doublesecretagency\sidekick\helpers\SkillsHelper;
use doublesecretagency\sidekick\helpers\VersionHelper;
use doublesecretagency\sidekick\models\SkillResponse;
use Throwable;
use yii\base\Exception;

/**
 * @category Fields
 */
class Fields extends BaseSkillSet
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
            $restrictedMethods[] = 'createField';
            $restrictedMethods[] = 'updateField';
            $restrictedMethods[] = 'deleteField';
            $restrictedMethods[] = 'createFieldLayout';
            $restrictedMethods[] = 'updateFieldLayout';
            // Craft 4 only
            $restrictedMethods[] = 'createFieldGroup';
            $restrictedMethods[] = 'deleteFieldGroup';
        }

        // Methods unavailable after Craft 4
        if (!VersionHelper::craftBetween('4.0.0', '5.0.0')) {
            $restrictedMethods[] = 'getAllFieldGroups';
            $restrictedMethods[] = 'createFieldGroup';
            $restrictedMethods[] = 'deleteFieldGroup';
        }

        // Return list of restricted methods
        return $restrictedMethods;
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
     * Use this tool to get an overview of all fields in the system.
     * For details on a specific field, use the `getFieldDetails` tool afterward.
     *
     * @return SkillResponse
     */
    public static function getAllFields(): SkillResponse
    {
        // Fetch all fields
        $fields = Craft::$app->getFields()->getAllFields();

        // Initialize results array
        $results = [];

        // Loop over each field
        foreach ($fields as $field) {

            // Get the field data
            $fieldData = [
                'id'     => $field->id,
                'name'   => $field->name,
                'handle' => $field->handle,
                'type'   => $field::class,
                'uid'    => $field->uid,
            ];

            // If this is Craft 4
            if (VersionHelper::craftBetween('4.0.0', '5.0.0')) {
                // Include the group ID
                $fieldData['groupId'] = ($field->groupId ?? null);
            }

            // Append data to results
            $results[] = $fieldData;
        }

        // If no results
        if (!$results) {
            // Return success message with no results
            return new SkillResponse([
                'success' => true,
                'message' => "No fields found."
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed all existing fields.",
            'response' => SkillsHelper::toCsv($results)
        ]);
    }

    // ========================================================================= //

    /**
     * Get the details of a specific existing field.
     *
     * If you don't know which fields exist, you MUST call the `getAllFields` tool instead.
     *
     * @param string $fieldHandle Handle of the field to get details for.
     * @return SkillResponse
     * @throws Exception
     */
    public static function getFieldDetails(string $fieldHandle): SkillResponse
    {
        // Get available field types
        $field = Craft::$app->getFields()->getFieldByHandle($fieldHandle);

        // If the field doesn't exist, throw an exception
        if (!$field) {
            throw new Exception("Field `{$fieldHandle}` was not found.");
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Checked details of field `{$fieldHandle}`.",
            'response' => Json::encode($field)
        ]);
    }

    /**
     * Create a new field.
     *
     * Ensure you understand which field types are available before creating a new field.
     * It's recommended to call `getAllFieldTypes` first, if you haven't already.
     *
     * Craft 4 only: Must specify a `group` or `groupId` in the configuration.
     * Call `getAllFieldGroups` to get a list of available field groups in Craft 4.
     *
     * @param string $fieldType Type of the field (from list of available field types). If not specified, ask for clarification.
     * @param string $fieldConfig JSON-stringified configuration for the field.
     * @return SkillResponse
     * @throws Exception|Throwable
     */
    public static function createField(string $fieldType, string $fieldConfig): SkillResponse
    {
        // Decode the JSON configuration
        $config = Json::decode($fieldConfig);

        // If config is invalid, throw an exception
        if (!$config || !is_array($config)) {
            throw new Exception("Invalid field configuration.");
        }

        // Create the field
        /** @var FieldInterface $fieldType */
        $field = new $fieldType($config);

        // If unable to save the field, throw an exception
        if (!Craft::$app->getFields()->saveField($field)) {
            $errors = implode(', ', $field->getErrorSummary(true));
            throw new Exception("Unable to create field: {$errors}");
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
     * @throws Throwable
     */
    public static function updateField(string $fieldHandle, string $newConfig): SkillResponse
    {
        // Get the field
        $field = Craft::$app->getFields()->getFieldByHandle($fieldHandle);

        // If field doesn't exist, throw an exception
        if (!$field) {
            throw new Exception("Unable to update, field `{$fieldHandle}` does not exist.");
        }

        // Decode the JSON configuration
        $config = Json::decode($newConfig);

        // Merge the new configuration with the existing field
        $field->setAttributes($config, false);

//        // Update the settings as well
//        $field->setSettings($config);

        // If unable to save the field, throw an exception
        if (!Craft::$app->getFields()->saveField($field)) {
            $errors = implode(', ', $field->getErrorSummary(true));
            throw new Exception("Unable to update field: {$errors}");
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
     * @throws Throwable
     */
    public static function deleteField(string $handle): SkillResponse
    {
        // Get the fields service
        $fieldsService = Craft::$app->getFields();

        // Attempt to find the field by its handle
        $field = Craft::$app->getFields()->getFieldByHandle($handle);

        // If the field doesn't exist, throw an exception
        if (!$field) {
            throw new Exception("Field \"{$handle}\" not found.");
        }

        // If unable to mark field for deletion, throw an exception
        if (!$fieldsService->deleteField($field)) {
            $errors = implode(', ', $field->getErrorSummary(true));
            throw new Exception("Unable to delete field: {$errors}");
        }

        // Actually delete the field
        $fieldsService->applyFieldDelete($field->uid);

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Field \"{$field->name}\" has been deleted.",
//            'response' => $config,
        ]);
    }

    // ========================================================================= //

    /**
     * Get a complete list of available field groups.
     *
     * ONLY AVAILABLE IN CRAFT 4.
     *
     * If you are unfamiliar with the existing field groups, you MUST call this tool before creating, updating, or deleting field groups.
     * Eagerly call this if an understanding of the current field groups is required.
     *
     * @return SkillResponse
     */
    public static function getAllFieldGroups(): SkillResponse
    {
        // Get all field groups
        $fieldGroups = Craft::$app->getFields()->getAllGroups();

        // Initialize results array
        $results = [];

        // Loop over each field group
        foreach ($fieldGroups as $group) {
            // Append data to results
            $results[] = [
                'id'   => $group->id,
                'name' => $group->name,
                'uid'  => $group->uid,
            ];
        }

        // If no results
        if (!$results) {
            // Return success message with no results
            return new SkillResponse([
                'success' => true,
                'message' => "No field groups found."
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed the existing field groups.",
            'response' => SkillsHelper::toCsv($results)
        ]);
    }

    /**
     * Create a new field group.
     *
     * ONLY AVAILABLE IN CRAFT 4.
     *
     * @param string $name Name of the field group.
     * @return SkillResponse
     * @throws Exception
     */
    public static function createFieldGroup(string $name): SkillResponse
    {
        // Create the field group
        $fieldGroup = new FieldGroup([
            'name' => $name,
        ]);

        // If unable to save the field group, throw an exception
        if (!Craft::$app->getFields()->saveGroup($fieldGroup)) {
            $errors = implode(', ', $fieldGroup->getErrorSummary(true));
            throw new Exception("Unable to create field group: {$errors}");
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Field group \"{$name}\" has been created.",
//            'response' => $config,
        ]);
    }

    /**
     * Delete an existing field group.
     *
     * ONLY AVAILABLE IN CRAFT 4.
     *
     * If you do not have a clear understanding of which field groups exist, call the `getAllFieldGroups` skill first.
     *
     * @param string $groupId ID of the field group to be deleted.
     * @return SkillResponse
     * @throws Exception
     */
    public static function deleteFieldGroup(string $groupId): SkillResponse
    {
        // If group ID is not numeric, throw an exception
        if (!is_numeric($groupId)) {
            throw new Exception("Unable to delete field group, invalid ID: {$groupId}");
        }

        // Get the fields service
        $fields = Craft::$app->getFields();

        // Get the field group by ID
        $group = $fields->getGroupById($groupId);

        // If group does not exist, throw an exception
        if (!$group) {
            throw new Exception("Unable to delete, field group does not exist.");
        }

        // If unable to delete the field group, throw an exception
        if (!$fields->deleteGroup($group)) {
            throw new Exception("Unable to delete the field group \"{$group->name}\".");
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Field group \"{$group->name}\" has been deleted.",
//            'response' => $config,
        ]);
    }

    // ========================================================================= //

    /**
     * Get a complete list of available field types.
     *
     * If you are considering creating a new field, you MUST call this tool first.
     *
     * @return SkillResponse
     */
    public static function getAllFieldTypes(): SkillResponse
    {
        // Get available field types
        $availableFieldTypes = Craft::$app->getFields()->getAllFieldTypes();

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed the existing field types.",
            'response' => Json::encode($availableFieldTypes)
        ]);
    }

    /**
     * Get the details of a particular field type.
     *
     * If you are considering creating a new field, you MUST call this tool first.
     * You will typically run this tool AFTER the `getAllFieldTypes` tool.
     *
     * @param string $fieldType The field type to get details for.
     * @return SkillResponse
     */
    public static function getFieldTypeDetails(string $fieldType): SkillResponse
    {
        /** @var FieldInterface $fieldType */

        // Get details about a specific field type
        $fieldTypeDetails = (new $fieldType())->getSettings();

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed details of the `{$fieldType}` field type.",
            'response' => Json::encode($fieldTypeDetails)
        ]);
    }

    // ========================================================================= //

    /**
     * Get details of a specified field layout.
     *
     * To identify custom fields, you SHOULD also call `getAllFieldTypes` (if you haven't already).
     *
     * @param string $fieldLayoutId ID of the field layout to identify.
     * @return SkillResponse
     * @throws Exception
     */
    public static function getFieldLayout(string $fieldLayoutId): SkillResponse
    {
        // Get the field layout by ID
        $layout = Craft::$app->getFields()->getLayoutById($fieldLayoutId);

        // If the layout doesn't exist, throw an exception
        if (!$layout) {
            throw new Exception("Field layout with ID \"{$fieldLayoutId}\" not found.");
        }

        // Get a description of the field layout
        $layoutDescription = FieldLayoutHelper::getDescription($fieldLayoutId);

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed field layout {$layoutDescription}.",
            'response' => Json::encode($layout->getConfig())
        ]);
    }

    /**
     * Create a new field layout.
     *
     * See the "Field Layouts" instructions.
     *
     * @param string $fieldLayoutConfig JSON-stringified configuration for the field layout.
     * @return SkillResponse
     * @throws Exception
     */
    public static function createFieldLayout(string $fieldLayoutConfig): SkillResponse
    {
        // Decode the JSON configuration
        $config = Json::decode($fieldLayoutConfig);

        // If the configuration was not valid JSON, throw an exception
        if (!is_array($config)) {
            throw new Exception("Invalid JSON provided for field layout configuration.");
        }

        // Create the field layout
        $layout = FieldLayout::createFromConfig($config);

        // If unable to save the field layout, throw an exception
        if (!Craft::$app->getFields()->saveLayout($layout, false)) {
            $errors = implode(', ', $layout->getErrorSummary(true));
            throw new Exception("Unable to create field layout: {$errors}");
        }

        // Get a description of the field layout
        $layoutDescription = FieldLayoutHelper::getDescription($layout->id);

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Created field layout {$layoutDescription}.",
//            'response' => $config,
        ]);
    }

    /**
     * Update an existing field layout with a new configuration.
     *
     * See the "Field Layouts" instructions.
     *
     * @param string $fieldLayoutId ID of the field layout to identify.
     * @param string $newConfig JSON-stringified configuration for the field layout.
     * @return SkillResponse
     * @throws Exception
     */
    public static function updateFieldLayout(string $fieldLayoutId, string $newConfig): SkillResponse
    {
        // Get the existing field layout by ID
        $existingLayout = Craft::$app->getFields()->getLayoutById($fieldLayoutId);

        // If the layout doesn't exist, throw an exception
        if (!$existingLayout) {
            throw new Exception("Field layout with ID \"{$fieldLayoutId}\" not found.");
        }

        // Decode the JSON configuration
        $config = Json::decode($newConfig);

        // If the configuration was not valid JSON, throw an exception
        if (!is_array($config)) {
            throw new Exception("Invalid JSON provided for field layout configuration.");
        }

        // Create the field layout
        $layout = FieldLayout::createFromConfig($config);

        // Set the ID and type of the existing layout
        $layout->id   = $existingLayout->id;
        $layout->type = $existingLayout->type;
        $layout->uid  = $existingLayout->uid;

        // If unable to save the field layout, throw an exception
        if (!Craft::$app->getFields()->saveLayout($layout, false)) {
            $errors = implode(', ', $layout->getErrorSummary(true));
            throw new Exception("Unable to update field layout: {$errors}");
        }

        // Get a description of the field layout
        $layoutDescription = FieldLayoutHelper::getDescription($layout->id);

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Updated field layout {$layoutDescription}.",
//            'response' => $config,
        ]);
    }
}

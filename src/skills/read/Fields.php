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
use craft\base\FieldInterface;
use craft\helpers\Json;
use doublesecretagency\sidekick\models\SkillResponse;
use Throwable;

/**
 * @category Fields
 */
class Fields
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
            'message' => "Reviewed the existing field types.",
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
        // Fetch all fields
        $allFields = Craft::$app->getFields()->getAllFields();

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed the existing fields.",
            'response' => Json::encode($allFields)
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
            'response' => Json::encode($field)
        ]);
    }

    // ========================================================================= //

    /**
     * Get details of a specified field layout.
     *
     * To identify custom fields, you SHOULD also call `getAvailableFieldTypes` (if you haven't already).
     *
     * @param string $fieldLayoutId ID of the field layout to identify.
     * @return SkillResponse
     */
    public static function getFieldLayout(string $fieldLayoutId): SkillResponse
    {
        // Get the field layout by ID
        $layout = Craft::$app->getFields()->getLayoutById($fieldLayoutId);

        // If the layout doesn't exist, return an error response
        if (!$layout) {
            return new SkillResponse([
                'success' => false,
                'message' => "Field layout with ID \"{$fieldLayoutId}\" not found.",
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed field layout {$fieldLayoutId}.",
            'response' => Json::encode($layout->getConfig())
        ]);
    }
}

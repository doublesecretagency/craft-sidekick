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
use craft\models\FieldGroup;
use doublesecretagency\sidekick\models\SkillResponse;
use Throwable;

/**
 * @category Fields
 */
class SettingsFieldsCraft4
{
    /**
     * Create a new field group.
     *
     * ONLY AVAILABLE IN CRAFT 4.
     *
     * @param string $name Name of the field group.
     * @return SkillResponse
     */
    public static function createFieldGroup(string $name): SkillResponse
    {
        // Attempt to create the field group
        try {

            // Create the field group
            $fieldGroup = new FieldGroup([
                'name' => $name,
            ]);

            // If unable to save the field group, return an error response
            if (!Craft::$app->getFields()->saveGroup($fieldGroup)) {
                $errors = implode(', ', $fieldGroup->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to create field group: {$errors}",
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

    /**
     * Delete an existing field group.
     *
     * ONLY AVAILABLE IN CRAFT 4.
     *
     * If you do not have a clear understanding of which field groups exist, call the `getAvailableFieldGroups` skill first.
     *
     * @param string $groupId ID of the field group to be deleted.
     * @return SkillResponse
     */
    public static function deleteFieldGroup(string $groupId): SkillResponse
    {
        // Attempt to delete the field group
        try {

            // If group ID is not numeric, return an error response
            if (!is_numeric($groupId)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Unable to delete field group, invalid ID: {$groupId}",
                ]);
            }

            // Get the fields service
            $fields = Craft::$app->getFields();

            // Get the field group by ID
            $group = $fields->getGroupById($groupId);

            // If group does not exist, return an error response
            if (!$group) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Unable to delete, field group does not exist.",
                ]);
            }

            // If unable to delete the field group, return an error response
            if (!$fields->deleteGroup($group)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to delete the field group \"{$group->name}\".",
                ]);
            }

        } catch (Throwable $e) {

            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to delete the field group. {$e->getMessage()}",
            ]);

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Field group \"{$group->name}\" has been deleted.",
//            'response' => $config,
        ]);
    }
}

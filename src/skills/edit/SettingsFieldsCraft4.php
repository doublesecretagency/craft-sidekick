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

class SettingsFieldsCraft4
{
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
}

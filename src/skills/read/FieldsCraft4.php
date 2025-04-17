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
use craft\helpers\Json;
use doublesecretagency\sidekick\models\SkillResponse;

class FieldsCraft4
{
    /**
     * Get a complete list of available field groups.
     *
     * ONLY AVAILABLE IN CRAFT 4.
     *
     * @return SkillResponse
     */
    public static function getAvailableFieldGroups(): SkillResponse
    {
        // Get all field groups
        $fieldGroups = Craft::$app->getFields()->getAllGroups();

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed the existing field groups.",
            'response' => Json::encode($fieldGroups)
        ]);
    }
}

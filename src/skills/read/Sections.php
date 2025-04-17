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

/**
 * @category Sections
 */
class Sections
{
    /**
     * Get a complete list of existing sections.
     *
     * If you are unfamiliar with the existing sections, you MUST call this tool before creating, reading, updating, or deleting sections.
     * Eagerly call this if an understanding of the current sections is required.
     *
     * You may also find it helpful to call this tool before updating an Entry.
     *
     * @return SkillResponse
     */
    public static function getSections(): SkillResponse
    {
        // Initialize sections
        $sections = [];

        // Fetch all sections
        $allSections = Craft::$app->getSections()->getAllSections();

        // Loop through each section and format the output
        foreach ($allSections as $section) {

            // Initialize entry types
            $entryTypes = [];

            // Get the entry types for the section
            foreach ($section->getEntryTypes() as $entryType) {
                // Catalog each entry type
                $entryTypes[] = [
                    'ID' => $entryType->id,
                    'Name' => $entryType->name,
                    'Handle' => $entryType->handle,
                    'Field Layout' => $entryType->fieldLayoutId,
                ];
            }

            // Catalog each section
            $sections[] = [
                'ID' => $section->id,
                'Name' => $section->name,
                'Handle' => $section->handle,
                'Section Type' => $section->type,
                'Available Entry Types' => $entryTypes,
            ];

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed the existing sections.",
            'response' => Json::encode($sections)
        ]);
    }
}

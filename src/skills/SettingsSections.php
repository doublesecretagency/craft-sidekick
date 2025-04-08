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
use craft\helpers\Json;
use craft\models\FieldLayout;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use doublesecretagency\sidekick\models\SkillResponse;
use Throwable;

class SettingsSections
{
    /**
     * Valid section types.
     *
     * @var array
     */
    private const SECTION_TYPES = [
        'single' => Section::TYPE_SINGLE,
        'channel' => Section::TYPE_CHANNEL,
        'structure' => Section::TYPE_STRUCTURE,
    ];

    // ========================================================================= //

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

    // ========================================================================= //

    /**
     * Get details of a specified field layout.
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

    /**
     * Create a new field layout.
     *
     * @param string $config JSON-stringified configuration for the field layout.
     * @return SkillResponse
     */
    public static function createFieldLayout(string $config): SkillResponse
    {
        // Decode the JSON configuration
        $config = Json::decodeIfJson($config);

        // If the configuration is not valid JSON, return an error response
        if ($config === null) {
            return new SkillResponse([
                'success' => false,
                'message' => "Invalid JSON configuration provided.",
            ]);
        }

        // Attempt to create and save the field layout
        try {

            // Create the field layout
            $layout = FieldLayout::createFromConfig($config);

            // If unable to save the field layout, return an error response
            if (!Craft::$app->getFields()->saveLayout($layout, false)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to create field layout: " . implode(', ', $layout->getErrorSummary(true)),
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
     * @param string $newConfig JSON-stringified configuration for the field layout.
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
        $config = Json::decodeIfJson($newConfig);

        // If the configuration is not valid JSON, return an error response
        if ($config === null) {
            return new SkillResponse([
                'success' => false,
                'message' => "Invalid JSON configuration provided.",
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
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to update field layout: " . implode(', ', $layout->getErrorSummary(true)),
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

    // ========================================================================= //

    /**
     * Create a new section.
     *
     * @param string $name Name of the section to create.
     * @param string $handle Handle for the section. If not otherwise specified, use a camelCase version of the name.
     * @param string $sectionType Type of the section (must be 'single', 'channel', or 'structure'). If not specified, ask for clarification.
     * @return SkillResponse
     */
    public static function createSection(string $name, string $handle, string $sectionType): SkillResponse
    {
        // If the section type is not valid, return an error response
        if (!in_array($sectionType, self::SECTION_TYPES, true)) {
            return new SkillResponse([
                'success' => false,
                'message' => "Invalid section type: {$sectionType}. Valid types are: " . implode(', ', self::SECTION_TYPES),
            ]);
        }

        // Attempt to create and save the section
        try {

            // Create the section
            $section = new Section([
                'name' => $name,
                'handle' => $handle,
                'type' => $sectionType,
                'siteSettings' => [
                    new Section_SiteSettings([
                        'siteId' => Craft::$app->sites->getPrimarySite()->id,
                        'enabledByDefault' => true,
                        'hasUrls' => false,
//                        'uriFormat' => 'foo/{slug}',
//                        'template' => 'foo/_entry',
                    ]),
                ]
            ]);

            // If unable to save the section, return an error response
            if (!Craft::$app->getSections()->saveSection($section)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to create section: " . implode(', ', $section->getErrorSummary(true)),
                ]);
            }

        } catch (Throwable $e) {

            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to create the section. {$e->getMessage()}",
            ]);

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Section \"{$name}\" with handle \"{$handle}\" of type \"{$sectionType}\" has been created.",
//            'response' => $config,
        ]);
    }

    /**
     * Update an existing section with a new configuration.
     *
     * Make sure you understand the EXISTING section configuration before updating.
     * If needed, you MUST call `getSections` to get the current configuration.
     *
     * For large updates, ask for confirmation before proceeding.
     *
     * @param string $sectionHandle Handle of the section to update.
     * @param string $newConfig JSON-stringified configuration for the section.
     * @return SkillResponse
     */
    public static function updateSection(string $sectionHandle, string $newConfig): SkillResponse
    {
        // Attempt to update the section
        try {

            // Get the section
            $section = Craft::$app->getSections()->getSectionByHandle($sectionHandle);

            // If section doesn't exist, return an error response
            if (!$section) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Unable to update, section `{$sectionHandle}` does not exist.",
                ]);
            }

            // Decode the JSON configuration
            $config = Json::decodeIfJson($newConfig);

            // If the configuration is not valid JSON, return an error response
            if ($config === null) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Invalid JSON configuration provided.",
                ]);
            }

            // Update the section with the new configuration
            $section->name = ($config['name'] ?? $section->name);
            $section->handle = ($config['handle'] ?? $section->handle);
            $section->type = ($config['type'] ?? $section->type);
//            $section->siteSettings = ($config['siteSettings'] ?? $section->siteSettings);
//            $section->hasUrls = ($config['hasUrls'] ?? $section->hasUrls);
//            $section->uriFormat = ($config['uriFormat'] ?? $section->uriFormat);
//            $section->template = ($config['template'] ?? $section->template);
//            $section->maxLevels = ($config['maxLevels'] ?? $section->maxLevels);
//            $section->structureId = ($config['structureId'] ?? $section->structureId);
//            $section->propagationMethod = ($config['propagationMethod'] ?? $section->propagationMethod);
//            $section->propagationKeyFormat = ($config['propagationKeyFormat'] ?? $section->propagationKeyFormat);

            // If unable to save the section, return an error response
            if (!Craft::$app->getSections()->saveSection($section)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to update section: " . implode(', ', $section->getErrorSummary(true)),
                ]);
            }

        } catch (Throwable $e) {

            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to update the section. {$e->getMessage()}",
            ]);

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Section \"{$section->name}\" has been updated.",
//            'response' => $config,
        ]);
    }

    /**
     * Delete a section by its handle.
     *
     * ALWAYS ASK FOR CONFIRMATION!! This is a very destructive action.
     *
     * Force the user to re-enter the section handle they are deleting.
     *
     * @param string $handle Section to delete.
     * @return SkillResponse
     */
    public static function deleteSection(string $handle): SkillResponse
    {
        // Attempt to find the section by its handle
        $section = Craft::$app->getSections()->getSectionByHandle($handle);

        // If the section doesn't exist, return an error response
        if (!$section) {
            return new SkillResponse([
                'success' => false,
                'message' => "Section \"{$handle}\" not found.",
            ]);
        }

        // Attempt to delete the section
        try {
            // If unable to delete the section, return an error response
            if (!Craft::$app->getSections()->deleteSection($section)) {
                $errors = implode(', ', $section->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to delete section: {$errors}",
                ]);
            }
        } catch (Throwable $e) {
            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to delete the section. {$e->getMessage()}",
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Section \"{$handle}\" has been deleted.",
//            'response' => $config,
        ]);
    }
}

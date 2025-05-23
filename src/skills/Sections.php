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
use craft\models\Section;
use craft\models\Section_SiteSettings;
use doublesecretagency\sidekick\helpers\VersionHelper;
use doublesecretagency\sidekick\models\SkillResponse;
use Throwable;

/**
 * @category Sections
 */
class Sections extends BaseSkillSet
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
            $restrictedMethods[] = 'createSection';
            $restrictedMethods[] = 'updateSection';
            $restrictedMethods[] = 'deleteSection';
        }

        // Return list of restricted methods
        return $restrictedMethods;
    }

    // ========================================================================= //

    /**
     * Valid section types.
     *
     * @var array
     */
    public const SECTION_TYPES = [
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

        // Get all sections
        if (VersionHelper::craftBetween('4.0.0', '5.0.0')) {
            // Craft 4
            $allSections = Craft::$app->getSections()->getAllSections();
        } else {
            // Craft 5+
            $allSections = Craft::$app->getEntries()->getAllSections();
        }

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

    /**
     * Create a new section.
     *
     * @param string $sectionConfig JSON-stringified configuration for the `Section` model.
     * @param string $siteSettingsConfig JSON-stringified array of configurations, each for the `Section_SiteSettings` model.
     * @return SkillResponse
     */
    public static function createSection(string $sectionConfig, string $siteSettingsConfig): SkillResponse
    {
        // Attempt to create and save the section
        try {

            // Decode the JSON configurations
            $section      = Json::decode($sectionConfig);
            $siteSettings = Json::decode($siteSettingsConfig);

            // Get the section type
            $sectionType = ($section['type'] ?? null);

            // If the section type is not valid, return an error response
            if (!in_array($sectionType, self::SECTION_TYPES, true)) {
                $types = implode(', ', self::SECTION_TYPES);
                return new SkillResponse([
                    'success' => false,
                    'message' => "Invalid section type: {$sectionType}. Valid types are: {$types}",
                ]);
            }

            // Create the section
            $section = new Section($section);

            // Append site settings
            $section->setSiteSettings(array_map(
                static fn(array $config) => new Section_SiteSettings($config),
                $siteSettings
            ));

            // If the section is not valid, return an error response
            if (!$section->validate()) {
                $errors = implode(', ', $section->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Invalid section configuration: {$errors}",
                ]);
            }

            // If unable to save the section, return an error response
            if (!Craft::$app->getSections()->saveSection($section)) {
                $errors = implode(', ', $section->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to create section: {$errors}",
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
            'message' => "Section \"{$section['name']}\" with handle \"{$section['handle']}\" of type \"{$sectionType}\" has been created.",
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
            $config = Json::decode($newConfig);

            // If the configuration was not valid JSON, return an error response
            if (!is_array($config)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Invalid JSON provided for section configuration.",
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
                $errors = implode(', ', $section->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to update section: {$errors}",
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
        // Get the sections service
        $sectionsService = Craft::$app->getSections();

        // Attempt to find the section by its handle
        $section = $sectionsService->getSectionByHandle($handle);

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
            if (!$sectionsService->deleteSection($section)) {
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
            'message' => "Section \"{$section->name}\" has been deleted.",
//            'response' => $config,
        ]);
    }
}

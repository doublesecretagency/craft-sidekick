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
use craft\helpers\Json;
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
    public const SECTION_TYPES = [
        'single' => Section::TYPE_SINGLE,
        'channel' => Section::TYPE_CHANNEL,
        'structure' => Section::TYPE_STRUCTURE,
    ];

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

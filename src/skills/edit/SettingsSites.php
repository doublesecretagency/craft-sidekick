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
use craft\models\Site;
use craft\models\SiteGroup;
use doublesecretagency\sidekick\models\SkillResponse;
use Throwable;

class SettingsSites
{
    /**
     * Create a new site.
     *
     * When creating a new site, you MUST first check the existing sites and site groups.
     *
     * @param string $siteConfig JSON-stringified configuration for the `Site` model.
     * @return SkillResponse
     */
    public static function createSite(string $siteConfig): SkillResponse
    {
        // Decode the JSON configurations
        $site = Json::decodeIfJson($siteConfig);

        // Attempt to create and save the site
        try {

            // Create the site
            $site = new Site($site);

            // If unable to save the site, return an error response
            if (!Craft::$app->getSites()->saveSite($site)) {
                $errors = implode(', ', $site->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to create site: {$errors}",
                ]);
            }

        } catch (Throwable $e) {

            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to create the site. {$e->getMessage()}",
            ]);

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Site \"{$site->name}\" with handle \"{$site->handle}\" has been created.",
//            'response' => $config,
        ]);
    }

    /**
     * Update an existing site with a new configuration.
     *
     * Make sure you understand the EXISTING site configuration before updating.
     * If needed, you MUST call `getSites` to get the current configuration.
     *
     * For large updates, ask for confirmation before proceeding.
     *
     * @param string $siteHandle Handle of the site to update.
     * @param string $newConfig JSON-stringified configuration for the site.
     * @return SkillResponse
     */
    public static function updateSite(string $siteHandle, string $newConfig): SkillResponse
    {
        // Attempt to update the site
        try {

            // Get the site
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            // If site doesn't exist, return an error response
            if (!$site) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Unable to update, site `{$siteHandle}` does not exist.",
                ]);
            }

            // Decode the JSON configuration
            $config = Json::decodeIfJson($newConfig);

            // If the configuration was not valid JSON, return an error response
            if (!is_array($config)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Invalid JSON provided for site configuration.",
                ]);
            }

            // Update the site with the new configuration
            $site->name = ($config['name'] ?? $site->name);
            $site->handle = ($config['handle'] ?? $site->handle);
            $site->type = ($config['type'] ?? $site->type);
//            $site->siteSettings = ($config['siteSettings'] ?? $site->siteSettings);
//            $site->hasUrls = ($config['hasUrls'] ?? $site->hasUrls);
//            $site->uriFormat = ($config['uriFormat'] ?? $site->uriFormat);
//            $site->template = ($config['template'] ?? $site->template);
//            $site->maxLevels = ($config['maxLevels'] ?? $site->maxLevels);
//            $site->structureId = ($config['structureId'] ?? $site->structureId);
//            $site->propagationMethod = ($config['propagationMethod'] ?? $site->propagationMethod);
//            $site->propagationKeyFormat = ($config['propagationKeyFormat'] ?? $site->propagationKeyFormat);

            // If unable to save the site, return an error response
            if (!Craft::$app->getSites()->saveSite($site)) {
                $errors = implode(', ', $site->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to update site: {$errors}",
                ]);
            }

        } catch (Throwable $e) {

            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to update the site. {$e->getMessage()}",
            ]);

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Site \"{$site->name}\" has been updated.",
//            'response' => $config,
        ]);
    }

    /**
     * Delete a site by its handle.
     *
     * ALWAYS ASK FOR CONFIRMATION!! This is a very destructive action.
     *
     * Force the user to re-enter the site handle they are deleting.
     *
     * @param string $handle Site to delete.
     * @return SkillResponse
     */
    public static function deleteSite(string $handle): SkillResponse
    {
        // Get the sites service
        $sitesService = Craft::$app->getSites();

        // Attempt to find the site by its handle
        $site = $sitesService->getSiteByHandle($handle);

        // If the site doesn't exist, return an error response
        if (!$site) {
            return new SkillResponse([
                'success' => false,
                'message' => "Site \"{$handle}\" not found.",
            ]);
        }

        // Attempt to delete the site
        try {
            // If unable to delete the site, return an error response
            if (!$sitesService->deleteSite($site)) {
                $errors = implode(', ', $site->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to delete site: {$errors}",
                ]);
            }
        } catch (Throwable $e) {
            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to delete the site. {$e->getMessage()}",
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Site \"{$handle}\" has been deleted.",
//            'response' => $config,
        ]);
    }

    // ========================================================================= //

    /**
     * Create a new site group.
     *
     * @param string $siteGroupConfig JSON-stringified configuration for the `SiteGroup` model.
     * @return SkillResponse
     */
    public static function createSiteGroup(string $siteGroupConfig): SkillResponse
    {
        // Decode the JSON configurations
        $siteGroup = Json::decodeIfJson($siteGroupConfig);

        // Attempt to create and save the site group
        try {

            // Create the site
            $group = new SiteGroup($siteGroup);

            // If unable to save the site group, return an error response
            if (!Craft::$app->getSites()->saveGroup($group)) {
                $errors = implode(', ', $group->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to create site group: {$errors}",
                ]);
            }

        } catch (Throwable $e) {

            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to create the site group. {$e->getMessage()}",
            ]);

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Site group \"{$group->getName()}\" has been created.",
//            'response' => $config,
        ]);
    }

    /**
     * Update an existing site group with a new configuration.
     *
     * Make sure you understand the EXISTING site group configuration before updating.
     * If needed, you MUST call `getSiteGroups` to get the current configuration.
     *
     * For large updates, ask for confirmation before proceeding.
     *
     * @param string $siteGroupId ID of the site group to update.
     * @param string $newConfig JSON-stringified configuration for the site.
     * @return SkillResponse
     */
    public static function updateSiteGroup(string $siteGroupId, string $newConfig): SkillResponse
    {
        // Attempt to update the site group
        try {

            // Get the site group
            $group = Craft::$app->getSites()->getGroupById($siteGroupId);

            // If site group doesn't exist, return an error response
            if (!$group) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Unable to update, site group {$siteGroupId} does not exist.",
                ]);
            }

            // Decode the JSON configuration
            $config = Json::decodeIfJson($newConfig);

            // If the configuration was not valid JSON, return an error response
            if (!is_array($config)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Invalid JSON provided for site configuration.",
                ]);
            }

            // Update the site group with the new configuration
            $group->name = ($config['name'] ?? $group->name);

            // If unable to save the site group, return an error response
            if (!Craft::$app->getSites()->saveGroup($group)) {
                $errors = implode(', ', $group->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to update site group: {$errors}",
                ]);
            }

        } catch (Throwable $e) {

            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to update the site group. {$e->getMessage()}",
            ]);

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Site group \"{$group->getName()}\" has been updated.",
//            'response' => $config,
        ]);
    }

    /**
     * Delete a site group by its ID.
     *
     * Site groups can only be deleted if they do not have any sites assigned to them.
     *
     * ALWAYS ASK FOR CONFIRMATION!! This is a very destructive action.
     *
     * Force the user to re-enter the group name they are deleting.
     *
     * @param string $groupId ID of site group to delete.
     * @return SkillResponse
     */
    public static function deleteSiteGroupById(string $groupId): SkillResponse
    {
        // Get the sites service
        $sitesService = Craft::$app->getSites();

        // Attempt to find the site group by its ID
        $group = $sitesService->getGroupById($groupId);

        // If the site doesn't exist, return an error response
        if (!$group) {
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to find site group with ID {$groupId}.",
            ]);
        }

        // If the site group still has sites, return an error response
        if ($sitesService->getSitesByGroupId($groupId)) {
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to delete a site group which still has sites assigned to it.",
            ]);
        }

        // Attempt to delete the site group
        try {
            // If unable to delete the site, return an error response
            if (!$sitesService->deleteGroup($group)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to delete site group \"{$group->getName()}\".",
                ]);
            }
        } catch (Throwable $e) {
            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to delete the site group. {$e->getMessage()}",
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Site group \"{$group->getName()}\" has been deleted.",
//            'response' => $config,
        ]);
    }
}

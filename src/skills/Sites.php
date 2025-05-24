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
use craft\models\Site;
use craft\models\SiteGroup;
use doublesecretagency\sidekick\models\SkillResponse;
use Throwable;

/**
 * @category Sites
 */
class Sites extends BaseSkillSet
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
            $restrictedMethods[] = 'createSite';
            $restrictedMethods[] = 'updateSite';
            $restrictedMethods[] = 'deleteSite';
            $restrictedMethods[] = 'createSiteGroup';
            $restrictedMethods[] = 'updateSiteGroup';
            $restrictedMethods[] = 'deleteSiteGroup';
        }

        // Return list of restricted methods
        return $restrictedMethods;
    }

    // ========================================================================= //

    /**
     * Get a complete list of existing sites.
     *
     * If you are unfamiliar with the existing sites, you MUST call this tool before creating, reading, updating, or deleting sites.
     * Eagerly call this if an understanding of the current sites is required.
     *
     * @return SkillResponse
     */
    public static function getAllSites(): SkillResponse
    {
        // Initialize sites
        $sites = [];

        // Fetch all sites
        $allSites = Craft::$app->getSites()->getAllSites();

        // Loop through each site and format the output
        foreach ($allSites as $site) {

            // Catalog each site
            $sites[] = [
                'ID' => $site->id,
                'UID' => $site->uid,
                'Group ID' => $site->groupId,
                'Name' => $site->getName(),
                'Handle' => $site->handle,
                'Language' => $site->language,
                'Locale' => $site->getLocale(),
                'Primary' => $site->primary,
                'Has URLs' => $site->hasUrls,
                'Base URL' => $site->getBaseUrl(),
                'Sort Order' => $site->sortOrder,
                'Date Created' => $site->dateCreated,
                'Date Updated' => $site->dateUpdated,
                'isEnabled' => $site->getEnabled(),
            ];

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed the existing sites.",
            'response' => Json::encode($sites)
        ]);
    }

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
        // Attempt to create and save the site
        try {

            // Create the site
            $site = new Site(
                Json::decode($siteConfig)
            );

            // If the site is not valid, return an error response
            if (!$site->validate()) {
                $errors = implode(', ', $site->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Invalid site configuration: {$errors}",
                ]);
            }

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
     * If needed, you MUST call `getAllSites` to get the current configuration.
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
            $config = Json::decode($newConfig);

            // If the configuration was not valid JSON, return an error response
            if (!is_array($config)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Invalid JSON provided for site configuration.",
                ]);
            }

            // Update the site with the new configuration
            $site->groupId = ($config['groupId'] ?? $site->groupId);
            $site->name = ($config['name'] ?? $site->name);
            $site->handle = ($config['handle'] ?? $site->handle);
            $site->language = ($config['language'] ?? $site->language);
            $site->primary = ($config['primary'] ?? $site->primary);
            $site->baseUrl = ($config['baseUrl'] ?? $site->baseUrl);
            $site->enabled = ($config['enabled'] ?? $site->enabled);

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
            'message' => "Site \"{$site->name}\" has been deleted.",
//            'response' => $config,
        ]);
    }

    // ========================================================================= //

    /**
     * Get a complete list of existing site groups.
     *
     * If you are unfamiliar with the existing site groups, you MUST call this tool before creating, reading, updating, or deleting site groups.
     * Eagerly call this if an understanding of the current site groups are required.
     *
     * Feel free to also call `getAllSites` for more information about the sites in each group.
     *
     * @return SkillResponse
     */
    public static function getAllSiteGroups(): SkillResponse
    {
        // Initialize site groups
        $groups = [];

        // Fetch all site groups
        $allGroups = Craft::$app->getSites()->getAllGroups();

        // Loop through each group and format the output
        foreach ($allGroups as $group) {

            // Catalog each group
            $groups[] = [
                'ID' => $group->id,
                'UID' => $group->uid,
                'Name' => $group->getName(),
                'Contains Sites' => $group->getSiteIds(),
            ];

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed the existing site groups.",
            'response' => Json::encode($groups)
        ]);
    }

    /**
     * Create a new site group.
     *
     * @param string $siteGroupConfig JSON-stringified configuration for the `SiteGroup` model.
     * @return SkillResponse
     */
    public static function createSiteGroup(string $siteGroupConfig): SkillResponse
    {
        // Attempt to create and save the site group
        try {

            // Decode the JSON configurations
            $siteGroup = Json::decode($siteGroupConfig);

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
     * If needed, you MUST call `getAllSiteGroups` to get the current configuration.
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
            $config = Json::decode($newConfig);

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
    public static function deleteSiteGroup(string $groupId): SkillResponse
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
            'message' => "Site group \"{$group->name}\" has been deleted.",
//            'response' => $config,
        ]);
    }
}

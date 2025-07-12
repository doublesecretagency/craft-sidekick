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
use craft\errors\SiteNotFoundException;
use craft\helpers\Json;
use craft\models\Site;
use craft\models\SiteGroup;
use doublesecretagency\sidekick\helpers\SkillsHelper;
use doublesecretagency\sidekick\models\SkillResponse;
use Throwable;
use yii\base\Exception;

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
        // Get all sites
        $allSites = Craft::$app->getSites()->getAllSites();

        // Initialize results array
        $results = [];

        // Loop through each site
        foreach ($allSites as $site) {
            // Append data to results
            $results[] = [
                'id'        => $site->id,
                'uid'       => $site->uid,
                'groupId'   => $site->groupId,
                'name'      => $site->getName(),
                'handle'    => $site->handle,
                'language'  => $site->language,
                'locale'    => $site->getLocale()->id,
                'primary'   => $site->primary,
                'hasUrls'   => $site->hasUrls,
                'baseUrl'   => $site->getBaseUrl(),
                'sortOrder' => $site->sortOrder,
                'isEnabled' => $site->getEnabled(),
            ];
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed the existing sites.",
            'response' => SkillsHelper::toCsv($results)
        ]);
    }

    /**
     * Create a new site.
     *
     * When creating a new site, you MUST first check the existing sites and site groups.
     *
     * @param string $siteConfig JSON-stringified configuration for the `Site` model.
     * @return SkillResponse
     * @throws Exception
     * @throws Throwable
     * @throws SiteNotFoundException
     */
    public static function createSite(string $siteConfig): SkillResponse
    {
        // Create the site
        $site = new Site(
            Json::decode($siteConfig)
        );

        // If the site is not valid, throw an exception
        if (!$site->validate()) {
            $errors = implode(', ', $site->getErrorSummary(true));
            throw new Exception("Invalid site configuration: {$errors}");
        }

        // If unable to save the site, throw an exception
        if (!Craft::$app->getSites()->saveSite($site)) {
            $errors = implode(', ', $site->getErrorSummary(true));
            throw new Exception("Unable to create site: {$errors}");
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Site created > {$site->name} (`{$site->handle}`)",
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
     * @throws Exception
     * @throws SiteNotFoundException
     * @throws Throwable
     */
    public static function updateSite(string $siteHandle, string $newConfig): SkillResponse
    {
        // Get the site
        $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

        // If site doesn't exist, throw an exception
        if (!$site) {
            throw new Exception("Unable to update, site `{$siteHandle}` does not exist.");
        }

        // Decode the JSON configuration
        $config = Json::decode($newConfig);

        // If the configuration was not valid JSON, throw an exception
        if (!is_array($config)) {
            throw new Exception("Invalid JSON provided for site configuration.");
        }

        // Update the site with the new configuration
        $site->groupId = ($config['groupId'] ?? $site->groupId);
        $site->name = ($config['name'] ?? $site->name);
        $site->handle = ($config['handle'] ?? $site->handle);
        $site->language = ($config['language'] ?? $site->language);
        $site->primary = ($config['primary'] ?? $site->primary);
        $site->baseUrl = ($config['baseUrl'] ?? $site->baseUrl);
        $site->enabled = ($config['enabled'] ?? $site->enabled);

        // If unable to save the site, throw an exception
        if (!Craft::$app->getSites()->saveSite($site)) {
            $errors = implode(', ', $site->getErrorSummary(true));
            throw new Exception("Unable to update site: {$errors}");
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Site updated > {$site->name}",
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
     * @throws Exception
     * @throws Throwable
     */
    public static function deleteSite(string $handle): SkillResponse
    {
        // Get the sites service
        $sitesService = Craft::$app->getSites();

        // Attempt to find the site by its handle
        $site = $sitesService->getSiteByHandle($handle);

        // If the site doesn't exist, throw an exception
        if (!$site) {
            throw new Exception("Site \"{$handle}\" not found.");
        }

        // If unable to delete the site, throw an exception
        if (!$sitesService->deleteSite($site)) {
            $errors = implode(', ', $site->getErrorSummary(true));
            throw new Exception("Unable to delete site: {$errors}");
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Site deleted > {$site->name}",
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
        // Get all site groups
        $siteGroups = Craft::$app->getSites()->getAllGroups();

        // Initialize results array
        $results = [];

        // Loop over each site group
        foreach ($siteGroups as $group) {

            // Identify which sites are in the group
            $siteIds = implode(',', $group->getSiteIds());

            // Append data to results
            $results[] = [
                'id'      => $group->id,
                'name'    => $group->getName(),
                'uid'     => $group->uid,
                'siteIds' => $siteIds,
            ];

        }

        // If no results
        if (!$results) {
            // Return success message with no results
            return new SkillResponse([
                'success' => true,
                'message' => "No site groups found."
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed the existing site groups.",
            'response' => SkillsHelper::toCsv($results)
        ]);
    }

    /**
     * Create a new site group.
     *
     * @param string $siteGroupConfig JSON-stringified configuration for the `SiteGroup` model.
     * @return SkillResponse
     * @throws Exception
     */
    public static function createSiteGroup(string $siteGroupConfig): SkillResponse
    {
        // Decode the JSON configurations
        $siteGroup = Json::decode($siteGroupConfig);

        // Create the site
        $group = new SiteGroup($siteGroup);

        // If unable to save the site group, throw an exception
        if (!Craft::$app->getSites()->saveGroup($group)) {
            $errors = implode(', ', $group->getErrorSummary(true));
            throw new Exception("Unable to create site group: {$errors}");
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Site group created > {$group->getName()}",
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
     * @throws Exception
     */
    public static function updateSiteGroup(string $siteGroupId, string $newConfig): SkillResponse
    {
        // Get the site group
        $group = Craft::$app->getSites()->getGroupById($siteGroupId);

        // If site group doesn't exist, throw an exception
        if (!$group) {
            throw new Exception("Unable to update, site group {$siteGroupId} does not exist.");
        }

        // Decode the JSON configuration
        $config = Json::decode($newConfig);

        // If the configuration was not valid JSON, throw an exception
        if (!is_array($config)) {
            throw new Exception("Invalid JSON provided for site configuration.");
        }

        // Update the site group with the new configuration
        $group->name = ($config['name'] ?? $group->name);

        // If unable to save the site group, throw an exception
        if (!Craft::$app->getSites()->saveGroup($group)) {
            $errors = implode(', ', $group->getErrorSummary(true));
            throw new Exception("Unable to update site group: {$errors}");
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Site group updated > {$group->getName()}",
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
     * @throws Exception
     */
    public static function deleteSiteGroup(string $groupId): SkillResponse
    {
        // Get the sites service
        $sitesService = Craft::$app->getSites();

        // Attempt to find the site group by its ID
        $group = $sitesService->getGroupById($groupId);

        // If the site doesn't exist, throw an exception
        if (!$group) {
            throw new Exception("Unable to find site group with ID {$groupId}.");
        }

        // If the site group still has sites, throw an exception
        if ($sitesService->getSitesByGroupId($groupId)) {
            throw new Exception("Unable to delete a site group which still has sites assigned to it.");
        }

        // If unable to delete the site, throw an exception
        if (!$sitesService->deleteGroup($group)) {
            throw new Exception("Unable to delete site group \"{$group->getName()}\".");
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Site group deleted > {$group->getName()}",
//            'response' => $config,
        ]);
    }
}

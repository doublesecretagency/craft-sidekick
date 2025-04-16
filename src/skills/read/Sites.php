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

class Sites
{
    /**
     * Get a complete list of existing sites.
     *
     * If you are unfamiliar with the existing sites, you MUST call this tool before creating, reading, updating, or deleting sites.
     * Eagerly call this if an understanding of the current sites is required.
     *
     * @return SkillResponse
     */
    public static function getSites(): SkillResponse
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

    // ========================================================================= //

    /**
     * Get a complete list of existing site groups.
     *
     * If you are unfamiliar with the existing site groups, you MUST call this tool before creating, reading, updating, or deleting site groups.
     * Eagerly call this if an understanding of the current site groups are required.
     *
     * Feel free to also call `getSites` for more information about the sites in each group.
     *
     * @return SkillResponse
     */
    public static function getSiteGroups(): SkillResponse
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
}

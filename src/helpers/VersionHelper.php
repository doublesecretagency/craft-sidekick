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

namespace doublesecretagency\sidekick\helpers;

use Craft;

class VersionHelper
{
    /**
     * Whether Craft is between the two specified versions.
     *
     * @param string $low The lower version bound.
     * @param string $high The upper version bound.
     * @return bool
     */
    public static function craftBetween(string $low, string $high): bool
    {
        // Get the Craft version
        $v = Craft::$app->getVersion();

        // Whether Craft is between the specified versions
        return (
            version_compare($v, $low, '>=') &&
            version_compare($v, $high, '<')
        );
    }

    /**
     * Get version-specific service for handling Sections.
     *
     * @return object
     */
    public static function sectionsService(): object
    {
        // If this is Craft 4
        if (self::craftBetween('4.0.0', '5.0.0')) {
            // Craft 4
            return Craft::$app->getSections();
        }

        // Craft 5+
        return Craft::$app->getEntries();
    }
}

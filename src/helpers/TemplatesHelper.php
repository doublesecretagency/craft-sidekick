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

class TemplatesHelper
{
    /**
     * Parse the templates path.
     *
     * @param string $path
     * @return string
     */
    public static function parseTemplatesPath(string $path): string
    {
        // Trim leading slashes from the path
        $path = ltrim($path, '/');

        // Replace the `templates` prefix with the actual path
        return preg_replace(
            '/^templates/',
            Craft::getAlias('@templates'),
            $path
        );
    }
}

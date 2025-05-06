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

namespace doublesecretagency\sidekick\utilities;

use Craft;
use craft\base\Utility;
use doublesecretagency\sidekick\helpers\SkillsHelper;
use doublesecretagency\sidekick\Sidekick;

class ChatWindowUtility extends Utility
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        // Get the settings
        $settings = Sidekick::getInstance()?->getSettings();

        // Return the display name
        return ($settings->overrideLinkName ?? 'Sidekick');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'sidekick-chat-window';
    }

    /**
     * @inheritdoc
     * @version Craft 5+
     */
    public static function icon(): ?string
    {
        // Set the icon mask path
        $iconPath = Craft::getAlias('@vendor/doublesecretagency/craft-sidekick/src/icon-mask.svg');

        // If not a string, bail
        if (!is_string($iconPath)) {
            return null;
        }

        // Return the icon mask path
        return $iconPath;
    }

    /**
     * @inheritdoc
     * @version Craft 4
     */
    public static function iconPath(): ?string
    {
        // Compatible with Craft 4
        return self::icon();
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        // Render as a utility
        return Craft::$app->getView()->renderTemplate('sidekick/chat/utility', [
            'skillSets' => SkillsHelper::slideoutSkillsList(),
        ]);
    }
}

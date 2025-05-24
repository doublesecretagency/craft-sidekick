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
use craft\db\Query;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\Tag;
use craft\elements\User;
use craft\models\EntryType;
use craft\models\FieldLayout;

class FieldLayoutHelper
{
    /**
     * Cached descriptions of field layouts.
     *
     * @var array
     */
    private static array $descriptions = [];

    // ========================================================================= //

    /**
     * Get a description of the field layout based on its ID.
     *
     * @param int $layoutId
     * @return string
     */
    public static function getDescription(int $layoutId): string
    {
        // If the description is already cached, return it
        if (isset(self::$descriptions[$layoutId])) {
            return self::$descriptions[$layoutId];
        }

        // Get the field layout by ID
        $layout = Craft::$app->getFields()->getLayoutById($layoutId);

        // If the layout doesn't exist
        if (!$layout) {
            return "which does not exist ({$layoutId})";
        }

        // Match based on the layout type
        $description = match ($layout->type) {
            Entry::class     => self::_entryDescription($layout),
            Category::class  => self::_categoryDescription($layout),
            Tag::class       => self::_tagDescription($layout),
//            Asset::class     => self::_assetDescription($layout),
//            User::class      => self::_userDescription($layout),
//            GlobalSet::class => self::_globalSetDescription($layout),
            default => "of an unknown field layout type ({$layoutId}: {$layout->type})",
        };

        // Cache the description for future use
        self::$descriptions[$layoutId] = $description;

        // Return the description
        return $description;
    }

    // ========================================================================= //

    /**
     * Get a description of the entry field layout.
     *
     * @param FieldLayout $layout
     * @return string
     */
    private static function _entryDescription(FieldLayout $layout): string
    {
        // Fetch matching entry types via a quick DB query
        $entryTypes = (new Query())
            ->select(['name','sectionId'])
            ->from('{{%entrytypes}}')
            ->where(['fieldLayoutId' => $layout->id])
            ->all();

        // If no matching entry types are found
        if (!$entryTypes) {
            return "with no matching entry type ({$layout->id})";
        }

        /** @var EntryType $entryType */
        $entryType = $entryTypes[0];

        // Grab the section model
        $section = Craft::$app->getSections()->getSectionById($entryType['sectionId']);

        // If the section doesn't exist
        if (!$section) {
            return "of the {$entryType['name']} entry type with no matching section ({$entryType['sectionId']})";
        }

        // Return the complete description
        return "of the {$entryType['name']} entry type in section \"{$section->name}\"";
    }

    /**
     * Get a description of the category field layout.
     *
     * @param FieldLayout $layout
     * @return string
     */
    private static function _categoryDescription(FieldLayout $layout): string
    {
        // Fetch matching category groups via a quick DB query
        $categoryGroups = (new Query())
            ->select(['name'])
            ->from('{{%categorygroups}}')
            ->where(['fieldLayoutId' => $layout->id])
            ->all();

        // If no matching category groups are found
        if (!$categoryGroups) {
            return "with no matching category group ({$layout->id})";
        }

        // Grab the first category group name
        $groupName = $categoryGroups[0]['name'];

        // Return the complete description
        return "of the \"{$groupName}\" category group";
    }

    /**
     * Get a description of the tag field layout.
     *
     * @param FieldLayout $layout
     * @return string
     */
    private static function _tagDescription(FieldLayout $layout): string
    {
        // Fetch matching tag groups via a quick DB query
        $tagGroups = (new Query())
            ->select(['name'])
            ->from('{{%taggroups}}')
            ->where(['fieldLayoutId' => $layout->id])
            ->all();

        // If no matching tag groups are found
        if (!$tagGroups) {
            return "with no matching tag group ({$layout->id})";
        }

        // Grab the first tag group name
        $groupName = $tagGroups[0]['name'];

        // Return the complete description
        return "of the \"{$groupName}\" tag group";
    }

    /**
     * Get a description of the asset field layout.
     *
     * @param FieldLayout $layout
     * @return string
     */
    private static function _assetDescription(FieldLayout $layout): string
    {
        // Fetch matching volumes via a quick DB query
        $volumes = (new Query())
            ->select(['name'])
            ->from('{{%volumes}}')
            ->where(['fieldLayoutId' => $layout->id])
            ->all();

        // If no matching volumes are found
        if (!$volumes) {
            return "with no matching volume ({$layout->id})";
        }

        // Grab the first volume name
        $volumeName = $volumes[0]['name'];

        // Return the complete description
        return "of the \"{$volumeName}\" volume";
    }

    /**
     * Get a description of the user field layout.
     *
     * @param FieldLayout $layout
     * @return string
     */
    private static function _userDescription(FieldLayout $layout): string
    {
        // Fetch matching user groups via a quick DB query
        $userGroups = (new Query())
            ->select(['name'])
            ->from('{{%usergroups}}')
            ->where(['fieldLayoutId' => $layout->id])
            ->all();

        // If no matching user groups are found
        if (!$userGroups) {
            return "with no matching user group ({$layout->id})";
        }

        // Grab the first user group name
        $groupName = $userGroups[0]['name'];

        // Return the complete description
        return "of the \"{$groupName}\" user group";
    }

    /**
     * Get a description of the global set field layout.
     *
     * @param FieldLayout $layout
     * @return string
     */
    private static function _globalSetDescription(FieldLayout $layout): string
    {
        // Fetch matching global sets via a quick DB query
        $globalSets = (new Query())
            ->select(['name'])
            ->from('{{%globals}}')
            ->where(['fieldLayoutId' => $layout->id])
            ->all();

        // If no matching global sets are found
        if (!$globalSets) {
            return "with no matching global set ({$layout->id})";
        }

        // Grab the first global set name
        $setName = $globalSets[0]['name'];

        // Return the complete description
        return "of the \"{$setName}\" global set";
    }
}

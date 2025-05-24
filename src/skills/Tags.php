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
use craft\elements\Tag;
use craft\helpers\Json;
use craft\models\TagGroup;
use doublesecretagency\sidekick\helpers\ElementsHelper;
use doublesecretagency\sidekick\models\SkillResponse;
use Throwable;
use yii\base\Exception;

/**
 * @category Tags
 */
class Tags extends BaseSkillSet
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
            $restrictedMethods[] = 'createTagGroup';
            $restrictedMethods[] = 'updateTagGroup';
            $restrictedMethods[] = 'deleteTagGroup';
        }

        // Return list of restricted methods
        return $restrictedMethods;
    }

    // ========================================================================= //

    /**
     * Get basic information (id, title, slug) about all tags.
     *
     * Optionally specify a group handle to filter the results.
     *
     * @param string $groupHandle Optional handle of the group to filter by. Set to empty string to get all tags.
     * @return SkillResponse
     */
    public static function getAllTags(string $groupHandle): SkillResponse
    {
        // Initialize the query
        $query = Tag::find()->select(['id', 'title', 'slug']);

        // If a group handle is provided
        if ($groupHandle) {
            // Filter the query by that group
            $query->group($groupHandle);
        }

        // Get all tags
        $tags = $query->all();

        // Initialize results array
        $results = [];

        // Loop over each tag
        foreach ($tags as $tag) {
            // Append title & slug to results
            $results[] = [
                'id'    => $tag->id,
                'title' => $tag->title,
                'slug'  => $tag->slug,
            ];
        }

        // Optionally append group handle to error/success messages
        $inGroup = ($groupHandle ? " in group \"{$groupHandle}\"" : '');

        // If no results
        if (!$results) {
            // Return error message
            return new SkillResponse([
                'success' => false,
                'message' => "No tags found{$inGroup}."
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed basic info for all tags{$inGroup}.",
            'response' => Json::encode($results)
        ]);
    }

    /**
     * Get a tag.
     *
     * @param string $tagId ID of the tag to retrieve.
     * @return SkillResponse
     */
    public static function getTag(string $tagId): SkillResponse
    {
        // Get the tag by ID
        $tag = Craft::$app->getElements()->getElementById($tagId);

        // If no such tag exists
        if (!$tag) {
            // Return error message
            return new SkillResponse([
                'success' => false,
                'message' => "Can't find tag with the ID {$tagId}."
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Retrieved tag \"{$tag->title}\".",
            'response' => Json::encode($tag)
        ]);
    }

    /**
     * Create a new tag.
     *
     * If you do not have a clear understanding of which tag groups exist, call the `getAllTagGroups` skill first.
     *
     * @param string $jsonConfig JSON-stringified configuration for the element. See the "Element Configs" instructions.
     * @return SkillResponse
     */
    public static function createTag(string $jsonConfig): SkillResponse
    {
        // Configure the new tag
        $tag = new Tag();

        // Populate the element
        ElementsHelper::populateElement($tag, $jsonConfig);

        // Attempt to save the element
        try {
            // If unable to save the tag, return an error response
            if (!Craft::$app->elements->saveElement($tag)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to create tag: " . implode(', ', $tag->getErrorSummary(true)),
                ]);
            }
        } catch (Throwable $e) {
            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to create the tag. {$e->getMessage()}",
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Tag \"{$tag->title}\" has been created.",
//            'response' => $config,
        ]);
    }

    /**
     * Update an existing tag.
     *
     * @param string $tagId ID of the tag to update.
     * @param string $jsonConfig JSON-stringified configuration for the element. See the "Element Configs" instructions.
     * @return SkillResponse
     */
    public static function updateTag(string $tagId, string $jsonConfig): SkillResponse
    {
        // Get the tag by ID
        $tag = Craft::$app->getElements()->getElementById($tagId);

        // If no such tag exists
        if (!$tag) {
            // Return error message
            return new SkillResponse([
                'success' => false,
                'message' => "Can't find tag with the ID {$tagId}."
            ]);
        }

        // Populate the element
        ElementsHelper::populateElement($tag, $jsonConfig);

        // Attempt to save the element
        try {
            // If unable to save the tag, return an error response
            if (!Craft::$app->elements->saveElement($tag)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to update tag: " . implode(', ', $tag->getErrorSummary(true)),
                ]);
            }
        } catch (Throwable $e) {
            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to update the tag. {$e->getMessage()}",
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Tag \"{$tag->title}\" has been updated.",
//            'response' => $config,
        ]);
    }

    /**
     * Delete a tag.
     *
     * ALWAYS ASK FOR CONFIRMATION!! This is a very destructive action.
     *
     * Force the user to re-enter the slug of the tag they are deleting.
     *
     * @param string $tagId ID of the tag to delete.
     * @return SkillResponse
     */
    public static function deleteTag(string $tagId): SkillResponse
    {
        try {
            // Get the elements service
            $elements = Craft::$app->getElements();

            // Get the tag by ID
            $tag = $elements->getElementById($tagId);

            // If no such tag exists
            if (!$tag) {
                // Throw an error message
                throw new Exception("No matching tag found.");
            }

            // Delete the tag by its ID
            $elements->deleteElementById($tagId);

        } catch (Throwable $e) {

            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to delete tag {$tagId}. {$e->getMessage()}",
            ]);

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Successfully deleted tag \"{$tag->title}\".",
        ]);
    }

    // ========================================================================= //

    /**
     * Get a complete list of existing tag groups.
     *
     * If you are unfamiliar with the existing tag groups, you MUST call this tool before creating, reading, updating, or deleting tag groups.
     * Eagerly call this if an understanding of the current tag groups is required.
     *
     * You may also find it helpful to call this tool before updating an Tag.
     *
     * @return SkillResponse
     */
    public static function getAllTagGroups(): SkillResponse
    {
        // Initialize tag groups
        $tagGroups = [];

        // Get all tag groups
        $allTagGroups = Craft::$app->getTags()->getAllTagGroups();

        // Loop through each tag group and format the output
        foreach ($allTagGroups as $tagGroup) {

            // Catalog each tag group
            $tagGroups[] = [
                'ID' => $tagGroup->id,
                'Name' => $tagGroup->name,
                'Handle' => $tagGroup->handle,
            ];

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed the existing tag groups.",
            'response' => Json::encode($tagGroups)
        ]);
    }

    /**
     * Create a new tag group.
     *
     * @param string $tagGroupConfig JSON-stringified configuration for the `TagGroup` model.
     * @return SkillResponse
     */
    public static function createTagGroup(string $tagGroupConfig): SkillResponse
    {
        // Attempt to create and save the tag group
        try {

            // Decode the JSON configurations
            $tagGroup = Json::decode($tagGroupConfig);

            // Create the tag group
            $tagGroup = new TagGroup($tagGroup);

            // If the tag group is not valid, return an error response
            if (!$tagGroup->validate()) {
                $errors = implode(', ', $tagGroup->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Invalid tag group configuration: {$errors}",
                ]);
            }

            // If unable to save the tag group, return an error response
            if (!Craft::$app->getTags()->saveTagGroup($tagGroup)) {
                $errors = implode(', ', $tagGroup->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to create tag group: {$errors}",
                ]);
            }

        } catch (Throwable $e) {

            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to create the tag group. {$e->getMessage()}",
            ]);

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Tag group \"{$tagGroup['name']}\" with handle \"{$tagGroup['handle']}\" has been created.",
//            'response' => $config,
        ]);
    }

    /**
     * Update an existing tag group with a new configuration.
     *
     * Make sure you understand the EXISTING tag group configuration before updating.
     * If needed, you MUST call `getAllTagGroups` to get the current configuration.
     *
     * For large updates, ask for confirmation before proceeding.
     *
     * @param string $tagGroupHandle Handle of the tag group to update.
     * @param string $newConfig JSON-stringified configuration for the tag group.
     * @return SkillResponse
     */
    public static function updateTagGroup(string $tagGroupHandle, string $newConfig): SkillResponse
    {
        // Attempt to update the tag group
        try {

            // Get the tag group
            $tagGroup = Craft::$app->getTags()->getTagGroupByHandle($tagGroupHandle);

            // If tag group doesn't exist, return an error response
            if (!$tagGroup) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Unable to update, tag group `{$tagGroupHandle}` does not exist.",
                ]);
            }

            // Decode the JSON configuration
            $config = Json::decode($newConfig);

            // If the configuration was not valid JSON, return an error response
            if (!is_array($config)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Invalid JSON provided for tag group configuration.",
                ]);
            }

            // Update the tag group with the new configuration
            $tagGroup->name = ($config['name'] ?? $tagGroup->name);
            $tagGroup->handle = ($config['handle'] ?? $tagGroup->handle);

            // If unable to save the tag group, return an error response
            if (!Craft::$app->getTags()->saveTagGroup($tagGroup)) {
                $errors = implode(', ', $tagGroup->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to update tag group: {$errors}",
                ]);
            }

        } catch (Throwable $e) {

            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to update the tag group. {$e->getMessage()}",
            ]);

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Tag group \"{$tagGroup->name}\" has been updated.",
//            'response' => $config,
        ]);
    }

    /**
     * Delete a tag group by its handle.
     *
     * ALWAYS ASK FOR CONFIRMATION!! This is a very destructive action.
     *
     * Force the user to re-enter the tag group handle they are deleting.
     *
     * @param string $handle Tag group to delete.
     * @return SkillResponse
     */
    public static function deleteTagGroup(string $handle): SkillResponse
    {
        // Get the tag groups service
        $tagsService = Craft::$app->getTags();

        // Attempt to find the tag group by its handle
        $tagGroup = $tagsService->getTagGroupByHandle($handle);

        // If the tag group doesn't exist, return an error response
        if (!$tagGroup) {
            return new SkillResponse([
                'success' => false,
                'message' => "Tag group \"{$handle}\" not found.",
            ]);
        }

        // Attempt to delete the tag group
        try {
            // If unable to delete the tag group, return an error response
            if (!$tagsService->deleteGroup($tagGroup)) {
                $errors = implode(', ', $tagGroup->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to delete tag group: {$errors}",
                ]);
            }
        } catch (Throwable $e) {
            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to delete the tag group. {$e->getMessage()}",
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Tag group \"{$tagGroup->name}\" has been deleted.",
//            'response' => $config,
        ]);
    }
}

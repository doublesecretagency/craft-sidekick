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
use craft\elements\Category;
use craft\helpers\Json;
use craft\models\CategoryGroup;
use craft\models\CategoryGroup_SiteSettings;
use doublesecretagency\sidekick\helpers\ElementsHelper;
use doublesecretagency\sidekick\models\SkillResponse;
use Throwable;

/**
 * @category Categories
 */
class Categories extends BaseSkillSet
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
            $restrictedMethods[] = 'createCategoryGroup';
            $restrictedMethods[] = 'updateCategoryGroup';
            $restrictedMethods[] = 'deleteCategoryGroup';
        }

        // Return list of restricted methods
        return $restrictedMethods;
    }

    // ========================================================================= //

    /**
     * Get basic information (id, title, slug) about all categories.
     *
     * Optionally specify a group handle to filter the results.
     *
     * @param string $groupHandle Optional handle of the group to filter by. Set to empty string to get all categories.
     * @return SkillResponse
     */
    public static function getCategoriesOverview(string $groupHandle): SkillResponse
    {
        // Initialize the query
        $query = Category::find()->select(['id', 'title', 'slug']);

        // If a group handle is provided
        if ($groupHandle) {
            // Filter the query by that group
            $query->group($groupHandle);
        }

        // Get all categories
        $categories = $query->all();

        // Initialize results array
        $results = [];

        // Loop over each category
        foreach ($categories as $category) {
            // Append title & slug to results
            $results[] = [
                'id'    => $category->id,
                'title' => $category->title,
                'slug'  => $category->slug,
            ];
        }

        // Optionally append group handle to error/success messages
        $inGroup = ($groupHandle ? " in group \"{$groupHandle}\"" : '');

        // If no results
        if (!$results) {
            // Return error message
            return new SkillResponse([
                'success' => false,
                'message' => "No categories found{$inGroup}."
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed basic info for all categories{$inGroup}.",
            'response' => Json::encode($results)
        ]);
    }

    /**
     * Get a category.
     *
     * @param string $categoryId ID of the category to retrieve.
     * @return SkillResponse
     */
    public static function getCategory(string $categoryId): SkillResponse
    {
        // Get the category by ID
        $category = Craft::$app->getElements()->getElementById($categoryId);

        // If no such category exists
        if (!$category) {
            // Return error message
            return new SkillResponse([
                'success' => false,
                'message' => "Can't find category with the ID {$categoryId}."
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Retrieved category {$categoryId}.",
            'response' => Json::encode($category)
        ]);
    }

    /**
     * Create a new category.
     *
     * If you do not have a clear understanding of which category groups exist, call the `getCategoryGroups` skill first.
     *
     * @param string $jsonConfig JSON-stringified configuration for the element. See the "Element Configs" instructions.
     * @return SkillResponse
     */
    public static function createCategory(string $jsonConfig): SkillResponse
    {
        // Configure the new category
        $category = new Category();

        // Populate the element
        ElementsHelper::populateElement($category, $jsonConfig);

        // Attempt to save the element
        try {
            // If unable to save the category, return an error response
            if (!Craft::$app->elements->saveElement($category)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to create category: " . implode(', ', $category->getErrorSummary(true)),
                ]);
            }
        } catch (Throwable $e) {
            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to create the category. {$e->getMessage()}",
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Category \"{$category->title}\" has been created.",
//            'response' => $config,
        ]);
    }

    /**
     * Update an existing category.
     *
     * @param string $categoryId ID of the category to update.
     * @param string $jsonConfig JSON-stringified configuration for the element. See the "Element Configs" instructions.
     * @return SkillResponse
     */
    public static function updateCategory(string $categoryId, string $jsonConfig): SkillResponse
    {
        // Get the category by ID
        $category = Craft::$app->getElements()->getElementById($categoryId);

        // If no such category exists
        if (!$category) {
            // Return error message
            return new SkillResponse([
                'success' => false,
                'message' => "Can't find category with the ID {$categoryId}."
            ]);
        }

        // Populate the element
        ElementsHelper::populateElement($category, $jsonConfig);

        // Attempt to save the element
        try {
            // If unable to save the category, return an error response
            if (!Craft::$app->elements->saveElement($category)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to update category: " . implode(', ', $category->getErrorSummary(true)),
                ]);
            }
        } catch (Throwable $e) {
            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to update the category. {$e->getMessage()}",
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Category \"{$category->title}\" has been updated.",
//            'response' => $config,
        ]);
    }

    /**
     * Delete a category.
     *
     * ALWAYS ASK FOR CONFIRMATION!! This is a very destructive action.
     *
     * Force the user to re-enter the slug of the category they are deleting.
     *
     * @param string $categoryId ID of the category to delete.
     * @return SkillResponse
     */
    public static function deleteCategory(string $categoryId): SkillResponse
    {
        try {

            // Delete the category by its ID
            Craft::$app->getElements()->deleteElementById($categoryId);

        } catch (Throwable $e) {

            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to delete category {$categoryId}. {$e->getMessage()}",
            ]);

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Successfully deleted category {$categoryId}.",
        ]);
    }

    // ========================================================================= //

    /**
     * Get a complete list of existing category groups.
     *
     * If you are unfamiliar with the existing category groups, you MUST call this tool before creating, reading, updating, or deleting category groups.
     * Eagerly call this if an understanding of the current category groups is required.
     *
     * You may also find it helpful to call this tool before updating an Category.
     *
     * @return SkillResponse
     */
    public static function getCategoryGroups(): SkillResponse
    {
        // Initialize category groups
        $categoryGroups = [];

        // Get all category groups
        $allCategoryGroups = Craft::$app->getCategories()->getAllGroups();

        // Loop through each category group and format the output
        foreach ($allCategoryGroups as $categoryGroup) {

            // Catalog each category group
            $categoryGroups[] = [
                'ID' => $categoryGroup->id,
                'Name' => $categoryGroup->name,
                'Handle' => $categoryGroup->handle,
                'Field Layout ID' => $categoryGroup->getFieldLayout(),
            ];

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed the existing category groups.",
            'response' => Json::encode($categoryGroups)
        ]);
    }

    /**
     * Create a new category group.
     *
     * @param string $categoryGroupConfig JSON-stringified configuration for the `CategoryGroup` model.
     * @param string $siteSettingsConfig JSON-stringified array of configurations, each for the `CategoryGroup_SiteSettings` model.
     * @return SkillResponse
     */
    public static function createCategoryGroup(string $categoryGroupConfig, string $siteSettingsConfig): SkillResponse
    {
        // Attempt to create and save the category group
        try {

            // Decode the JSON configurations
            $categoryGroup = Json::decode($categoryGroupConfig);
            $siteSettings  = Json::decode($siteSettingsConfig);

            // Create the category group
            $categoryGroup = new CategoryGroup($categoryGroup);

            // Append site settings
            $categoryGroup->setSiteSettings(array_map(
                static fn(array $config) => new CategoryGroup_SiteSettings($config),
                $siteSettings
            ));

            // If the category group is not valid, return an error response
            if (!$categoryGroup->validate()) {
                $errors = implode(', ', $categoryGroup->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Invalid category group configuration: {$errors}",
                ]);
            }

            // If unable to save the category group, return an error response
            if (!Craft::$app->getCategories()->saveGroup($categoryGroup)) {
                $errors = implode(', ', $categoryGroup->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to create category group: {$errors}",
                ]);
            }

        } catch (Throwable $e) {

            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to create the category group. {$e->getMessage()}",
            ]);

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Category group \"{$categoryGroup['name']}\" with handle \"{$categoryGroup['handle']}\" has been created.",
//            'response' => $config,
        ]);
    }

    /**
     * Update an existing category group with a new configuration.
     *
     * Make sure you understand the EXISTING category group configuration before updating.
     * If needed, you MUST call `getCategoryGroups` to get the current configuration.
     *
     * For large updates, ask for confirmation before proceeding.
     *
     * @param string $categoryGroupHandle Handle of the category group to update.
     * @param string $newConfig JSON-stringified configuration for the category group.
     * @return SkillResponse
     */
    public static function updateCategoryGroup(string $categoryGroupHandle, string $newConfig): SkillResponse
    {
        // Attempt to update the category group
        try {

            // Get the category group
            $categoryGroup = Craft::$app->getCategories()->getGroupByHandle($categoryGroupHandle);

            // If category group doesn't exist, return an error response
            if (!$categoryGroup) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Unable to update, category group `{$categoryGroupHandle}` does not exist.",
                ]);
            }

            // Decode the JSON configuration
            $config = Json::decode($newConfig);

            // If the configuration was not valid JSON, return an error response
            if (!is_array($config)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Invalid JSON provided for category group configuration.",
                ]);
            }

            // Update the category group with the new configuration
            $categoryGroup->name = ($config['name'] ?? $categoryGroup->name);
            $categoryGroup->handle = ($config['handle'] ?? $categoryGroup->handle);

            // If unable to save the category group, return an error response
            if (!Craft::$app->getCategories()->saveGroup($categoryGroup)) {
                $errors = implode(', ', $categoryGroup->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to update category group: {$errors}",
                ]);
            }

        } catch (Throwable $e) {

            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to update the category group. {$e->getMessage()}",
            ]);

        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Category group \"{$categoryGroup->name}\" has been updated.",
//            'response' => $config,
        ]);
    }

    /**
     * Delete a category group by its handle.
     *
     * ALWAYS ASK FOR CONFIRMATION!! This is a very destructive action.
     *
     * Force the user to re-enter the category group handle they are deleting.
     *
     * @param string $handle Category group to delete.
     * @return SkillResponse
     */
    public static function deleteCategoryGroup(string $handle): SkillResponse
    {
        // Get the category groups service
        $categoriesService = Craft::$app->getCategories();

        // Attempt to find the category group by its handle
        $categoryGroup = $categoriesService->getGroupByHandle($handle);

        // If the category group doesn't exist, return an error response
        if (!$categoryGroup) {
            return new SkillResponse([
                'success' => false,
                'message' => "Category group \"{$handle}\" not found.",
            ]);
        }

        // Attempt to delete the category group
        try {
            // If unable to delete the category group, return an error response
            if (!$categoriesService->deleteGroup($categoryGroup)) {
                $errors = implode(', ', $categoryGroup->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to delete category group: {$errors}",
                ]);
            }
        } catch (Throwable $e) {
            // Something went wrong, return an error response
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to delete the category group. {$e->getMessage()}",
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Category group \"{$categoryGroup->name}\" has been deleted.",
//            'response' => $config,
        ]);
    }
}

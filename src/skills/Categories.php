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
use craft\errors\CategoryGroupNotFoundException;
use craft\errors\ElementNotFoundException;
use craft\helpers\Json;
use craft\models\CategoryGroup;
use craft\models\CategoryGroup_SiteSettings;
use doublesecretagency\sidekick\helpers\ElementsHelper;
use doublesecretagency\sidekick\helpers\SkillsHelper;
use doublesecretagency\sidekick\models\SkillResponse;
use Throwable;
use yii\base\Exception;

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
     * Get basic information about all categories. Optionally specify a group to filter the results.
     *
     * Use this tool to get an overview of all categories in the system.
     * For details on a specific category, use the `getCategory` tool afterward.
     *
     * @param string $groupHandle Optional handle of the group to filter by. Set to empty string to get all categories.
     * @return SkillResponse
     */
    public static function getAllCategories(string $groupHandle): SkillResponse
    {
        // Initialize the query
        $query = Category::find()->select([
            'id',
            'groupId',
            'title',
            'slug'
        ]);

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
        /** @var Category $category */
        foreach ($categories as $category) {
            // Append data to results
            $results[] = [
                'id'      => $category->id,
                'groupId' => $category->groupId,
                'title'   => $category->title,
                'slug'    => $category->slug,
            ];
        }

        // Optionally append group handle to error/success messages
        $inGroup = ($groupHandle ? " in group \"{$groupHandle}\"" : '');

        // If no results
        if (!$results) {
            // Return success message with no results
            return new SkillResponse([
                'success' => true,
                'message' => "No categories found{$inGroup}."
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed basic info for all categories{$inGroup}.",
            'response' => SkillsHelper::toCsv($results)
        ]);
    }

    /**
     * Get a category.
     *
     * If you don't know which categories exist, you MUST call the `getAllCategories` tool instead.
     *
     * @param string $categoryId ID of the category to retrieve.
     * @return SkillResponse
     * @throws Exception
     */
    public static function getCategory(string $categoryId): SkillResponse
    {
        // Get the category by ID
        $category = Craft::$app->getElements()->getElementById($categoryId);

        // If no such category exists
        if (!$category) {
            throw new Exception("Can't find category with the ID {$categoryId}.");
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Category read > [{$category->title}]({$category->getCpEditUrl()})",
            'response' => Json::encode($category)
        ]);
    }

    /**
     * Create a new category.
     *
     * If you do not have a clear understanding of which category groups exist, call the `getAllCategoryGroups` skill first.
     *
     * @param string $jsonConfig JSON-stringified configuration for the element. See the "Element Configs" instructions.
     * @return SkillResponse
     * @throws Exception
     * @throws Throwable
     * @throws ElementNotFoundException
     */
    public static function createCategory(string $jsonConfig): SkillResponse
    {
        // Configure the new category
        $category = new Category();

        // Populate the element
        ElementsHelper::populateElement($category, $jsonConfig);

        // If unable to save the category, throw an exception
        if (!Craft::$app->elements->saveElement($category)) {
            throw new Exception("Unable to create category: " . implode(', ', $category->getErrorSummary(true)));
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Category created > [{$category->title}]({$category->getCpEditUrl()})",
//            'response' => $config,
        ]);
    }

    /**
     * Update an existing category.
     *
     * @param string $categoryId ID of the category to update.
     * @param string $jsonConfig JSON-stringified configuration for the element. See the "Element Configs" instructions.
     * @return SkillResponse
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws Throwable
     */
    public static function updateCategory(string $categoryId, string $jsonConfig): SkillResponse
    {
        // Get the category by ID
        $category = Craft::$app->getElements()->getElementById($categoryId);

        // If no such category exists
        if (!$category) {
            throw new Exception("Can't find category with the ID {$categoryId}.");
        }

        // Populate the element
        ElementsHelper::populateElement($category, $jsonConfig);

        // If unable to save the category, throw an exception
        if (!Craft::$app->elements->saveElement($category)) {
            throw new Exception("Unable to update category: " . implode(', ', $category->getErrorSummary(true)));
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Category updated > [{$category->title}]({$category->getCpEditUrl()})",
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
     * @throws Exception
     * @throws Throwable
     */
    public static function deleteCategory(string $categoryId): SkillResponse
    {
        // Get the elements service
        $elements = Craft::$app->getElements();

        // Get the category by ID
        $category = $elements->getElementById($categoryId);

        // If no such category exists
        if (!$category) {
            // Throw an error message
            throw new Exception("No matching category found.");
        }

        // Delete the category by its ID
        $elements->deleteElementById($categoryId);

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Category deleted > {$category->title}",
        ]);
    }

    // ========================================================================= //

    /**
     * Get a complete list of existing category groups.
     *
     * If you are unfamiliar with the existing category groups, you MUST call this tool before creating, reading, updating, or deleting category groups.
     * Eagerly call this if an understanding of the current category groups is required.
     *
     * You may also find it helpful to call this tool before updating a Category.
     *
     * @return SkillResponse
     */
    public static function getAllCategoryGroups(): SkillResponse
    {
        // Get all category groups
        $categoryGroups = Craft::$app->getCategories()->getAllGroups();

        // Initialize results array
        $results = [];

        // Loop through each category group
        foreach ($categoryGroups as $group) {
            // Append data to results
            $results[] = [
                'id'            => $group->id,
                'fieldLayoutId' => $group->getFieldLayout()->id,
                'name'          => $group->name,
                'handle'        => $group->handle,
            ];
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed the existing category groups.",
            'response' => SkillsHelper::toCsv($results)
        ]);
    }

    /**
     * Create a new category group.
     *
     * @param string $categoryGroupConfig JSON-stringified configuration for the `CategoryGroup` model.
     * @param string $siteSettingsConfig JSON-stringified array of configurations, each for the `CategoryGroup_SiteSettings` model.
     * @return SkillResponse
     * @throws Exception
     * @throws Throwable
     * @throws CategoryGroupNotFoundException
     */
    public static function createCategoryGroup(string $categoryGroupConfig, string $siteSettingsConfig): SkillResponse
    {
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

        // If the category group is not valid, throw an exception
        if (!$categoryGroup->validate()) {
            $errors = implode(', ', $categoryGroup->getErrorSummary(true));
            throw new Exception("Invalid category group configuration: {$errors}");
        }

        // If unable to save the category group, throw an exception
        if (!Craft::$app->getCategories()->saveGroup($categoryGroup)) {
            $errors = implode(', ', $categoryGroup->getErrorSummary(true));
            throw new Exception("Unable to create category group: {$errors}");
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
     * If needed, you MUST call `getAllCategoryGroups` to get the current configuration.
     *
     * For large updates, ask for confirmation before proceeding.
     *
     * @param string $categoryGroupHandle Handle of the category group to update.
     * @param string $newConfig JSON-stringified configuration for the category group.
     * @return SkillResponse
     * @throws CategoryGroupNotFoundException
     * @throws Exception
     * @throws Throwable
     */
    public static function updateCategoryGroup(string $categoryGroupHandle, string $newConfig): SkillResponse
    {
        // Get the category group
        $categoryGroup = Craft::$app->getCategories()->getGroupByHandle($categoryGroupHandle);

        // If category group doesn't exist, throw an exception
        if (!$categoryGroup) {
            throw new Exception("Unable to update, category group `{$categoryGroupHandle}` does not exist.");
        }

        // Decode the JSON configuration
        $config = Json::decode($newConfig);

        // If the configuration was not valid JSON, throw an exception
        if (!is_array($config)) {
            throw new Exception("Invalid JSON provided for category group configuration.");
        }

        // Update the category group with the new configuration
        $categoryGroup->name = ($config['name'] ?? $categoryGroup->name);
        $categoryGroup->handle = ($config['handle'] ?? $categoryGroup->handle);

        // If unable to save the category group, throw an exception
        if (!Craft::$app->getCategories()->saveGroup($categoryGroup)) {
            $errors = implode(', ', $categoryGroup->getErrorSummary(true));
            throw new Exception("Unable to update category group: {$errors}");
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
     * @throws Exception
     */
    public static function deleteCategoryGroup(string $handle): SkillResponse
    {
        // Get the category groups service
        $categoriesService = Craft::$app->getCategories();

        // Attempt to find the category group by its handle
        $categoryGroup = $categoriesService->getGroupByHandle($handle);

        // If the category group doesn't exist, throw an exception
        if (!$categoryGroup) {
            throw new Exception("Category group \"{$handle}\" not found.");
        }

        // If unable to delete the category group, throw an exception
        if (!$categoriesService->deleteGroup($categoryGroup)) {
            $errors = implode(', ', $categoryGroup->getErrorSummary(true));
            throw new Exception("Unable to delete category group: {$errors}");
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Category group \"{$categoryGroup->name}\" has been deleted.",
//            'response' => $config,
        ]);
    }
}

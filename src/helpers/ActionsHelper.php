<?php

namespace doublesecretagency\sidekick\helpers;

use Craft;
use doublesecretagency\sidekick\Sidekick;
use Symfony\Component\DomCrawler\Crawler;
use yii\base\Exception;
use yii\helpers\FileHelper;

class ActionsHelper
{
    /**
     * ### **updateElement**
     *
     * - **Description:** Update the content of a specific HTML element within a Twig template.
     *
     * - **Parameters:**
     *   - **action**: `"updateElement"`
     *   - **file**: The path to the target Twig file.
     *   - **element**: The HTML tag or Twig block to update.
     *   - **newValue**: The new content to insert.
     *
     * - **User Instruction Example:**
     *
     *   "Change the `<h1>` in `index.twig` to 'Welcome to Our Site'"
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "updateElement",
     *       "file": "/templates/index.twig",
     *       "element": "h1",
     *       "newValue": "Welcome to Our Site"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function updateElement(array $action): array
    {
        $filePath = $action['file'];
        $selector = $action['element']; // Now using CSS selector
        $newValue = $action['newValue'];

        $fileService = Sidekick::$plugin->fileManagement;

        $content = $fileService->readFile($filePath);
        if ($content === null) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        try {
            $crawler = new Crawler($content);

            // Select elements matching the CSS selector
            $crawler->filter($selector)->each(function (Crawler $node) use ($newValue) {
                // Replace the content of the node
                $node->getNode(0)->nodeValue = $newValue;
            });

            // Get the modified HTML
            $modifiedContent = $crawler->html();

            // Write back the modified content
            if (!$fileService->rewriteFile($filePath, $modifiedContent)) {
                return ['success' => false, 'message' => "Failed to write changes to '{$filePath}'."];
            }

            return ['success' => true, 'message' => "Element '{$selector}' in '{$filePath}' updated successfully."];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => "An error occurred while updating the element: {$e->getMessage()}"];
        }
    }

    /**
     * ### **createFile**
     *
     * - **Description:** Create a new Twig template file with specified content.
     *
     * - **Parameters:**
     *   - **action**: `"createFile"`
     *   - **file**: The path to the new file.
     *   - **content**: The content to include in the file.
     *
     * - **User Instruction Example:**
     *
     *   "Create a new template called `about.twig` with basic HTML structure."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "createFile",
     *       "file": "/templates/about.twig",
     *       "content": "<!DOCTYPE html>\n<html>\n<head>\n    <title>About Us</title>\n</head>\n<body>\n    <!-- Content goes here -->\n</body>\n</html>"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function createFile(array $action): array
    {
        $filePath = $action['file'];
        $content = $action['content'] ?? '';

        $fileService = Sidekick::$plugin->fileManagement;

        // Check if file already exists
        if ($fileService->fileExists($filePath)) {
            return ['success' => false, 'message' => "File already exists: {$filePath}"];
        }

        $result = $fileService->writeFile($filePath, $content);
        if ($result) {
            return ['success' => true, 'message' => "File '{$filePath}' created successfully."];
        } else {
            return ['success' => false, 'message' => "Failed to create file '{$filePath}'."];
        }
    }

    /**
     * ### **deleteFile**
     *
     * - **Description:** Delete an existing Twig template file.
     *
     * - **Parameters:**
     *   - **action**: `"deleteFile"`
     *   - **file**: The path to the file to delete.
     *
     * - **User Instruction Example:**
     *
     *   "Delete the `old_layout.twig` file."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "deleteFile",
     *       "file": "/templates/old_layout.twig"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function deleteFile(array $action): array
    {
        $filePath = $action['file'];

        $fileService = Sidekick::$plugin->fileManagement;

        // Check if file exists
        if (!$fileService->fileExists($filePath)) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        $result = $fileService->deleteFile($filePath);
        if ($result) {
            return ['success' => true, 'message' => "File '{$filePath}' deleted successfully."];
        } else {
            return ['success' => false, 'message' => "Failed to delete file '{$filePath}'."];
        }
    }

    /**
     * ### **insertContent**
     *
     * - **Description:** Insert specific content into a file at a specified location.
     *
     * - **Parameters:**
     *   - **action**: `"insertContent"`
     *   - **file**: The path to the target Twig file.
     *   - **location**: The reference point for insertion (e.g., `"afterElement": "header"`).
     *   - **content**: The content to insert.
     *
     * - **User Instruction Example:**
     *
     *   "Insert a navigation menu after the header in `index.twig`."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "insertContent",
     *       "file": "/templates/index.twig",
     *       "location": {
     *         "afterElement": "header"
     *       },
     *       "content": "<nav>...navigation menu...</nav>"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function insertContent(array $action): array
    {
        $filePath = $action['file'];
        $location = $action['location'];
        $contentToInsert = $action['content'];

        $fileService = Sidekick::$plugin->fileManagement;

        $content = $fileService->readFile($filePath);
        if ($content === null) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        // Implement logic to insert content at the specified location
        $modifiedContent = $contentToInsert;
//        $modifiedContent = $this->_insertContentAtLocation($content, $location, $contentToInsert);

        $result = $fileService->rewriteFile($filePath, $modifiedContent);
        if ($result) {
            return ['success' => true, 'message' => "Content inserted into '{$filePath}' successfully."];
        } else {
            return ['success' => false, 'message' => "Failed to write changes to '{$filePath}'."];
        }
    }

    /**
     * ### **replaceContent**
     *
     * - **Description:** Replace a specific block of content in a file.
     *
     * - **Parameters:**
     *   - **action**: `"replaceContent"`
     *   - **file**: The path to the target Twig file.
     *   - **target**: The content or element to replace.
     *   - **newContent**: The new content to insert.
     *
     * - **User Instruction Example:**
     *
     *   "Replace the footer section in `base.twig` with new content."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "replaceContent",
     *       "file": "/templates/base.twig",
     *       "target": "footer",
     *       "newContent": "<footer>...new footer content...</footer>"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function replaceContent(array $action): array
    {
        $filePath = $action['file'];
        $target = $action['target'];
        $newContent = $action['newContent'];

        $fileService = Sidekick::$plugin->fileManagement;

        $content = $fileService->readFile($filePath);
        if ($content === null) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        // Implement logic to replace target content with new content
        $modifiedContent = str_replace($target, $newContent, $content);

        $result = $fileService->rewriteFile($filePath, $modifiedContent);
        if ($result) {
            return ['success' => true, 'message' => "Content in '{$filePath}' replaced successfully."];
        } else {
            return ['success' => false, 'message' => "Failed to write changes to '{$filePath}'."];
        }
    }

    /**
     * ### **appendContent**
     *
     * - **Description:** Add content to the end of a file.
     *
     * - **Parameters:**
     *   - **action**: `"appendContent"`
     *   - **file**: The path to the target Twig file.
     *   - **content**: The content to append.
     *
     * - **User Instruction Example:**
     *
     *   "Append a script tag to the end of `layout.twig`."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "appendContent",
     *       "file": "/templates/layout.twig",
     *       "content": "<script src='app.js'></script>"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function appendContent(array $action): array
    {
        $filePath = $action['file'];
        $contentToAppend = $action['content'];

        $fileService = Sidekick::$plugin->fileManagement;

        $result = $fileService->appendToFile($filePath, $contentToAppend);
        if ($result) {
            return ['success' => true, 'message' => "Content appended to '{$filePath}' successfully."];
        } else {
            return ['success' => false, 'message' => "Failed to append content to '{$filePath}'."];
        }
    }

    /**
     * ### **prependContent**
     *
     * - **Description:** Add content to the beginning of a file.
     *
     * - **Parameters:**
     *   - **action**: `"prependContent"`
     *   - **file**: The path to the target Twig file.
     *   - **content**: The content to prepend.
     *
     * - **User Instruction Example:**
     *
     *   "Add a comment at the top of `index.twig` noting the last update date."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "prependContent",
     *       "file": "/templates/index.twig",
     *       "content": "{# Last updated on 2023-10-05 #}\n"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function prependContent(array $action): array
    {
        $filePath = $action['file'];
        $contentToPrepend = $action['content'];

        $fileService = Sidekick::$plugin->fileManagement;

        $originalContent = $fileService->readFile($filePath);
        if ($originalContent === null) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        $modifiedContent = $contentToPrepend . $originalContent;

        $result = $fileService->rewriteFile($filePath, $modifiedContent);
        if ($result) {
            return ['success' => true, 'message' => "Content prepended to '{$filePath}' successfully."];
        } else {
            return ['success' => false, 'message' => "Failed to prepend content to '{$filePath}'."];
        }
    }

    /**
     * ### **updateVariable**
     *
     * - **Description:** Change the value of a variable within the template.
     *
     * - **Parameters:**
     *   - **action**: `"updateVariable"`
     *   - **file**: The path to the target Twig file.
     *   - **variable**: The name of the variable to update.
     *   - **newValue**: The new value for the variable.
     *
     * - **User Instruction Example:**
     *
     *   "Set the `siteTitle` variable to 'My New Site' in `config.twig`."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "updateVariable",
     *       "file": "/templates/config.twig",
     *       "variable": "siteTitle",
     *       "newValue": "My New Site"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function updateVariable(array $action): array
    {
        $filePath = $action['file'];
        $variable = $action['variable'];
        $newValue = $action['newValue'];

        $fileService = Sidekick::$plugin->fileManagement;

        $content = $fileService->readFile($filePath);
        if ($content === null) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        // Use regex to find and replace the variable assignment
        $pattern = '/({%\s*set\s*' . preg_quote($variable, '/') . '\s*=\s*)(["\']?)(.*?)\2(\s*%})/';
        $replacement = '\1\2' . $newValue . '\2\4';
        $modifiedContent = preg_replace($pattern, $replacement, $content);

        if ($modifiedContent === null) {
            return ['success' => false, 'message' => "Failed to update variable '{$variable}' in '{$filePath}'."];
        }

        $result = $fileService->rewriteFile($filePath, $modifiedContent);
        if ($result) {
            return ['success' => true, 'message' => "Variable '{$variable}' in '{$filePath}' updated successfully."];
        } else {
            return ['success' => false, 'message' => "Failed to write changes to '{$filePath}'."];
        }
    }

    /**
     * ### **addBlock**
     *
     * - **Description:** Add a new Twig block to a template.
     *
     * - **Parameters:**
     *   - **action**: `"addBlock"`
     *   - **file**: The path to the target Twig file.
     *   - **blockName**: The name of the new block.
     *   - **content**: The content of the new block.
     *
     * - **User Instruction Example:**
     *
     *   "Add a new block called `sidebar` to `base.twig`."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "addBlock",
     *       "file": "/templates/base.twig",
     *       "blockName": "sidebar",
     *       "content": "{% block sidebar %}\n<!-- Sidebar content here -->\n{% endblock %}"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function addBlock(array $action): array
    {
        $filePath = $action['file'];
        $blockName = $action['blockName'];
        $content = $action['content'];

        $fileService = Sidekick::$plugin->fileManagement;

        $templateContent = $fileService->readFile($filePath);
        if ($templateContent === null) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        // Append the new block at the end of the file
        $modifiedContent = $templateContent . "\n" . $content;

        if (!$fileService->rewriteFile($filePath, $modifiedContent)) {
            return ['success' => false, 'message' => "Failed to add block '{$blockName}' to '{$filePath}'."];
        }

        return ['success' => true, 'message' => "Block '{$blockName}' added to '{$filePath}' successfully."];
    }

    /**
     * ### **removeBlock**
     *
     * - **Description:** Remove a Twig block from a template.
     *
     * - **Parameters:**
     *   - **action**: `"removeBlock"`
     *   - **file**: The path to the target Twig file.
     *   - **blockName**: The name of the block to remove.
     *
     * - **User Instruction Example:**
     *
     *   "Remove the `advertisement` block from `layout.twig`."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "removeBlock",
     *       "file": "/templates/layout.twig",
     *       "blockName": "advertisement"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function removeBlock(array $action): array
    {
        $filePath = $action['file'];
        $blockName = $action['blockName'];

        $fileService = Sidekick::$plugin->fileManagement;

        $templateContent = $fileService->readFile($filePath);
        if ($templateContent === null) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        // Use regex to remove the block
        $pattern = '/{% block\s+' . preg_quote($blockName, '/') . '\b.*?%}(.*?)({% endblock %})/s';
        $modifiedContent = preg_replace($pattern, '', $templateContent);

        if ($modifiedContent === null) {
            return ['success' => false, 'message' => "Failed to remove block '{$blockName}' from '{$filePath}'."];
        }

        if (!$fileService->rewriteFile($filePath, $modifiedContent)) {
            return ['success' => false, 'message' => "Failed to write changes to '{$filePath}'."];
        }

        return ['success' => true, 'message' => "Block '{$blockName}' removed from '{$filePath}' successfully."];
    }

    /**
     * ### **removeElement**
     *
     * - **Description:** Remove an HTML element and its content from a Twig template.
     *
     * - **Parameters:**
     *   - **action**: `"removeElement"`
     *   - **file**: The path to the target Twig file.
     *   - **element**: The HTML tag to remove.
     *
     * - **User Instruction Example:**
     *
     *   "Remove the `<h1>` tag from `index.twig`."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "removeElement",
     *       "file": "/templates/index.twig",
     *       "element": "h1"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function removeElement(array $action): array
    {
        $filePath = $action['file'];
        $selector = $action['element']; // Now using CSS selector

        $fileService = Sidekick::$plugin->fileManagement;

        // Read the file content
        $content = $fileService->readFile($filePath);
        if ($content === null) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        try {
            $crawler = new Crawler($content);

            // Remove elements matching the selector
            $crawler->filter($selector)->each(function (Crawler $node) {
                $node->getNode(0)->parentNode->removeChild($node->getNode(0));
            });

            $modifiedContent = $crawler->html();

            // Write back the modified content
            if (!$fileService->rewriteFile($filePath, $modifiedContent)) {
                return ['success' => false, 'message' => "Failed to write changes to '{$filePath}'."];
            }

            return ['success' => true, 'message' => "Element '{$selector}' removed from '{$filePath}' successfully."];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => "An error occurred while removing the element: {$e->getMessage()}"];
        }
    }

    /**
     * ### **wrapContent**
     *
     * - **Description:** Wrap existing content within a new element or block.
     *
     * - **Parameters:**
     *   - **action**: `"wrapContent"`
     *   - **file**: The path to the target Twig file.
     *   - **targetContent**: The content or selector to wrap.
     *   - **wrapper**: The new element or block to wrap around the target content.
     *
     * - **User Instruction Example:**
     *
     *   "Wrap the main content of `index.twig` in a `<div class='container'>`."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "wrapContent",
     *       "file": "/templates/index.twig",
     *       "targetContent": "main",
     *       "wrapper": "<div class='container'>{{ content }}</div>"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function wrapContent(array $action): array
    {
        $filePath = $action['file'];
        $targetContent = $action['targetContent'];
        $wrapper = $action['wrapper'];

        $fileService = Sidekick::$plugin->fileManagement;

        $templateContent = $fileService->readFile($filePath);
        if ($templateContent === null) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        // Wrap the target content
        $modifiedContent = str_replace($targetContent, $wrapper, $templateContent);

        if (!$fileService->rewriteFile($filePath, $modifiedContent)) {
            return ['success' => false, 'message' => "Failed to wrap content in '{$filePath}'."];
        }

        return ['success' => true, 'message' => "Content in '{$filePath}' wrapped successfully."];
    }

    /**
     * ### **modifyAttribute**
     *
     * - **Description:** Change attributes of HTML elements within the template.
     *
     * - **Parameters:**
     *   - **action**: `"modifyAttribute"`
     *   - **file**: The path to the target Twig file.
     *   - **element**: The HTML tag or selector to modify.
     *   - **attribute**: The attribute to change.
     *   - **newValue**: The new value for the attribute.
     *
     * - **User Instruction Example:**
     *
     *   "Change the class of the `<body>` tag to `homepage` in `index.twig`."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "modifyAttribute",
     *       "file": "/templates/index.twig",
     *       "element": "body",
     *       "attribute": "class",
     *       "newValue": "homepage"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function modifyAttribute(array $action): array
    {
        $filePath = $action['file'];
        $element = $action['element'];
        $attribute = $action['attribute'];
        $newValue = $action['newValue'];

        $fileService = Sidekick::$plugin->fileManagement;

        $templateContent = $fileService->readFile($filePath);
        if ($templateContent === null) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        // Use regex to modify the attribute
        $pattern = '/(<' . preg_quote($element, '/') . '\b[^>]*\s' . preg_quote($attribute, '/') . '=["\'])([^"\']*)(["\'])/';
        $replacement = '\1' . $newValue . '\3';
        $modifiedContent = preg_replace($pattern, $replacement, $templateContent);

        if ($modifiedContent === null) {
            return ['success' => false, 'message' => "Failed to modify attribute '{$attribute}' in '{$filePath}'."];
        }

        if (!$fileService->rewriteFile($filePath, $modifiedContent)) {
            return ['success' => false, 'message' => "Failed to write changes to '{$filePath}'."];
        }

        return ['success' => true, 'message' => "Attribute '{$attribute}' of element '{$element}' in '{$filePath}' modified successfully."];
    }

    /**
     * ### **duplicateFile**
     *
     * - **Description:** Create a copy of an existing template file.
     *
     * - **Parameters:**
     *   - **action**: `"duplicateFile"`
     *   - **sourceFile**: The path to the existing file.
     *   - **destinationFile**: The path for the new duplicate file.
     *
     * - **User Instruction Example:**
     *
     *   "Duplicate `index.twig` and name the new file `landing.twig`."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "duplicateFile",
     *       "sourceFile": "/templates/index.twig",
     *       "destinationFile": "/templates/landing.twig"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function duplicateFile(array $action): array
    {
        $sourceFile = $action['sourceFile'];
        $destinationFile = $action['destinationFile'];

        $fileService = Sidekick::$plugin->fileManagement;

        if (!$fileService->fileExists($sourceFile)) {
            return ['success' => false, 'message' => "Source file not found: {$sourceFile}"];
        }

        if ($fileService->fileExists($destinationFile)) {
            return ['success' => false, 'message' => "Destination file already exists: {$destinationFile}"];
        }

        if (!$fileService->copyFile($sourceFile, $destinationFile)) {
            return ['success' => false, 'message' => "Failed to duplicate file to '{$destinationFile}'."];
        }

        return ['success' => true, 'message' => "File duplicated to '{$destinationFile}' successfully."];
    }

    /**
     * ### **renameFile**
     *
     * - **Description:** Rename an existing template file.
     *
     * - **Parameters:**
     *   - **action**: `"renameFile"`
     *   - **oldFile**: The current path to the file.
     *   - **newFile**: The new path for the file.
     *
     * - **User Instruction Example:**
     *
     *   "Rename `old_home.twig` to `home.twig`."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "renameFile",
     *       "oldFile": "/templates/old_home.twig",
     *       "newFile": "/templates/home.twig"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function renameFile(array $action): array
    {
        $oldFile = $action['oldFile'];
        $newFile = $action['newFile'];

        $fileService = Sidekick::$plugin->fileManagement;

        if (!$fileService->fileExists($oldFile)) {
            return ['success' => false, 'message' => "File not found: {$oldFile}"];
        }

        if ($fileService->fileExists($newFile)) {
            return ['success' => false, 'message' => "A file with the new name already exists: {$newFile}"];
        }

        if (!$fileService->renameFile($oldFile, $newFile)) {
            return ['success' => false, 'message' => "Failed to rename file to '{$newFile}'."];
        }

        return ['success' => true, 'message' => "File renamed to '{$newFile}' successfully."];
    }

    /**
     * ### **searchAndReplace**
     *
     * - **Description:** Search for a specific string in a file and replace it with another string.
     *
     * - **Parameters:**
     *   - **action**: `"searchAndReplace"`
     *   - **file**: The path to the target Twig file.
     *   - **search**: The string to search for.
     *   - **replace**: The string to replace it with.
     *
     * - **User Instruction Example:**
     *
     *   "In `base.twig`, replace all instances of `oldBrand` with `newBrand`."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "searchAndReplace",
     *       "file": "/templates/base.twig",
     *       "search": "oldBrand",
     *       "replace": "newBrand"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function searchAndReplace(array $action): array
    {
        $filePath = $action['file'];
        $search = $action['search'];
        $replace = $action['replace'];

        $fileService = Sidekick::$plugin->fileManagement;

        $content = $fileService->readFile($filePath);
        if ($content === null) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        $modifiedContent = str_replace($search, $replace, $content);

        if (!$fileService->rewriteFile($filePath, $modifiedContent)) {
            return ['success' => false, 'message' => "Failed to perform search and replace in '{$filePath}'."];
        }

        return ['success' => true, 'message' => "Search and replace in '{$filePath}' completed successfully."];
    }

    /**
     * ### **listFiles**
     *
     * - **Description:** Provide a list of all Twig templates in the `/templates` directory.
     *
     * - **Parameters:**
     *   - **action**: `"listFiles"`
     *
     * - **User Instruction Example:**
     *
     *   "List all available templates."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "listFiles"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     * @throws Exception
     */
    public static function listFiles(array $action): array
    {
        $templatesPath = Craft::$app->getPath()->getSiteTemplatesPath();

        $files = FileHelper::findFiles($templatesPath, ['only' => ['*.twig']]);

        // Convert full paths to relative paths
        $relativeFiles = array_map(
        // Convert full paths to relative paths
            static function ($file) use ($templatesPath) {
                // Remove the path to the templates directory
                return str_replace($templatesPath . DIRECTORY_SEPARATOR, '', $file);
            },
            $files
        );

        return ['success' => true, 'message' => 'Files listed successfully.', 'files' => $relativeFiles];
    }

    /**
     * ### **getFileInfo**
     *
     * - **Description:** Retrieve metadata about a file, such as its size or last modified date.
     *
     * - **Parameters:**
     *   - **action**: `"getFileInfo"`
     *   - **file**: The path to the target Twig file.
     *
     * - **User Instruction Example:**
     *
     *   "Get info about `layout.twig`."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "getFileInfo",
     *       "file": "/templates/layout.twig"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function getFileInfo(array $action): array
    {
        $filePath = $action['file'];

        $fileService = Sidekick::$plugin->fileManagement;

        if (!$fileService->fileExists($filePath)) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        $fullPath = $fileService->getFullPath($filePath);
        $fileInfo = [
            'size' => filesize($fullPath),
            'lastModified' => filemtime($fullPath),
        ];

        return ['success' => true, 'message' => 'File info retrieved successfully.', 'fileInfo' => $fileInfo];
    }

    /**
     * ### **commentBlock**
     *
     * - **Description:** Comment out a block of code within a template.
     *
     * - **Parameters:**
     *   - **action**: `"commentBlock"`
     *   - **file**: The path to the target Twig file.
     *   - **target**: The content or selector to comment out.
     *
     * - **User Instruction Example:**
     *
     *   "Comment out the navigation menu in `header.twig`."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "commentBlock",
     *       "file": "/templates/header.twig",
     *       "target": "navigation menu"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function commentBlock(array $action): array
    {
        $filePath = $action['file'];
        $target = $action['target'];

        $fileService = Sidekick::$plugin->fileManagement;

        $content = $fileService->readFile($filePath);
        if ($content === null) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        // Use regex to find the target and comment it out
        $pattern = '/(' . preg_quote($target, '/') . ')/s';
        $replacement = '{# $1 #}';
        $modifiedContent = preg_replace($pattern, $replacement, $content, 1);

        if ($modifiedContent === null) {
            return ['success' => false, 'message' => "Failed to comment out target in '{$filePath}'."];
        }

        if (!$fileService->rewriteFile($filePath, $modifiedContent)) {
            return ['success' => false, 'message' => "Failed to write changes to '{$filePath}'."];
        }

        return ['success' => true, 'message' => "Target in '{$filePath}' commented out successfully."];
    }

    /**
     * ### **uncommentBlock**
     *
     * - **Description:** Uncomment a previously commented block of code.
     *
     * - **Parameters:**
     *   - **action**: `"uncommentBlock"`
     *   - **file**: The path to the target Twig file.
     *   - **target**: The content or selector to uncomment.
     *
     * - **User Instruction Example:**
     *
     *   "Uncomment the footer section in `base.twig`."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "uncommentBlock",
     *       "file": "/templates/base.twig",
     *       "target": "footer section"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function uncommentBlock(array $action): array
    {
        $filePath = $action['file'];
        $target = $action['target'];

        $fileService = Sidekick::$plugin->fileManagement;

        $content = $fileService->readFile($filePath);
        if ($content === null) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        // Use regex to find the commented target and uncomment it
        $pattern = '/{#\s*(' . preg_quote($target, '/') . ')\s*#}/s';
        $replacement = '$1';
        $modifiedContent = preg_replace($pattern, $replacement, $content, 1);

        if ($modifiedContent === null) {
            return ['success' => false, 'message' => "Failed to uncomment target in '{$filePath}'."];
        }

        if (!$fileService->rewriteFile($filePath, $modifiedContent)) {
            return ['success' => false, 'message' => "Failed to write changes to '{$filePath}'."];
        }

        return ['success' => true, 'message' => "Target in '{$filePath}' uncommented successfully."];
    }

    /**
     * ### **addInclude**
     *
     * - **Description:** Add an `{% include %}` statement to include another template.
     *
     * - **Parameters:**
     *   - **action**: `"addInclude"`
     *   - **file**: The path to the target Twig file.
     *   - **includeFile**: The path to the template to include.
     *   - **location**: (Optional) The location to insert the include statement.
     *
     * - **User Instruction Example:**
     *
     *   "Include `header.twig` at the top of `index.twig`."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "addInclude",
     *       "file": "/templates/index.twig",
     *       "includeFile": "header.twig",
     *       "location": "top"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function addInclude(array $action): array
    {
        $filePath = $action['file'];
        $includeFile = $action['includeFile'];
        $location = $action['location'] ?? 'bottom';

        $fileService = Sidekick::$plugin->fileManagement;

        $templateContent = $fileService->readFile($filePath);
        if ($templateContent === null) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        $includeStatement = "{% include '{$includeFile}' %}\n";

        if ($location === 'top') {
            $modifiedContent = $includeStatement . $templateContent;
        } else {
            $modifiedContent = $templateContent . "\n" . $includeStatement;
        }

        if (!$fileService->rewriteFile($filePath, $modifiedContent)) {
            return ['success' => false, 'message' => "Failed to add include in '{$filePath}'."];
        }

        return ['success' => true, 'message' => "Include added to '{$filePath}' successfully."];
    }

    /**
     * ### **updateExtends**
     *
     * - **Description:** Change the template that a file extends.
     *
     * - **Parameters:**
     *   - **action**: `"updateExtends"`
     *   - **file**: The path to the target Twig file.
     *   - **newParent**: The new template to extend.
     *
     * - **User Instruction Example:**
     *
     *   "In `page.twig`, change `{% extends 'base.twig' %}` to `{% extends 'new_base.twig' %}`."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "updateExtends",
     *       "file": "/templates/page.twig",
     *       "newParent": "new_base.twig"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function updateExtends(array $action): array
    {
        $filePath = $action['file'];
        $newParent = $action['newParent'];

        $fileService = Sidekick::$plugin->fileManagement;

        $templateContent = $fileService->readFile($filePath);
        if ($templateContent === null) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        // Use regex to update the extends statement
        $pattern = '/{%\s*extends\s+[\'"][^\'"]+[\'"]\s*%}/';
        $replacement = "{% extends '{$newParent}' %}";
        $modifiedContent = preg_replace($pattern, $replacement, $templateContent, 1);

        if ($modifiedContent === null) {
            return ['success' => false, 'message' => "Failed to update extends in '{$filePath}'."];
        }

        if (!$fileService->rewriteFile($filePath, $modifiedContent)) {
            return ['success' => false, 'message' => "Failed to write changes to '{$filePath}'."];
        }

        return ['success' => true, 'message' => "Extends in '{$filePath}' updated successfully."];
    }

    /**
     * ### **updateMacro**
     *
     * - **Description:** Modify a macro within a template.
     *
     * - **Parameters:**
     *   - **action**: `"updateMacro"`
     *   - **file**: The path to the target Twig file.
     *   - **macroName**: The name of the macro to update.
     *   - **newContent**: The new content or parameters for the macro.
     *
     * - **User Instruction Example:**
     *
     *   "In `macros.twig`, update the `button` macro to accept a `type` parameter."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "updateMacro",
     *       "file": "/templates/macros.twig",
     *       "macroName": "button",
     *       "newContent": "{% macro button(label, type='button') %}\n<button type=\"{{ type }}\">{{ label }}</button>\n{% endmacro %}"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function updateMacro(array $action): array
    {
        $filePath = $action['file'];
        $macroName = $action['macroName'];
        $newContent = $action['newContent'];

        $fileService = Sidekick::$plugin->fileManagement;

        $templateContent = $fileService->readFile($filePath);
        if ($templateContent === null) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        // Use regex to find and replace the macro
        $pattern = '/({%\s*macro\s+' . preg_quote($macroName, '/') . '\b.*?%})(.*?)({%\s*endmacro\s*%})/s';
        $replacement = $newContent;
        $modifiedContent = preg_replace($pattern, $replacement, $templateContent, 1);

        if ($modifiedContent === null) {
            return ['success' => false, 'message' => "Failed to update macro '{$macroName}' in '{$filePath}'."];
        }

        if (!$fileService->rewriteFile($filePath, $modifiedContent)) {
            return ['success' => false, 'message' => "Failed to write changes to '{$filePath}'."];
        }

        return ['success' => true, 'message' => "Macro '{$macroName}' in '{$filePath}' updated successfully."];
    }

    /**
     * ### **insertBefore**
     *
     * - **Description:** Insert content before a specific element or line in a file.
     *
     * - **Parameters:**
     *   - **action**: `"insertBefore"`
     *   - **file**: The path to the target Twig file.
     *   - **target**: The element or content before which to insert.
     *   - **content**: The content to insert.
     *
     * - **User Instruction Example:**
     *
     *   "Insert a `<meta>` tag before the closing `</head>` tag in `layout.twig`."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "insertBefore",
     *       "file": "/templates/layout.twig",
     *       "target": "</head>",
     *       "content": "<meta name=\"description\" content=\"...\">"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function insertBefore(array $action): array
    {
        $filePath = $action['file'];
        $target = $action['target'];
        $contentToInsert = $action['content'];

        $fileService = Sidekick::$plugin->fileManagement;

        $templateContent = $fileService->readFile($filePath);
        if ($templateContent === null) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        // Insert content before the target
        $modifiedContent = str_replace($target, $contentToInsert . $target, $templateContent);

        if (!$fileService->rewriteFile($filePath, $modifiedContent)) {
            return ['success' => false, 'message' => "Failed to insert content before target in '{$filePath}'."];
        }

        return ['success' => true, 'message' => "Content inserted before target in '{$filePath}' successfully."];
    }

    /**
     * ### **insertAfter**
     *
     * - **Description:** Insert content after a specific element or line in a file.
     *
     * - **Parameters:**
     *   - **action**: `"insertAfter"`
     *   - **file**: The path to the target Twig file.
     *   - **target**: The element or content after which to insert.
     *   - **content**: The content to insert.
     *
     * - **User Instruction Example:**
     *
     *   "Insert a `<script>` tag after the closing `</body>` tag in `index.twig`."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "insertAfter",
     *       "file": "/templates/index.twig",
     *       "target": "</body>",
     *       "content": "<script src=\"analytics.js\"></script>"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function insertAfter(array $action): array
    {
        $filePath = $action['file'];
        $target = $action['target'];
        $contentToInsert = $action['content'];

        $fileService = Sidekick::$plugin->fileManagement;

        $templateContent = $fileService->readFile($filePath);
        if ($templateContent === null) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        // Insert content after the target
        $modifiedContent = str_replace($target, $target . $contentToInsert, $templateContent);

        if (!$fileService->rewriteFile($filePath, $modifiedContent)) {
            return ['success' => false, 'message' => "Failed to insert content after target in '{$filePath}'."];
        }

        return ['success' => true, 'message' => "Content inserted after target in '{$filePath}' successfully."];
    }

    /**
     * ### **extractPartial**
     *
     * - **Description:** Extract a section of code into a new partial template and include it.
     *
     * - **Parameters:**
     *   - **action**: `"extractPartial"`
     *   - **file**: The path to the original Twig file.
     *   - **target**: The content or selector to extract.
     *   - **newPartial**: The path for the new partial template.
     *
     * - **User Instruction Example:**
     *
     *   "Extract the header section from `index.twig` into a new partial called `header.twig` and include it."
     *
     * - **Assistant JSON Response:**
     *
     * ```json
     * {
     *   "actions": [
     *     {
     *       "action": "extractPartial",
     *       "file": "/templates/index.twig",
     *       "target": "header section",
     *       "newPartial": "/templates/header.twig"
     *     }
     *   ]
     * }
     * ```
     *
     * @param array $action
     * @return array
     */
    public static function extractPartial(array $action): array
    {
        $filePath = $action['file'];
        $target = $action['target'];
        $newPartial = $action['newPartial'];

        $fileService = Sidekick::$plugin->fileManagement;

        $originalContent = $fileService->readFile($filePath);
        if ($originalContent === null) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        // Use regex or other logic to extract the target content
        // For simplicity, we'll assume $target is a unique string in the file
        if (strpos($originalContent, $target) === false) {
            return ['success' => false, 'message' => "Target content not found in '{$filePath}'."];
        }

        // Write the target content to the new partial file
        if (!$fileService->writeFile($newPartial, $target)) {
            return ['success' => false, 'message' => "Failed to create partial '{$newPartial}'."];
        }

        // Replace the target content in the original file with an include statement
        $modifiedContent = str_replace($target, "{% include '" . basename($newPartial) . "' %}", $originalContent);

        if (!$fileService->rewriteFile($filePath, $modifiedContent)) {
            return ['success' => false, 'message' => "Failed to update '{$filePath}' with include statement."];
        }

        return ['success' => true, 'message' => "Partial extracted to '{$newPartial}' and included in '{$filePath}' successfully."];
    }
}

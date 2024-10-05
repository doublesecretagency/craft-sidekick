<?php

namespace doublesecretagency\sidekick\services;

use Craft;
use craft\base\Component;
use doublesecretagency\sidekick\Sidekick;
use yii\base\Exception;
use yii\helpers\FileHelper;

/**
 * Class ActionsService
 *
 * Handles the execution of actions received from the assistant.
 */
class ActionsService extends Component
{
    /**
     * Executes a list of actions provided by the assistant.
     *
     * @param array $actions
     * @return array
     */
    public function executeActions(array $actions): array
    {
        // Execute each action in the list
        foreach ($actions as $action) {

            // Handle the action
            $result = $this->_handleAction($action);

            // Return immediately if an action fails
            if (!$result['success']) {
                return $result;
            }

        }

        // Return success message if all actions succeed
        return ['success' => true, 'message' => 'All actions executed successfully.'];
    }

    /**
     * Handles a single action.
     *
     * @param array $action
     * @return array
     */
    private function _handleAction(array $action): array
    {
        // Get the type of action to be performed
        $actionType = $action['action'] ?? null;

        // Ensure action type is provided
        if (!$actionType) {
            return ['success' => false, 'message' => "Action type is missing."];
        }

        // Determine the method name
        $actionMethod = "_{$actionType}";

        // Check if the method exists in the class
        if (!method_exists($this, $actionMethod)) {
            return ['success' => false, 'message' => "Unsupported action: {$actionType}"];
        }

        // Execute the appropriate action method
        return $this->$actionMethod($action);
    }

    /**
     * Updates a specific element in a file.
     *
     * @param array $action
     * @return array
     */
    private function _updateElement(array $action): array
    {
        $filePath = $action['file'];
        $element = $action['element'];
        $newValue = $action['newValue'];

        $fileService = Sidekick::$plugin->fileManagementService;

        $content = $fileService->readFile($filePath);
        if ($content === null) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        // Modify the content
        $pattern = "/(<{$element}[^>]*>)(.*?)(<\/{$element}>)/s";
        $replacement = "\$1{$newValue}\$3";
        $modifiedContent = preg_replace($pattern, $replacement, $content);

        $result = $fileService->rewriteFile($filePath, $modifiedContent);
        if ($result) {
            return ['success' => true, 'message' => "Element '{$element}' in '{$filePath}' updated successfully."];
        } else {
            return ['success' => false, 'message' => "Failed to write changes to '{$filePath}'."];
        }
    }

    /**
     * Creates a new file with specified content.
     *
     * @param array $action
     * @return array
     */
    private function _createFile(array $action): array
    {
        $filePath = $action['file'];
        $content = $action['content'] ?? '';

        $fileService = Sidekick::$plugin->fileManagementService;

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
     * Deletes a specified file.
     *
     * @param array $action
     * @return array
     */
    private function _deleteFile(array $action): array
    {
        $filePath = $action['file'];

        $fileService = Sidekick::$plugin->fileManagementService;

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
     * Inserts content into a file at a specified location.
     *
     * @param array $action
     * @return array
     */
    private function _insertContent(array $action): array
    {
        $filePath = $action['file'];
        $location = $action['location'];
        $contentToInsert = $action['content'];

        $fileService = Sidekick::$plugin->fileManagementService;

        $content = $fileService->readFile($filePath);
        if ($content === null) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        // Implement logic to insert content at the specified location
        $modifiedContent = $this->_insertContentAtLocation($content, $location, $contentToInsert);

        $result = $fileService->rewriteFile($filePath, $modifiedContent);
        if ($result) {
            return ['success' => true, 'message' => "Content inserted into '{$filePath}' successfully."];
        } else {
            return ['success' => false, 'message' => "Failed to write changes to '{$filePath}'."];
        }
    }

    /**
     * Replaces a specific block of content in a file.
     *
     * @param array $action
     * @return array
     */
    private function _replaceContent(array $action): array
    {
        $filePath = $action['file'];
        $target = $action['target'];
        $newContent = $action['newContent'];

        $fileService = Sidekick::$plugin->fileManagementService;

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
     * Appends content to the end of a file.
     *
     * @param array $action
     * @return array
     */
    private function _appendContent(array $action): array
    {
        $filePath = $action['file'];
        $contentToAppend = $action['content'];

        $fileService = Sidekick::$plugin->fileManagementService;

        $result = $fileService->appendToFile($filePath, $contentToAppend);
        if ($result) {
            return ['success' => true, 'message' => "Content appended to '{$filePath}' successfully."];
        } else {
            return ['success' => false, 'message' => "Failed to append content to '{$filePath}'."];
        }
    }

    /**
     * Prepends content to the beginning of a file.
     *
     * @param array $action
     * @return array
     */
    private function _prependContent(array $action): array
    {
        $filePath = $action['file'];
        $contentToPrepend = $action['content'];

        $fileService = Sidekick::$plugin->fileManagementService;

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
     * Updates a variable within a Twig template.
     *
     * @param array $action
     * @return array
     */
    private function _updateVariable(array $action): array
    {
        $filePath = $action['file'];
        $variable = $action['variable'];
        $newValue = $action['newValue'];

        $fileService = Sidekick::$plugin->fileManagementService;

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
     * Adds a new Twig block to a template.
     *
     * @param array $action
     * @return array
     */
    private function _addBlock(array $action): array
    {
        $filePath = $action['file'];
        $blockName = $action['blockName'];
        $content = $action['content'];

        $fileService = Sidekick::$plugin->fileManagementService;

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
     * Removes a Twig block from a template.
     *
     * @param array $action
     * @return array
     */
    private function _removeBlock(array $action): array
    {
        $filePath = $action['file'];
        $blockName = $action['blockName'];

        $fileService = Sidekick::$plugin->fileManagementService;

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
     * Removes an HTML element from a template.
     *
     * @param array $action
     * @return array
     */
    private function _removeElement(array $action): array
    {
        $filePath = $action['file'];
        $element = $action['element'];

        $fileService = Sidekick::$plugin->fileManagementService;

        // Read the file content
        $templateContent = $fileService->readFile($filePath);
        if ($templateContent === null) {
            return ['success' => false, 'message' => "File not found: {$filePath}"];
        }

        // Use regex to remove the element and its content
        $pattern = '/<' . preg_quote($element, '/') . '\b[^>]*>.*?<\/' . preg_quote($element, '/') . '>/s';
        $modifiedContent = preg_replace($pattern, '', $templateContent);

        if ($modifiedContent === null || $modifiedContent === $templateContent) {
            return ['success' => false, 'message' => "Element '<{$element}>' not found in '{$filePath}'."];
        }

        // Write the modified content back to the file
        if (!$fileService->rewriteFile($filePath, $modifiedContent)) {
            return ['success' => false, 'message' => "Failed to write changes to '{$filePath}'."];
        }

        return ['success' => true, 'message' => "Element '<{$element}>' removed from '{$filePath}' successfully."];
    }

    /**
     * Wraps existing content within a new element or block.
     *
     * @param array $action
     * @return array
     */
    private function _wrapContent(array $action): array
    {
        $filePath = $action['file'];
        $targetContent = $action['targetContent'];
        $wrapper = $action['wrapper'];

        $fileService = Sidekick::$plugin->fileManagementService;

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
     * Modifies attributes of HTML elements within the template.
     *
     * @param array $action
     * @return array
     */
    private function _modifyAttribute(array $action): array
    {
        $filePath = $action['file'];
        $element = $action['element'];
        $attribute = $action['attribute'];
        $newValue = $action['newValue'];

        $fileService = Sidekick::$plugin->fileManagementService;

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
     * Duplicates an existing template file.
     *
     * @param array $action
     * @return array
     */
    private function _duplicateFile(array $action): array
    {
        $sourceFile = $action['sourceFile'];
        $destinationFile = $action['destinationFile'];

        $fileService = Sidekick::$plugin->fileManagementService;

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
     * Renames an existing template file.
     *
     * @param array $action
     * @return array
     */
    private function _renameFile(array $action): array
    {
        $oldFile = $action['oldFile'];
        $newFile = $action['newFile'];

        $fileService = Sidekick::$plugin->fileManagementService;

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
     * Performs a search and replace operation within a file.
     *
     * @param array $action
     * @return array
     */
    private function _searchAndReplace(array $action): array
    {
        $filePath = $action['file'];
        $search = $action['search'];
        $replace = $action['replace'];

        $fileService = Sidekick::$plugin->fileManagementService;

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
     * Lists all Twig template files in the `/templates` directory.
     *
     * @param array $action
     * @return array
     * @throws Exception
     */
    private function _listFiles(array $action): array
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
     * Retrieves metadata about a file.
     *
     * @param array $action
     * @return array
     */
    private function _getFileInfo(array $action): array
    {
        $filePath = $action['file'];

        $fileService = Sidekick::$plugin->fileManagementService;

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
     * Comments out a block of code within a template.
     *
     * @param array $action
     * @return array
     */
    private function _commentBlock(array $action): array
    {
        $filePath = $action['file'];
        $target = $action['target'];

        $fileService = Sidekick::$plugin->fileManagementService;

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
     * Uncomments a previously commented block of code.
     *
     * @param array $action
     * @return array
     */
    private function _uncommentBlock(array $action): array
    {
        $filePath = $action['file'];
        $target = $action['target'];

        $fileService = Sidekick::$plugin->fileManagementService;

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
     * Adds an `{% include %}` statement to include another template.
     *
     * @param array $action
     * @return array
     */
    private function _addInclude(array $action): array
    {
        $filePath = $action['file'];
        $includeFile = $action['includeFile'];
        $location = $action['location'] ?? 'bottom';

        $fileService = Sidekick::$plugin->fileManagementService;

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
     * Updates the `{% extends %}` statement in a template.
     *
     * @param array $action
     * @return array
     */
    private function _updateExtends(array $action): array
    {
        $filePath = $action['file'];
        $newParent = $action['newParent'];

        $fileService = Sidekick::$plugin->fileManagementService;

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
     * Updates a macro within a template.
     *
     * @param array $action
     * @return array
     */
    private function _updateMacro(array $action): array
    {
        $filePath = $action['file'];
        $macroName = $action['macroName'];
        $newContent = $action['newContent'];

        $fileService = Sidekick::$plugin->fileManagementService;

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
     * Inserts content before a specific element or line in a file.
     *
     * @param array $action
     * @return array
     */
    private function _insertBefore(array $action): array
    {
        $filePath = $action['file'];
        $target = $action['target'];
        $contentToInsert = $action['content'];

        $fileService = Sidekick::$plugin->fileManagementService;

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
     * Inserts content after a specific element or line in a file.
     *
     * @param array $action
     * @return array
     */
    private function _insertAfter(array $action): array
    {
        $filePath = $action['file'];
        $target = $action['target'];
        $contentToInsert = $action['content'];

        $fileService = Sidekick::$plugin->fileManagementService;

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
     * Extracts a section of code into a new partial template and includes it.
     *
     * @param array $action
     * @return array
     */
    private function _extractPartial(array $action): array
    {
        $filePath = $action['file'];
        $target = $action['target'];
        $newPartial = $action['newPartial'];

        $fileService = Sidekick::$plugin->fileManagementService;

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

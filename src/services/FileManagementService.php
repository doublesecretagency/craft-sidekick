<?php

namespace doublesecretagency\sidekick\services;

use Craft;
use craft\helpers\FileHelper;
use yii\base\Component;
use yii\base\Exception;

/**
 * Class FileManagementService
 *
 * Handles file reading and writing operations for Sidekick.
 */
class FileManagementService extends Component
{
    /**
     * @var string The path to the project root.
     */
    private string $projectRootPath = '';

    /**
     * @var string The path to the templates directory.
     */
    private string $templatesPath = '';

    /**
     * Initializes the service.
     */
    public function init()
    {
        parent::init();

        // Set the project root path
        $this->projectRootPath = Craft::getAlias('@root');

        // Set the templates path
        $this->templatesPath = Craft::getAlias('@templates');
    }

    /**
     * Resolves a file path relative to the project root.
     *
     * @param string $filePath
     * @return string
     */
    public function resolveFilePath(string $filePath): string
    {
        // Remove leading slashes
        $filePath = ltrim($filePath, '/\\');

        // Resolve the absolute file path
        $absolutePath = $this->projectRootPath . DIRECTORY_SEPARATOR . $filePath;

        return $absolutePath;
    }

    /**
     * Reads the contents of a file.
     *
     * @param string $filePath The relative path to the file within the project.
     * @return string|null The file contents or null on failure.
     */
    public function readFile(string $filePath): ?string
    {
        try {
            // Sanitize the file path
            $filePath = $this->sanitizeFilePath($filePath);

            // Resolve the absolute file path
            $absolutePath = $this->resolveFilePath($filePath);

            // Ensure the path is allowed
            if (!$this->isPathAllowed($absolutePath)) {
                throw new Exception('Unauthorized file path.');
            }

            // Check if the file exists
            if (!file_exists($absolutePath)) {
                throw new Exception('File does not exist.');
            }

            // Read and return the file contents
            $content = file_get_contents($absolutePath);
            Craft::info("Successfully read file: {$filePath}", __METHOD__);
            return $content;

        } catch (\Exception $e) {
            Craft::error('Error reading file: ' . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    /**
     * Creates a new file with the given content.
     *
     * @param string $filePath The relative path to the file within the project.
     * @param string $content The content to write to the file.
     * @return bool True on success, false on failure.
     */
    public function createFile(string $filePath, string $content): bool
    {
        try {
            // Sanitize the file path
            $filePath = $this->sanitizeFilePath($filePath);

            // Resolve the absolute file path
            $absolutePath = $this->resolveFilePath($filePath);

            // Ensure the path is allowed
            if (!$this->isPathAllowed($absolutePath)) {
                throw new Exception('Unauthorized file path.');
            }

            // Check if the file already exists
            if (file_exists($absolutePath)) {
                throw new Exception('File already exists.');
            }

            // Ensure the directory exists
            $directory = dirname($absolutePath);
            if (!is_dir($directory)) {
                FileHelper::createDirectory($directory);
            }

            // Write the content to the file
            file_put_contents($absolutePath, $content);
            Craft::info("Successfully created file: {$filePath}", __METHOD__);
            return true;

        } catch (\Exception $e) {
            Craft::error('Error creating file: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Rewrites the content of an existing file.
     *
     * @param string $filePath The relative path to the file within the project.
     * @param string $content The new content to write to the file.
     * @return bool True on success, false on failure.
     */
    public function rewriteFile(string $filePath, string $content): bool
    {
        try {
            // Sanitize the file path
            $filePath = $this->sanitizeFilePath($filePath);

            // Resolve the absolute file path
            $absolutePath = $this->resolveFilePath($filePath);

            // Ensure the path is allowed
            if (!$this->isPathAllowed($absolutePath)) {
                throw new Exception('Unauthorized file path.');
            }

            // Check if the file exists
            if (!file_exists($absolutePath)) {
                throw new Exception('File does not exist.');
            }

            // Write the new content to the file
            file_put_contents($absolutePath, $content);
            Craft::info("Successfully updated file: {$filePath}", __METHOD__);
            return true;

        } catch (\Exception $e) {
            Craft::error('Error updating file: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Deletes a file.
     *
     * @param string $filePath The relative path to the file within the project.
     * @return bool True on success, false on failure.
     */
    public function deleteFile(string $filePath): bool
    {
        try {
            // Sanitize the file path
            $filePath = $this->sanitizeFilePath($filePath);

            // Resolve the absolute file path
            $absolutePath = $this->resolveFilePath($filePath);

            // Ensure the path is allowed
            if (!$this->isPathAllowed($absolutePath)) {
                throw new Exception('Unauthorized file path.');
            }

            // Check if the file exists
            if (!file_exists($absolutePath)) {
                throw new Exception('File does not exist.');
            }

            // Delete the file
            unlink($absolutePath);
            Craft::info("Successfully deleted file: {$filePath}", __METHOD__);
            return true;

        } catch (\Exception $e) {
            Craft::error('Error deleting file: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Lists all Twig templates within the /templates directory and its subdirectories.
     *
     * @return array An array of relative file paths.
     */
    public function listTwigTemplates(): array
    {
        try {
            $templatesDir = realpath($this->templatesPath);

            if ($templatesDir === false) {
                throw new Exception("Templates directory not found at {$this->templatesPath}.");
            }

            $files = FileHelper::findFiles($templatesDir, [
                'only' => ['*.twig'],
                'recursive' => true,
            ]);

            $relativeFiles = array_map(function ($absolutePath) use ($templatesDir) {
                return '/' . ltrim(str_replace($templatesDir, '', $absolutePath), '/\\');
            }, $files);

            Craft::info("Successfully listed Twig templates.", __METHOD__);
            return $relativeFiles;

        } catch (\Exception $e) {
            Craft::error('Error listing Twig templates: ' . $e->getMessage(), __METHOD__);
            return [];
        }
    }

    /**
     * Sanitizes the file path to prevent injection attacks.
     *
     * @param string $filePath
     * @return string
     */
    public function sanitizeFilePath(string $filePath): string
    {
        // Remove null bytes and directory traversal characters
        $filePath = str_replace(["\0", '..'], '', $filePath);

        // Normalize directory separators
        $filePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);

        return $filePath;
    }

    /**
     * Validates that the given path is within the allowed directories.
     *
     * @param string $absolutePath
     * @return bool
     */
    public function isPathAllowed(string $absolutePath): bool
    {
        // Only allow files within the templates directory
        $allowedDir = realpath($this->templatesPath);

        if ($allowedDir === false) {
            Craft::error("Templates directory not found at {$this->templatesPath}.", __METHOD__);
            return false;
        }

        $normalizedAllowedDir = realpath($allowedDir);
        $normalizedPath = realpath($absolutePath);

        if ($normalizedPath === false) {
            return false;
        }

        return strpos($normalizedPath, $normalizedAllowedDir) === 0;
    }

    /**
     * Validates that the file has a .twig extension.
     *
     * @param string $filePath
     * @return bool
     */
    public function isTwigFile(string $filePath): bool
    {
        return pathinfo($filePath, PATHINFO_EXTENSION) === 'twig';
    }
}

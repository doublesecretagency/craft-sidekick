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
     * @var string The path to the templates directory.
     */
    private $templatesPath = '';

    /**
     * Initializes the service.
     */
    public function init()
    {
        parent::init();

        // Set the templates path
        $this->templatesPath = Craft::getAlias('@templates');
    }

    /**
     * Reads the contents of a file.
     *
     * @param string $filePath The relative path to the file within the templates directory.
     * @return string|null The file contents or null on failure.
     */
    public function readFile(string $filePath): ?string
    {
        try {
            // Sanitize the file path
            $filePath = $this->sanitizeFilePath($filePath);

            // Resolve the absolute file path
            $absolutePath = $this->templatesPath . DIRECTORY_SEPARATOR . ltrim($filePath, '/\\');

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
     * Creates a new Twig file.
     *
     * @param string $filePath The relative path to the Twig file within the templates directory.
     * @param string $content The content to be written to the Twig file.
     * @return bool|string Returns true on success, or an error message string on failure.
     */
    public function createFile(string $filePath, string $content)
    {
        try {
            // Sanitize the file path
            $filePath = $this->sanitizeFilePath($filePath);

            // Resolve the absolute file path
            $absolutePath = $this->templatesPath . DIRECTORY_SEPARATOR . ltrim($filePath, '/\\');

            // Ensure the path is allowed
            if (!$this->isPathAllowed($absolutePath)) {
                throw new Exception('Unauthorized file path.');
            }

            // Ensure the file has a .twig extension
            if (!$this->isTwigFile($absolutePath)) {
                throw new Exception('Only .twig files are allowed.');
            }

            // Check if the file already exists
            if (file_exists($absolutePath)) {
                throw new Exception('File already exists.');
            }

            // Ensure the directory exists
            $directory = dirname($absolutePath);
            if (!is_dir($directory)) {
                FileHelper::createDirectory($directory);
                Craft::info("Created directory: {$directory}", __METHOD__);
            }

            // Validate content security
            if (!$this->isSecureContent($content)) {
                throw new Exception('Content contains prohibited elements.');
            }

            // Validate Twig syntax
            if (!$this->validateTwigSyntax($content)) {
                throw new Exception('Invalid Twig syntax.');
            }

            // Write content to the file
            file_put_contents($absolutePath, $content);
            Craft::info("Successfully created file: {$filePath}", __METHOD__);

            return true;
        } catch (\Exception $e) {
            Craft::error('Error creating file: ' . $e->getMessage(), __METHOD__);
            return 'Error creating file: ' . $e->getMessage();
        }
    }

    /**
     * Rewrites an existing Twig file.
     *
     * @param string $filePath The relative path to the Twig file within the templates directory.
     * @param string $newContent The new content to be written to the Twig file.
     * @return bool|string Returns true on success, or an error message string on failure.
     */
    public function rewriteFile(string $filePath, string $newContent)
    {
        try {
            // Sanitize the file path
            $filePath = $this->sanitizeFilePath($filePath);

            // Resolve the absolute file path
            $absolutePath = $this->templatesPath . DIRECTORY_SEPARATOR . ltrim($filePath, '/\\');

            // Ensure the path is allowed
            if (!$this->isPathAllowed($absolutePath)) {
                throw new Exception('Unauthorized file path.');
            }

            // Ensure the file has a .twig extension
            if (!$this->isTwigFile($absolutePath)) {
                throw new Exception('Only .twig files are allowed.');
            }

            // Check if the file exists
            if (!file_exists($absolutePath)) {
                throw new Exception('File does not exist.');
            }

            // Validate content security
            if (!$this->isSecureContent($newContent)) {
                throw new Exception('Content contains prohibited elements.');
            }

            // Validate Twig syntax
            if (!$this->validateTwigSyntax($newContent)) {
                throw new Exception('Invalid Twig syntax.');
            }

            // Replace file content
            file_put_contents($absolutePath, $newContent);
            Craft::info("Successfully rewritten file: {$filePath}", __METHOD__);

            return true;
        } catch (\Exception $e) {
            Craft::error('Error rewriting file: ' . $e->getMessage(), __METHOD__);
            return 'Error rewriting file: ' . $e->getMessage();
        }
    }

    /**
     * Deletes an existing Twig file.
     *
     * @param string $filePath The relative path to the Twig file within the templates directory.
     * @return bool|string Returns true on success, or an error message string on failure.
     */
    public function deleteFile(string $filePath)
    {
        try {
            // Sanitize the file path
            $filePath = $this->sanitizeFilePath($filePath);

            // Resolve the absolute file path
            $absolutePath = $this->templatesPath . DIRECTORY_SEPARATOR . ltrim($filePath, '/\\');

            // Ensure the path is allowed
            if (!$this->isPathAllowed($absolutePath)) {
                throw new Exception('Unauthorized file path.');
            }

            // Ensure the file has a .twig extension
            if (!$this->isTwigFile($absolutePath)) {
                throw new Exception('Only .twig files are allowed.');
            }

            // Check if the file exists
            if (!file_exists($absolutePath)) {
                throw new Exception('File does not exist.');
            }

            // Delete the file
            if (!unlink($absolutePath)) {
                throw new Exception('Failed to delete the file.');
            }

            Craft::info("Successfully deleted file: {$filePath}", __METHOD__);

            return true;
        } catch (\Exception $e) {
            Craft::error('Error deleting file: ' . $e->getMessage(), __METHOD__);
            return 'Error deleting file: ' . $e->getMessage();
        }
    }

    /**
     * Replaces the entire content of a file.
     *
     * @param string $filePath The relative path to the file within the templates directory.
     * @param string $newContent The new content to be written to the file.
     * @return bool|string Returns true on success, or an error message string on failure.
     */
    public function replaceFileContent(string $filePath, string $newContent): bool
    {
        return $this->rewriteFile($filePath, $newContent);
    }

    /**
     * Lists all Twig templates within the templates directory.
     *
     * @return array An array of relative paths to Twig files.
     */
    public function listTwigTemplates(): array
    {
        $templates = [];

        if ($this->templatesPath && is_dir($this->templatesPath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->templatesPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'twig') {
                    $relativePath = str_replace($this->templatesPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $templates[] = '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
                    Craft::info("Detected Twig file: /{$relativePath}", __METHOD__);
                }
            }
        } else {
            Craft::warning("Templates path not found or is not a directory: " . $this->templatesPath, __METHOD__);
        }

        return $templates;
    }

    /**
     * Validates that the given path is within the allowed directories.
     *
     * @param string $absolutePath
     * @return bool
     */
    private function isPathAllowed(string $absolutePath): bool
    {
        // Normalize paths to avoid discrepancies
        $normalizedAllowedDir = rtrim($this->templatesPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $normalizedPath = rtrim($absolutePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        return strpos($normalizedPath, $normalizedAllowedDir) === 0;
    }

    /**
     * Validates that the file has a .twig extension.
     *
     * @param string $filePath
     * @return bool
     */
    private function isTwigFile(string $filePath): bool
    {
        return pathinfo($filePath, PATHINFO_EXTENSION) === 'twig';
    }

    /**
     * Sanitizes the file path to prevent injection attacks.
     *
     * @param string $filePath
     * @return string
     */
    private function sanitizeFilePath(string $filePath): string
    {
        // Remove null bytes
        $filePath = str_replace("\0", '', $filePath);

        // Normalize directory separators
        $filePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);

        return $filePath;
    }

    /**
     * Validates and sanitizes content to prevent security issues.
     *
     * @param string $content
     * @return bool
     */
    private function isSecureContent(string $content): bool
    {
        // Prevent PHP tags
        if (preg_match('/<\?php/i', $content)) {
            Craft::error('PHP tags detected in Twig template.', __METHOD__);
            return false;
        }

        // Add more security checks as needed (e.g., prevent certain functions or tags)

        return true;
    }

    /**
     * Validates Twig syntax of a given content.
     *
     * @param string $content
     * @return bool
     */
    public function validateTwigSyntax(string $content): bool
    {
        try {
            // Utilize Twig's parser to validate syntax
            $twig = new \Twig\Environment(new \Twig\Loader\ArrayLoader());
            $twig->parse($twig->tokenize($content));
            Craft::info('Twig syntax validation passed.', __METHOD__);
            return true;
        } catch (\Twig\Error\SyntaxError $e) {
            Craft::error('Twig syntax error: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Generates a diff between two file contents.
     *
     * @param string $originalContent
     * @param string $newContent
     * @return string
     */
    public function generateDiff(string $originalContent, string $newContent): string
    {
        $diff = \Diff::compare($originalContent, $newContent);
        return $diff;
    }
}

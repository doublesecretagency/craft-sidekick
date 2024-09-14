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
     * Reads the contents of a file.
     *
     * @param string $filePath
     * @return string|null
     */
    public function readFile(string $filePath): ?string
    {
        try {
            // Ensure the file exists
            if (!file_exists($filePath)) {
                throw new Exception("File does not exist at path: $filePath");
            }

            // Return file contents
            return file_get_contents($filePath);

        } catch (\Exception $e) {
            Craft::error('Error reading file: ' . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    /**
     * Writes content to a file.
     *
     * @param string $filePath
     * @param string $content
     * @return bool
     */
    public function writeFile(string $filePath, string $content): bool
    {
        try {
            // Ensure the directory exists
            $directory = dirname($filePath);
            if (!is_dir($directory)) {
                FileHelper::createDirectory($directory);
            }

            // Write content to the file
            file_put_contents($filePath, $content);

            // Return success
            return true;

        } catch (\Exception $e) {
            Craft::error('Error writing to file: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Replaces the entire content of a file.
     *
     * @param string $filePath
     * @param string $newContent
     * @return bool
     */
    public function replaceFileContent(string $filePath, string $newContent): bool
    {
        // Perform the file replacement
        return $this->writeFile($filePath, $newContent);
    }

    /**
     * Retrieves metadata of a file.
     *
     * @param string $filePath
     * @return array|null
     */
    public function getFileMetadata(string $filePath): ?array
    {
        try {
            if (!file_exists($filePath)) {
                throw new Exception("File does not exist at path: $filePath");
            }

            return [
                'filename' => basename($filePath),
                'path' => realpath($filePath),
                'size' => filesize($filePath),
                'created_at' => filectime($filePath),
                'modified_at' => filemtime($filePath),
            ];

        } catch (\Exception $e) {
            Craft::error('Error retrieving file metadata: ' . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    /**
     * Lists all Twig templates within the templates directory.
     *
     * @return array
     */
    public function listTwigTemplates(): array
    {
        $templatesPath = realpath(CRAFT_TEMPLATES_PATH);
        $templates = [];

        if ($templatesPath && is_dir($templatesPath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($templatesPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'twig') {
                    $templates[] = $file->getPathname();
                }
            }
        }

        return $templates;
    }

    /**
     * Lists all PHP classes within the modules directory.
     *
     * @return array
     */
    public function listPhpClasses(): array
    {
        $modulesPath = Craft::getAlias('@modules');
        $classes = [];

        if ($modulesPath && is_dir($modulesPath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($modulesPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $classes[] = $file->getPathname();
                }
            }
        }

        return $classes;
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
            return true;
        } catch (\Twig\Error\SyntaxError $e) {
            Craft::error('Twig syntax error: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * @param string $content
     * @return bool
     */
    public function isSecureContent(string $content): bool
    {
        // Prevent PHP tags
        if (preg_match('/<\?php/i', $content)) {
            Craft::error('PHP tags detected in Twig template.', __METHOD__);
            return false;
        }

        // Add more security checks as needed

        return true;
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

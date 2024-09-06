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

}

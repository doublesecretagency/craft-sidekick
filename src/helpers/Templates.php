<?php

namespace doublesecretagency\sidekick\helpers;

use Craft;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Templates
{
    /**
     * Read the directory and file structure of the templates folder.
     *
     * Directory should always begin with `templates`.
     *
     * Use the following tabbed format when displaying the file structure:
     *
     * ```
     * templates
     * └── _layout
     *     ├── footer.twig
     *     └── header.twig
     * └── index.twig
     * ```
     *
     * @return array
     */
    public static function templatesStructure(): array
    {
        // Get the templates path
        $templatesPath = Craft::getAlias('@templates');

        // Get just the top directory of the templates folder
        $topDirectory = basename($templatesPath);

        // Regex pattern to match the templates path
        $pattern = '/^'.preg_quote($templatesPath, '/').'/';

        // Initialize the structure array
        $structure = [];

        // Recursive directory iterator to read the templates folder
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($templatesPath));

        // Loop through the files and directories
        foreach ($iterator as $file) {
            // Only consider files (ignore directories)
            if ($file->isFile()) {
                // Replace the templates path with just the top directory
                $relativePath = preg_replace($pattern, $topDirectory, $file->getPathname());
                // Add the relative path to the structure array
                $structure[] = $relativePath;
            }
        }

        // Return success message
        return [
            'success' => true,
//            'message' => implode("\n", $structure)
            'message' => "Understanding the structure of the templates directory.",
            'content' => implode("\n", $structure)
        ];
    }

    // ========================================================================= //

    /**
     * Create a new file with specified content.
     *
     * Directory should always begin with `templates`.
     *
     * @param string $directory Directory to create the file in.
     * @param string $file Name of the file to create.
     * @param string $content Content to include in the file.
     * @return array
     */
    public static function createFile(string $directory, string $file, string $content): array
    {
        // Parse the templates path
        $filePath = self::_parseTemplatesPath("{$directory}/{$file}");

        // If file already exists, return an error
        if (file_exists($filePath)) {
            return [
                'success' => false,
                'error' => "Unable to create file {$directory}/{$file} (already exists)."
            ];
        }

        // Create the file and write the content
        $bytesWritten = file_put_contents($filePath, $content);

        // If the file was successfully created
        if ($bytesWritten !== false) {
            // Return success message
            return [
                'success' => true,
//                'message' => $content
                'message' => "Successfully created {$directory}/{$file}",
                'content' => $content
            ];
        }

        // Something went wrong
        return [
            'success' => false,
            'error' => "Unable to create file {$directory}/{$file}."
        ];

    }

    /**
     * Read an existing file.
     *
     * Directory should always begin with `templates`.
     *
     * @param string $directory Directory to look in.
     * @param string $file Name of the file to read.
     * @return array
     */
    public static function readFile(string $directory, string $file): array
    {
        // Parse the templates path
        $filePath = self::_parseTemplatesPath("{$directory}/{$file}");

        // Check if the file exists
        if (file_exists($filePath)) {
            // Read the file content
            $content = file_get_contents($filePath);
            return [
                'success' => true,
//                'message' => $content
                'message' => "Successfully read {$directory}/{$file}",
                'content' => $content
            ];
        }

        // Something went wrong
        return [
            'success' => false,
            'error' => "Unable to read file {$directory}/{$file}."
        ];

    }

    /**
     * Update an existing file with specified content.
     *
     * Directory should always begin with `templates`.
     *
     * @param string $directory Directory where file currently exists.
     * @param string $file Name of the file to edit.
     * @param string $content Content to include in the file.
     * @return array
     */
    public static function updateFile(string $directory, string $file, string $content): array
    {
        // Parse the templates path
        $filePath = self::_parseTemplatesPath("{$directory}/{$file}");

        // Check if the file exists
        if (file_exists($filePath)) {
            // Write the new content to the file
            $bytesWritten = file_put_contents($filePath, $content);
            // If the file was successfully updated
            if ($bytesWritten === false) {
                return [
                    'success' => false,
                    'error' => "Unable to write to file {$directory}/{$file}."
                ];
            }
            // Return success message
            return [
                'success' => true,
//                'message' => $content
                'message' => "Successfully read {$directory}/{$file}",
                'content' => $content
            ];
        }

        // Something went wrong
        return [
            'success' => false,
            'error' => "Unable to edit file {$directory}/{$file}."
        ];

    }

    /**
     * Delete the specified file.
     *
     * Directory should always begin with `templates`.
     *
     * @param string $directory The directory of the file to be deleted.
     * @param string $file The name of the file to be deleted.
     * @return array
     */
    public static function deleteFile(string $directory, string $file): array
    {
        // Parse the templates path
        $filePath = self::_parseTemplatesPath("{$directory}/{$file}");

        // Check if the file exists
        if (file_exists($filePath)) {
            // Attempt to delete the file
            $deleted = unlink($filePath);
            // If the file was successfully deleted
            if ($deleted) {
                return [
                    'success' => true,
                    'message' => "Successfully deleted {$directory}/{$file}"
                ];
            }
        }

        // Something went wrong
        return [
            'success' => false,
            'error' => "Unable to delete file {$directory}/{$file}."
        ];

    }

    // ========================================================================= //

    /**
     * Parse the templates path.
     *
     * @param string $path
     * @return string
     */
    private static function _parseTemplatesPath(string $path): string
    {
        // Replace the `templates` prefix with the actual path
        return preg_replace(
            '/^templates/',
            Craft::getAlias('@templates'),
            $path
        );
    }
}

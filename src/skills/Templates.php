<?php

namespace doublesecretagency\sidekick\skills;

use Craft;
use doublesecretagency\sidekick\models\SkillResponse;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Templates
{
    /**
     * Read the directory and file structure of the templates folder. Eagerly call this if an understanding of the templates directory is required.
     *
     * Directory MUST ALWAYS begin with `templates`.
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
     * @return SkillResponse
     */
    public static function templatesStructure(): SkillResponse
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
            // Replace the templates path with just the top directory
            $relativePath = preg_replace($pattern, $topDirectory, $file->getPathname());
            // Add the relative path to the structure array
            $structure[] = $relativePath;
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed the structure of the templates directory.",
            'response' => implode("\n", $structure)
        ]);
    }

    // ========================================================================= //

    /**
     * Create a new directory.
     *
     * Directory MUST ALWAYS begin with `templates`.
     *
     * @param string $directory Directory to create the file in.
     * @return SkillResponse
     */
    public static function createDirectory(string $directory): SkillResponse
    {
        // Parse the templates path
        $path = self::_parseTemplatesPath($directory);

        // Get the directory path
        $directoryPath = dirname($path);

        // If the required directory already exists
        if (is_dir($directoryPath)) {
            return new SkillResponse([
                'success' => true,
                'message' => "The directory {$directory} already exists."
            ]);
        }

        // Attempt to create the directory (with check to ensure that it worked)
        if (!mkdir($directoryPath, 0755, true) && !is_dir($directoryPath)) {
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to create the directory {$directory}."
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Created the directory {$directory}."
        ]);
    }

    /**
     * Create a new file with specified content.
     *
     * Directory MUST ALWAYS begin with `templates`.
     *
     * @param string $directory Directory to create the file in.
     * @param string $file Name of the file to create.
     * @param string $content Content to include in the file.
     * @return SkillResponse
     */
    public static function createFile(string $directory, string $file, string $content): SkillResponse
    {
        // Parse the templates path
        $filePath = self::_parseTemplatesPath("{$directory}/{$file}");

        // If file already exists, return an error
        if (file_exists($filePath)) {
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to create file {$directory}/{$file} (already exists)."
            ]);
        }

        // Get the directory path
        $directoryPath = dirname($filePath);

        // If the required directory does not exist
        if (!is_dir($directoryPath)) {
            // Attempt to create the directory (with check to ensure that it worked)
            if (!mkdir($directoryPath, 0755, true) && !is_dir($directoryPath)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Unable to create the directory {$directory}."
                ]);
            }
        }

        // Create the file and write the content
        $bytesWritten = file_put_contents($filePath, $content);

        // If unable to create the file, return an error
        if ($bytesWritten === false) {
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to create file {$directory}/{$file}."
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Created {$directory}/{$file}",
            'response' => $content
        ]);
    }

    /**
     * Read an existing file.
     *
     * Directory MUST ALWAYS begin with `templates`.
     *
     * @param string $directory Directory to look in.
     * @param string $file Name of the file to read.
     * @return SkillResponse
     */
    public static function readFile(string $directory, string $file): SkillResponse
    {
        // Parse the templates path
        $filePath = self::_parseTemplatesPath("{$directory}/{$file}");

        // If file doesn't exist, return an error
        if (!file_exists($filePath)) {
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to read file, {$directory}/{$file} does not exist."
            ]);
        }

        // Read the file content
        $content = file_get_contents($filePath);
        return new SkillResponse([
            'success' => true,
            'message' => "Read {$directory}/{$file}",
            'response' => $content
        ]);
    }

    /**
     * Update an existing file with specified content.
     *
     * Directory MUST ALWAYS begin with `templates`.
     *
     * For large updates, ask for confirmation before proceeding.
     *
     * @param string $directory Directory where file currently exists.
     * @param string $file Name of the file to edit.
     * @param string $content Content to include in the file.
     * @return SkillResponse
     */
    public static function updateFile(string $directory, string $file, string $content): SkillResponse
    {
        // Parse the templates path
        $filePath = self::_parseTemplatesPath("{$directory}/{$file}");

        // If the file doesn't exist, return an error
        if (!file_exists($filePath)) {
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to update file, {$directory}/{$file} does not exist."
            ]);
        }

        // If not a file, return an error
        if (!is_file($filePath)) {
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to update, {$directory}/{$file} is not a file."
            ]);
        }

        // Write the new content to the file
        $bytesWritten = file_put_contents($filePath, $content);

        // If unable to update the file, return an error
        if ($bytesWritten === false) {
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to write to file {$directory}/{$file}."
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Updated {$directory}/{$file}",
            'response' => $content
        ]);
    }

    /**
     * Delete the specified file.
     *
     * Empty directories cannot be deleted. When deleting both files and directories, the files must be deleted first.
     *
     * Directory MUST ALWAYS begin with `templates`.
     *
     * If the user doesn't explicitly say "delete", ask for confirmation before proceeding.
     *
     * @param string $directory The directory of the file to be deleted.
     * @param string $file The name of the file to be deleted.
     * @return SkillResponse
     */
    public static function deleteFile(string $directory, string $file): SkillResponse
    {
        // Parse the templates path
        $filePath = self::_parseTemplatesPath("{$directory}/{$file}");

        // If file does not exist, return an error
        if (!file_exists($filePath)) {
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to delete, no existing file {$directory}/{$file}"
            ]);
        }

        // If not a file, return an error
        if (!is_file($filePath)) {
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to delete, {$directory}/{$file} is not a file."
            ]);
        }

        // If unable to delete the file, return an error
        if (!unlink($filePath)) {
            return new SkillResponse([
                'success' => false,
                'message' => "Failed to delete file {$directory}/{$file}"
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Deleted {$directory}/{$file}"
        ]);
    }

    /**
     * Delete the specified directory.
     *
     * Empty directories cannot be deleted. When deleting both files and directories, the files must be deleted first.
     *
     * Directory MUST ALWAYS begin with `templates`.
     *
     * If the user doesn't explicitly say "delete", ask for confirmation before proceeding.
     *
     * @param string $directory The directory to be deleted.
     * @return SkillResponse
     */
    public static function deleteDirectory(string $directory): SkillResponse
    {
        // Parse the templates path
        $path = self::_parseTemplatesPath($directory);

        // If directory does not exist, return an error
        if (!file_exists($path)) {
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to delete, no existing directory {$directory}"
            ]);
        }

        // If not a directory, return an error
        if (!is_dir($path)) {
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to delete, {$directory} is not a directory."
            ]);
        }

        // Check if the directory is empty
        $contents = scandir($path); // Returns '.' and '..' even for an empty directory
        $isEmpty = count($contents) === 2; // Only '.' and '..' are present

        // If the directory is not empty, return an error
        if (!$isEmpty) {
            return new SkillResponse([
                'success' => false,
                'message' => "Cannot delete, directory {$directory} is not empty."
            ]);
        }

        // If unable to delete the directory, return an error
        if (!rmdir($path)) {
            return new SkillResponse([
                'success' => false,
                'message' => "Failed to delete directory {$directory}"
            ]);
        }

        // Return success message
        return new SkillResponse([
            'success' => true,
            'message' => "Deleted {$directory}"
        ]);
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
        // Trim leading slashes from the path
        $path = ltrim($path, '/');

        // Replace the `templates` prefix with the actual path
        return preg_replace(
            '/^templates/',
            Craft::getAlias('@templates'),
            $path
        );
    }
}

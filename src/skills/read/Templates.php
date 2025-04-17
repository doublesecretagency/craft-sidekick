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

namespace doublesecretagency\sidekick\skills\read;

use Craft;
use doublesecretagency\sidekick\helpers\TemplatesHelper;
use doublesecretagency\sidekick\models\SkillResponse;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * @category Templates
 */
class Templates
{
    /**
     * Read the directory and file structure of the templates folder.
     *
     * Directory MUST ALWAYS begin with `templates`.
     *
     * If you are unfamiliar with the existing structure, you MUST call this tool before creating, reading, updating, or deleting files.
     * Eagerly call this if an understanding of the templates directory is required.
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
    public static function getTemplatesFolderStructure(): SkillResponse
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
        $filePath = TemplatesHelper::parseTemplatesPath("{$directory}/{$file}");

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
}

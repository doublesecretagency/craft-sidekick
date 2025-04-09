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

namespace doublesecretagency\sidekick\helpers;

use Craft;
use craft\helpers\Json;

class SystemPrompt
{
    /**
     * @var array The list of system prompt files to compile.
     */
    private static array $systemPromptFiles = [
        'instructions.md',
    ];

    /**
     * Compiles the system prompt from a collection of Markdown files.
     */
    public static function getPrompt(): string
    {
        // Initialize system prompt
        $systemPrompt = '';

        // Get the path to the Sidekick plugin
        $path = Craft::getAlias('@doublesecretagency/sidekick');

        /**
         * IMPORTANT:
         *
         * To maximize AI caching,
         * make sure to put the
         * STATIC content FIRST, and
         * the DYNAMIC content LAST.
         */

        // Loop through each prompt file
        foreach (static::$systemPromptFiles as $file) {

            // Load the content of each prompt file
            $filePath = "{$path}/prompts/{$file}";

            // Ensure the file exists
            if (file_exists($filePath)) {
                // Load the file content
                $content = file_get_contents($filePath);
                // Ensure there's a line break between sections
                $systemPrompt .= "{$content}\n\n";
            } else {
                // Handle missing files if necessary
                Craft::warning("Prompt file not found: {$filePath}", __METHOD__);
            }

        }

        // If no prompt content was loaded, throw an exception
        if (!$systemPrompt) {
            $error = "Unable to compile the system prompt.";
            Craft::error($error, __METHOD__);
        }

        // Get the relevant system data
        $data = static::_getSystemData();

        // Append data unique to this system
        $systemPrompt .= "\n\n# Craft CMS System Configuration\n\n{$data}";

        // Log that the system prompt has been compiled
        Craft::info("Compiled system prompt.", __METHOD__);

        // Return the compiled system prompt
        return $systemPrompt;
    }

    /**
     * Appends relevant system data to the prompt.
     *
     * @return string
     */
    private static function _getSystemData(): string
    {
        // Get the general config settings
        $generalConfig = Craft::$app->getConfig()->general;

        // Relevant system data
        $data = [
            'Craft CMS version' => Craft::$app->getVersion(),
            'Craft CMS edition' => Craft::$app->getEdition(),
            'PHP version' => PHP_VERSION,
            'General Config' => [
                'aliases' => $generalConfig->aliases,
                'allowAdminChanges' => $generalConfig->allowAdminChanges,
            ]
        ];

        // Return JSON encoded system data
        return Json::encode($data);
    }
}

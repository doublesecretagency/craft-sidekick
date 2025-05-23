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
use doublesecretagency\sidekick\Sidekick;

class SystemPrompt
{
    /**
     * Compiles the system prompt from a collection of Markdown files.
     */
    public static function getPrompt(): string
    {
        /**
         * IMPORTANT:
         *
         * To maximize AI caching,
         * make sure to put the
         * STATIC content FIRST, and
         * the DYNAMIC content LAST.
         */

        // Get all prompts (native and custom)
        $promptFiles = Sidekick::getInstance()?->getPrompts();

        // Initialize system prompt
        $systemPrompt = '';

        // Loop through each prompt file
        foreach ($promptFiles as $filePath) {

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

        // Append the namespace hashes
        $systemPrompt .= static::_namespaceHashes();

        // Append the system data
        $systemPrompt .= static::_systemData();

        // Log that the system prompt has been compiled
        Craft::info("Compiled system prompt.", __METHOD__);

        // Return the compiled system prompt
        return $systemPrompt;
    }

    // ========================================================================= //

    /**
     * Append namespace hashes to the system prompt.
     *
     * @return string
     */
    private static function _namespaceHashes(): string
    {
        // Get the namespace hashes from the OpenAI instance
        $hashes = Sidekick::getInstance()?->openAi->skillSetsHash;

        // Json encode the hashes
        $hashes = Json::encode($hashes);

        // Return JSON encoded system data
        return <<<MARKDOWN

# Namespace Hash Array

To perform the translations, here is a complete mapping of the namespace hashes for all available tools:

{$hashes}

MARKDOWN;
    }

    /**
     * Append relevant system data to the prompt.
     *
     * @return string
     */
    private static function _systemData(): string
    {
        // Get the general config settings
        $generalConfig = Craft::$app->getConfig()->general;

        // Relevant system data
        $data = Json::encode([
            'Craft CMS version' => Craft::$app->getVersion(),
            'Craft CMS edition' => Craft::$app->getEdition(),
            'PHP version' => PHP_VERSION,
            'General Config' => [
                'aliases' => $generalConfig->aliases,
                'allowAdminChanges' => $generalConfig->allowAdminChanges,
            ]
        ]);

        // Return JSON encoded system data
return <<<MARKDOWN

# Craft System Configuration

The following data represents the current Craft CMS system configuration:

{$data}

MARKDOWN;
    }
}

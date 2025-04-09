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
        $hashes = Sidekick::getInstance()?->openAi->skillsHash;

        // Json encode the hashes
        $hashes = Json::encode($hashes);

        // Return JSON encoded system data
        return <<<MARKDOWN

# Namespace Hashes

The prefix of each tool function is a short hash, which **directly translates** to a class namespace.

You will obviously execute the tool by calling its correct tool name,
but otherwise feel free to reference the complete (accurate) namespace, class name, and method name
in communications with the user.

When referencing a namespace, you MUST include the **complete namespace** (including ALL path segments).

```
{hash}-{ClassName}-{MethodName}
# ... can be converted to ...
{namespace}\{ClassName}::{MethodName}
```

Here is a practical example:

```
# BEFORE: Original tool function name
41ff3f-Templates-templatesStructure

# AFTER: Namespaced path to actual class method
doublesecretagency\sidekick\skills\read\Templates::templatesStructure
```

You MUST ensure consistency between the hash key and its corresponding namespace.

To perform the translations, here are the namespace hashes for all available tools:

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

# Craft CMS System Configuration

The following data represents the current system configuration of Craft CMS:

{$data}

MARKDOWN;
    }
}

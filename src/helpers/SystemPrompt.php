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

Each tool name follows a specific formula, ie:
```
41ff3f-Templates-templatesStructure
```

And each of those tool functions maps directly to an underlying PHP class method, ie:
```
doublesecretagency\sidekick\skills\read\Templates::templatesStructure
```

The prefix of each tool function is a short hash, which is **directly equivalent** to an underlying PHP class namespace.

```
{hash} == {namespace}
```

When compiled with its respective `{ClassName}` and `{MethodName}`, each format looks like this:

```
# TOOL FUNCTION NAME: The tool function name to use for the AI assistant to perform tasks.
{hash}-{ClassName}-{MethodName}

# UNDERLYING PHP CLASS: The fully namespaced path to the underlying PHP class method.
{namespace}\{ClassName}::{MethodName}
```

For a complete mapping of `{hash}` to `{namespace}`, see the namespace hash array below.

You MUST ensure consistency between the hash key and its corresponding namespace.

## Tool use context

When calling a tool, you MUST use this format: `{hash}-{ClassName}-{MethodName}`

## PHP class context

When investigating a PHP class (perhaps while debugging), or when describing a PHP class to a user, you MUST use this format: `{namespace}\{ClassName}::{MethodName}`

In a PHP class context, you MUST include the **complete namespace** (including ALL path segments).

# Hash => namespace translations mapping

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

# Craft CMS System Configuration

The following data represents the current system configuration of Craft CMS:

{$data}

MARKDOWN;
    }
}

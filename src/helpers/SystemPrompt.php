<?php

namespace doublesecretagency\sidekick\helpers;

use Craft;
use doublesecretagency\sidekick\Sidekick;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;

class SystemPrompt
{
    /**
     * @var array The list of system prompt files to compile.
     */
    private static array $systemPromptFiles = [
        'introduction.md',
        'general-guidelines.md',
//        'formatting-style.md',
//        'security-compliance.md',
//        'examples.md',
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

        // Append the actions documentation to the system prompt
//        $systemPrompt = static::_appendActionsDocs($systemPrompt);

        // Log that the system prompt has been compiled
        Craft::info("Compiled system prompt.", __METHOD__);

        // Return the compiled system prompt
        return $systemPrompt;
    }

    /**
     * Appends the actions documentation to the system prompt.
     *
     * @param string $systemPrompt
     * @return string
     */
    private static function _appendActionsDocs(string $systemPrompt): string
    {
        // Get the actions service
        $actionsService = Sidekick::$plugin->actions;

        // Get all methods from the ActionsHelper class
        $methods = (new ReflectionClass(ApiTools::class))->getMethods();

        // Create a new instance of the DocBlockFactory
        $docFactory = DocBlockFactory::createInstance();

        // Get the path to the Sidekick plugin
        $path = Craft::getAlias('@doublesecretagency/sidekick');

        // Load the content of the actions documentation
        $filePath = "{$path}/prompts/actions.md";

        // If the file doesn't exist, bail
        if (!file_exists($filePath)) {
            Craft::error('Unable to find Markdown prompt for Actions.', __METHOD__);
            return '';
        }

        // Load the actions documentation
        $actionsDocumentation = file_get_contents($filePath);

        // Initialize the actions documentation
        $listOfActions = '';

        // Loop through each method
        foreach ($methods as $method) {

            // Get the method name
            $action = $method->getName();

            // Skip methods that aren't valid actions
            if (!in_array($action, $actionsService->getValidActions(), true)) {
                continue;
            }

            // Get the method's doc comment
            $docComment = $method->getDocComment();

            // If no doc comment is present, skip this method
            if (!$docComment) {
                continue;
            }

            // Create a new DocBlock instance
            $docBlock = $docFactory->create($docComment);

            // Get the summary and description
            $summary = $docBlock->getSummary();
            $description = $docBlock->getDescription();

            // Append the action documentation to the system prompt
            $listOfActions .= "\n{$summary}\n\n{$description}\n";
        }

        // Replace the placeholder with the actions documentation
        $actionsDocumentation = str_replace('{listOfActions}', $listOfActions, $actionsDocumentation);

        // Return the updated system prompt
        return $systemPrompt.$actionsDocumentation;
    }
}

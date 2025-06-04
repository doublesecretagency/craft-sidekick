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

use craft\helpers\StringHelper;
use doublesecretagency\sidekick\Sidekick;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use ReflectionException;

class SkillsHelper
{
    /**
     * Get list of available skills to be displayed in the slideout.
     *
     * @return array
     */
    public static function slideoutSkillsList(): array
    {
        // Initialize skill sets
        $skillSets = [];

        // Create a new instance of the DocBlockFactory
        $docFactory = DocBlockFactory::createInstance();

        // Loop through each tool class
        foreach (Sidekick::getInstance()?->getSkills() as $skill) {

            // Defaults to uncategorized
            $category = 'Uncategorized';

            // Attempt to get the actual category
            try {

                // Get available tool functions
                $toolFunctions = (new $skill())->getToolFunctions();

                // Get reflection class object
                $reflection = new ReflectionClass($skill);

                // Get the class's docblock
                $classDocsComment = $reflection->getDocComment();

                // If the class has a docblock
                if ($classDocsComment) {

                    // Get the method's docblock
                    $classDocs = $docFactory->create($classDocsComment);

                    // Get the @category value of the class
                    $categories = $classDocs->getTagsByName('category');

                    // If any category tags exist
                    if ($categories) {
                        // Get only the first category
                        $category = $categories[0]->getDescription()->render();
                    }

                }

            } catch (ReflectionException $e) {

                // Something went wrong, skip to the next one
                continue;

            }

            // Loop through each tool function
            foreach ($toolFunctions as $toolFunction) {

                // Get the method's docblock
                $docBlock = $docFactory->create($toolFunction->getDocComment());

                // Get the method name
                $method = $toolFunction->getName();

                // Convert camelCase $method to normal Title Caps
                $name = StringHelper::toPascalCase($method);
                $name = implode(' ', StringHelper::toWords($name));

                // If category array does not yet exist, initialize it
                if (!isset($skillSets[$category])) {
                    $skillSets[$category] = [];
                }

                // Configure the skill info
                $skillSets[$category][] = [
                    'fullPath' => "$skill::$method",
                    'name' => $name,
                    'description' => $docBlock->getSummary()
                ];

            }

        }

        // Return the skill sets
        return $skillSets;
    }

    // ========================================================================= //

    /**
     * Convert a nested array to CSV format.
     *
     * @param array $data
     * @return string
     */
    public static function toCsv(array $data): string
    {
        // If no data, return an empty string
        if (!$data) {
            return '';
        }

        // Get CSV headers from the first row's keys
        $headers = array_keys($data[0]);

        // Initialize CSV output
        $csvOutput = '';

        // Build header row
        $csvHeaders = array_map(static function ($header) {
            return '"'.addslashes($header).'"';
        }, $headers);

        // Append headers to CSV output
        $csvOutput .= implode(',', $csvHeaders) . "\n";

        // Process each row
        foreach ($data as $row) {

            // Initialize values
            $values = [];

            // Loop through each header
            foreach ($headers as $header) {
                // Get the corresponding value from the row
                $value = (isset($row[$header]) ? addslashes($row[$header]) : '');
                // Escape the value and add it to the values array
                $values[] = '"'.$value.'"';
            }

            // Append the row values to the CSV output
            $csvOutput .= implode(',', $values) . "\n";

        }

        // Return the complete CSV output
        return $csvOutput;
    }
}

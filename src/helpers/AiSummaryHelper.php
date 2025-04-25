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

use craft\base\Element;
use doublesecretagency\sidekick\fields\AiSummary;
use doublesecretagency\sidekick\Sidekick;
use Exception;

class AiSummaryHelper
{
    /**
     * Parse all AI summaries of the element.
     *
     * @param Element $element
     * @return array
     */
    public static function parseElement(Element $element): array
    {
        // Initialize the content array
        $content = [];

        // Get the field layout of the element
        $fieldLayout = $element->getFieldLayout();

        // If no field layout, return empty array
        if (!$fieldLayout) {
            return $content;
        }

        // Loop through all custom fields in the field layout
        foreach ($fieldLayout->getCustomFields() as $field) {

            // If not an AI Summary field, skip it
            if (!$field instanceof AiSummary) {
                continue;
            }

            // If not generating on save, skip it
            if (!$field->generateOnSave) {
                continue;
            }

            // Parse the new field value
            $content[$field->handle] = self::parseField($field, $element, $field->forceRegeneration);

        }

        // Return the complete content array
        return $content;
    }

    /**
     * Parses field for a given element.
     *
     * @param AiSummary $field
     * @param Element $element
     * @param bool $force
     * @return string|null
     */
    public static function parseField(AiSummary $field, Element $element, bool $force = false): ?string
    {
        try {

            // Get the existing field value
            $fieldValue = $element->getFieldValue($field->handle);

            // If the field value already exists (and not forcing it), return it
            if ($fieldValue && !$force) {
                return $fieldValue;
            }

            // Get the summary instructions
            $instructions = $field->summaryInstructions;

            // If the instructions are empty, return null
            if (!$instructions) {
                return null;
            }

            // Summarize the element according to the given instructions
            return Sidekick::getInstance()?->openAi->summarizeElement($element, $instructions);

        } catch (Exception $exception) {

            // Something went wrong, return null
            return null;

        }
    }
}

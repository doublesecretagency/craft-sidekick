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

use craft\base\ElementInterface;
use craft\helpers\Json;
use Exception;
use RuntimeException;

class ElementsHelper
{
    /**
     * Populates an element with the given attributes/custom field values.
     *
     * @param ElementInterface $element
     * @param array|string $data
     */
    public static function populateElement(ElementInterface $element, array|string $data): void
    {
        // If not an array, decode the JSON string
        if (!is_array($data)) {
            $data = Json::decodeIfJson($data);
        }

        // If still not an array, bail
        if (!is_array($data)) {
            return;
        }

        // Set core attributes
        foreach (($data['attributes'] ?? []) as $name => $value) {

            // List of date fields
            $dateFields = [
                'dateCreated',
                'dateUpdated',
                'postDate',
                'expiryDate',
            ];

            // If the attribute is a date field
            if (in_array($name, $dateFields, true)) {

                try {
                    // Convert valid formats to a DateTime object
                    $element->$name = new \DateTime($value);
                } catch (Exception $e) {
                    // If the date format is invalid, throw an exception
                    throw new RuntimeException("Invalid date format for `{$name}`: {$value}");
                }

            } else {

                // By default, set the attribute directly on the element
                $element->$name = $value;

            }

        }

        // Set custom fields values
        $element->setFieldValues($data['fields'] ?? []);
    }
}

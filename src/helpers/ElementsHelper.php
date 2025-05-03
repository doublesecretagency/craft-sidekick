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
            $element->$name = $value;
        }

        // Set custom fields values
        $element->setFieldValues($data['fields'] ?? []);
    }
}

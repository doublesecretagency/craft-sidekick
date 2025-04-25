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

namespace doublesecretagency\sidekick\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;

class AiSummary extends Field implements PreviewableFieldInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'AI Summary');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): string
    {
        return 'sparkles';
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return 'string|null';
    }

    /**
     * @var string|null Instructions for summarizing an element.
     */
    public ?string $summaryInstructions = null;

    /**
     * @var string The mode of the field. [editable|readOnly|disabled]
     */
    public string $fieldMode = 'editable';

    /**
     * @var int The minimum number of rows the input should have.
     */
    public int $initialRows = 4;

    /**
     * @var bool Whether to regenerate the summary when an element is saved.
     */
    public bool $generateOnSave = false;

    /**
     * @var bool Whether to force a regeneration if a value already exists.
     */
    public bool $forceRegeneration = false;

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        // Render the settings template
        return Craft::$app->getView()->renderTemplate('sidekick/fieldtypes/AiSummary/settings', [
            'field' => $this,
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element = null, bool $inline = false): string
    {
        // Render the input template
        return Craft::$app->getView()->renderTemplate('sidekick/fieldtypes/AiSummary/input', [
            'value' => $value,
            'field' => $this,
            'element' => $element,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml(mixed $value, ElementInterface $element): string
    {
        try {

            // Get the field value
            $fieldValue = ($element->getFieldValue($this->handle) ?? '');

            // Convert line breaks to <br> tags
            $fieldValue = nl2br($fieldValue);

            // Render the field value
            return Craft::$app->getView()->renderString($fieldValue);

        } catch (\Throwable $e) {

            // Something went wrong, return an empty string
            return '';

        }
    }
}

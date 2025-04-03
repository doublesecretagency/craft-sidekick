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

namespace doublesecretagency\sidekick\models;

use craft\base\Model;

/**
 * Class Settings
 *
 * This model defines the settings for the Sidekick plugin.
 */
class Settings extends Model
{
    /**
     * @var string OpenAI API Key.
     */
    public string $openAiApiKey = '';

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['openAiApiKey'], 'string'],
            [['openAiApiKey'], 'required'],
        ];
    }
}

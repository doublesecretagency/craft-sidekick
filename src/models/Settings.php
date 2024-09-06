<?php

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

}

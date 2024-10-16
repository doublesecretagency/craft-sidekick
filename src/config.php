<?php

/**
 * Sidekick config.php
 *
 * This file exists only as a template for the Sidekick settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'config' as 'sidekick.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

use craft\helpers\App;

return [
    'aiModel' => App::env('AI_MODEL'),
    'openAiApiKey' => App::env('OPENAI_API_KEY')
];

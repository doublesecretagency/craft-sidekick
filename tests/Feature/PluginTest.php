<?php

use doublesecretagency\sidekick\Sidekick;
use doublesecretagency\sidekick\services\ActionsService;
use doublesecretagency\sidekick\services\FileManagementService;
use doublesecretagency\sidekick\services\OpenAIService;
use markhuot\craftpest\test\TestCase;

uses(TestCase::class);

test('Plugin initializes correctly', function () {
    // Access the plugin instance via Craft's plugin service
    $plugin = Sidekick::getInstance();

    // Expect plugin to be an instance of the Sidekick class
    expect($plugin)->toBeInstanceOf(Sidekick::class);

    // Expect the plugin's components to be set
    expect($plugin->actions)->toBeInstanceOf(ActionsService::class);
    expect($plugin->fileManagement)->toBeInstanceOf(FileManagementService::class);
    expect($plugin->openAi)->toBeInstanceOf(OpenAIService::class);
});

test('Plugin settings are accessible', function () {
    $plugin = Sidekick::getInstance();
    $settings = $plugin->getSettings();

    // Expect the OpenAI API key to be set (in a real test, you might mock this)
    expect($settings->openAiApiKey)->not->toBeEmpty();
});

test('Control panel navigation item is correct', function () {
    $plugin = Sidekick::getInstance();
    $navItem = $plugin->getCpNavItem();

    expect($navItem['label'])->toBe('Sidekick');
    expect($navItem['url'])->toBe('sidekick/chat');
});

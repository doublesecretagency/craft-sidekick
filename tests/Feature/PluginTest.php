<?php

use doublesecretagency\sidekick\Sidekick;
use markhuot\craftpest\test\TestCase;

uses(TestCase::class);

test('Initializes the plugin correctly', function () {
    // Access the plugin instance via Craft's plugin service
    $plugin = Sidekick::getInstance();
    // Expect plugin to be an instance of the Sidekick class
    expect($plugin)->toBeInstanceOf(Sidekick::class);
});

<?php

use doublesecretagency\sidekick\Sidekick;
use markhuot\craftpest\test\TestCase;

uses(TestCase::class);

test('Retrieves valid actions from ActionsService', function () {
    // Get valid actions
    $validActions = Sidekick::getInstance()->actions->getValidActions();
    // Expect valid actions to be a non-empty array
    expect($validActions)->toBeArray()->and($validActions)->not->toBeEmpty();
});

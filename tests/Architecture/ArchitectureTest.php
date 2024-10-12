<?php

use markhuot\craftpest\test\TestCase;

uses(TestCase::class);

// https://github.com/markhuot/craft-pest/issues/102#issuecomment-2079694213
test('Triggers pointless Yii warning')
    ->expect(true)
    ->toBeTrue();

test('Source code does not contain any `Craft::dd` statements')
    ->expect(Craft::class)
    ->not->toUse(['dd']);

test('Source code does not contain any `var_dump` or `die` statements')
    ->expect(['var_dump', 'die'])
    ->not->toBeUsed();

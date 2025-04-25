<?php

use doublesecretagency\sidekick\services\FileManagementService;
use doublesecretagency\sidekick\Sidekick;
use doublesecretagency\sidekick\skills\read\Templates;
use markhuot\craftpest\test\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->actionsService = Sidekick::getInstance()?->actions;

    // Create a mock for the FileManagementService
    $mockFileService = Mockery::mock(FileManagementService::class);

    // Mock the fileExists method to return false (file doesn't exist)
    $mockFileService->shouldReceive('fileExists')->andReturn(false);

    // Mock the writeFile method to return true (file write succeeds)
    $mockFileService->shouldReceive('writeFile')->andReturn(true);

    // Inject the mocked service into the plugin
    Sidekick::getInstance()?->set('fileManagement', $mockFileService);
});

test('Retrieves valid actions from ActionsService', function () {
    // Get valid actions
    $validActions = $this->actionsService->getValidActions();

    // Expect valid actions to be a non-empty array
    expect($validActions)->toBeArray()->not->toBeEmpty();

    // Expect specific actions to be present
    expect($validActions)->toContain('createFile', 'deleteFile', 'updateElement');
});

test('Executes a valid action successfully', function () {
    $action = [
        'action' => 'createFile',
        'file' => '/templates/test.twig',
        'content' => '<h1>Test Template</h1>',
    ];

    $result = Templates::createFile($action);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toBe("File '/templates/test.twig' created successfully.");
});

test('Fails to execute an invalid action', function () {
    $action = [
        'action' => 'nonExistentAction',
        'file' => '/templates/test.twig',
    ];

    $result = $this->actionsService->executeActions([$action]);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe("Unsupported action: nonExistentAction");
});

test('Handles missing action type gracefully', function () {
    $action = [
        'file' => '/templates/test.twig',
    ];

    $result = $this->actionsService->executeActions([$action]);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe("Action type is missing.");
});

test('Displays file contents successfully', function () {
    // Mock the file content
    $mockContent = '<h1>Test Template</h1>';

    // Mock the FileManagementService
    $mockFileService = Mockery::mock(FileManagementService::class);
    $mockFileService->shouldReceive('readFile')->andReturn($mockContent);

    // Inject the mocked service
    Sidekick::getInstance()?->set('fileManagement', $mockFileService);

    // Prepare the action
    $action = [
        'action' => 'displayFileContents',
        'file' => '/templates/test.twig',
    ];

    // Execute the action
    $result = Templates::displayFileContents($action);

    // Assertions
    expect($result['success'])->toBeTrue();
    expect($result['message'])->toBe("Contents of '/templates/test.twig':");
    expect($result['content'])->toBe($mockContent);
});

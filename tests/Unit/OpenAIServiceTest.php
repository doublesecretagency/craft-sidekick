<?php

use craft\helpers\App;
use doublesecretagency\sidekick\services\OpenAIService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use markhuot\craftpest\test\TestCase;

uses(TestCase::class);

beforeEach(function () {
    // Mock the GuzzleHttp Client
    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('post')->andReturn(
        new Response(200, [], json_encode([
            'choices' => [
                [
                    'message' => [
                        'content' => 'Test response from OpenAI.'
                    ]
                ]
            ]
        ]))
    );

    // Create a new instance of OpenAIService
    $this->openAIService = new OpenAIService();

    // Use Reflection to set the private $apiKey property
    $reflectionClass = new ReflectionClass($this->openAIService);

    // Set the private $apiKey property
    $apiKeyProperty = $reflectionClass->getProperty('apiKey');
    $apiKeyProperty->setAccessible(true);
    $apiKeyProperty->setValue($this->openAIService, 'test-api-key');

    // Set the private $systemPrompt property
    $systemPromptProperty = $reflectionClass->getProperty('systemPrompt');
    $systemPromptProperty->setAccessible(true);
    $systemPromptProperty->setValue($this->openAIService, 'Test system prompt');

    // Set the mocked HTTP client
    $this->openAIService->setHttpClient($mockClient);
});

test('OpenAIService can retrieve the system prompt', function () {
    $prompt = $this->openAIService->getSystemPrompt();

    expect($prompt)->toBeString()->not->toBeEmpty();
});

test('OpenAIService handles API response correctly', function () {
    $apiRequest = [
        'model' => 'gpt-4',
        'messages' => [
            ['role' => 'user', 'content' => 'Hello, assistant!'],
        ],
    ];

    $response = $this->openAIService->callChatCompletion($apiRequest);

    expect($response['success'])->toBeTrue();
    expect($response['results'])->toBe('Test response from OpenAI.');
});

// Clean up Mockery after tests
afterEach(function () {
    Mockery::close();
});

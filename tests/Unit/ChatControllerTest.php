<?php
//
//use doublesecretagency\sidekick\controllers\ChatController;
//use doublesecretagency\sidekick\Sidekick;
//use markhuot\craftpest\test\TestCase;
//use yii\web\Request;
//use yii\web\Response;
//use yii\web\Session;
//
//uses(TestCase::class);
//
//test('ChatController processes assistant JSON responses correctly', function () {
//    // Mock the assistant's message containing JSON actions
//    $assistantMessage = <<<JSON
//```json
//{
//    "actions": [
//        {
//            "action": "displayFileContents",
//            "file": "/templates/index.twig"
//        }
//    ]
//}
//```
//JSON;
//
//    // Mock the Request component
//    $request = Mockery::mock(Request::class);
//
//    // Mock methods called in the controller
//    $request->shouldReceive('getRequiredBodyParam')
//        ->with('message')
//        ->andReturn('show me `index.twig`');
//    $request->shouldReceive('getBodyParam')
//        ->with('greeting')
//        ->andReturn(null);
//    $request->shouldReceive('getIsLivePreview')
//        ->andReturn(false);
//    $request->shouldReceive('getMethod')
//        ->andReturn('POST');
//    $request->shouldReceive('getAcceptsJson')
//        ->andReturn(true);
//    $request->shouldReceive('getIsAjax')
//        ->andReturn(true);
//    $request->shouldReceive('getCsrfToken')
//        ->andReturn('mock-csrf-token');
//    $request->shouldReceive('validateCsrfToken')
//        ->andReturn(true);
//    $request->shouldReceive('getIsCpRequest')
//        ->andReturn(false);
//    $request->shouldReceive('hasValidSiteToken')
//        ->andReturn(false);
//
//    // Set the mock request component in Craft
//    Craft::$app->set('request', $request);
//
//    // Mock the Session component
//    $session = Mockery::mock(Session::class);
//    $session->shouldReceive('get')
//        ->andReturn([]);
//    $session->shouldReceive('set')
//        ->andReturnNull();
//    $session->shouldReceive('remove')
//        ->andReturnNull();
//
//    // Set the mock session component in Craft
//    Craft::$app->set('session', $session);
//
//    // Mock the OpenAI service
//    $openAiService = Mockery::mock(Sidekick::$plugin->openAi);
//    $openAiService->shouldReceive('callChatCompletion')
//        ->andReturn([
//            'success' => true,
//            'results' => $assistantMessage,
//        ]);
//
//    // Set the mock OpenAI service in the plugin
//    Sidekick::$plugin->set('openAi', $openAiService);
//
//    // Mock the Actions service
//    $actionsService = Mockery::mock(Sidekick::$plugin->actions);
//    $actionsService->shouldReceive('executeActions')
//        ->andReturn([
//            'success'        => true,
//            'message'        => 'File contents displayed successfully.',
//            'actionMessages' => [],
//            'content'        => '<h1>Test Template</h1>',
//        ]);
//
//    // Set the mock Actions service in the plugin
//    Sidekick::$plugin->set('actions', $actionsService);
//
//    // Instantiate the ChatController
//    $controller = new ChatController('chat', Craft::$app);
//
//    // Suppress the output
//    ob_start();
//    // Invoke the actionSendMessage method
//    $response = $controller->runAction('send-message');
//    ob_end_clean();
//
//    // Assert that the response is a JSON response
//    expect($response)->toBeInstanceOf(Response::class);
//    expect($response->format)->toBe(Response::FORMAT_JSON);
//
//    // Get the response data
//    $responseData = $response->data;
//
//    // Assert that the response indicates success
//    expect($responseData['success'])->toBeTrue();
//
//    // Assert that the action was executed and the expected keys are present
//    expect($responseData)->toHaveKeys(['message', 'actionMessages', 'content']);
//
//    // Assert that the content is as expected
//    expect($responseData['content'])->toBe('<h1>Test Template</h1>');
//
//    // Assert that the message is as expected
//    expect($responseData['message'])->toBe('File contents displayed successfully.');
//});
//
//afterEach(function () {
//    // Close Mockery after each test
//    Mockery::close();
//});

<?php

namespace doublesecretagency\sidekick\controllers;

use Craft;
use craft\errors\MissingComponentException;
use craft\web\Controller;
use doublesecretagency\sidekick\Sidekick;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Class ChatController
 *
 * Handles chat interactions and processes file operations based on assistant commands.
 */
class ChatController extends Controller
{
    // Allow anonymous access to specific actions if necessary
    protected array|int|bool $allowAnonymous = [];

    /**
     * Renders the chat interface.
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        return $this->renderTemplate('sidekick/chat');
    }

    /**
     * Handles sending messages to the AI model and processing responses.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws MissingComponentException
     */
    public function actionSendMessage(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $message = $request->getRequiredBodyParam('message');

        // Append the user's message to the conversation
        $session = Craft::$app->getSession();
        $conversation = $session->get('sidekickConversation', []);

        // Load the assistant's system prompt
        $prompt = Sidekick::$plugin->openAIService->getSystemPrompt();

        // Include the system prompt as the first message
        $messages = [
            [
                'role' => 'system',
                'content' => $prompt
            ],
        ];

        // Add the conversation history
        foreach ($conversation as $msg) {
            $messages[] = $msg;
        }

        // Add the new user message
        $messages[] = [
            'role' => 'user',
            'content' => $message
        ];

        // Prepare the AI API request
        $apiRequest = [
            'model' => Sidekick::$aiModel,
            'messages' => $messages,
            'max_tokens' => 1500,
            'temperature' => 0.2,
        ];

        // Call the AI API
        $openAIService = Sidekick::$plugin->openAIService;
        $apiResponse = $openAIService->callChatCompletion($apiRequest);

        // Handle API errors
        if (!$apiResponse['success']) {
            Craft::error("AI API Error: " . $apiResponse['error'], __METHOD__);
            return $this->asJson([
                'success' => false,
                'error' => $apiResponse['error'],
            ]);
        }

        // Extract the assistant's message from the API response
        $assistantMessage = $apiResponse['results'];

        // Preprocess the assistant's response to remove code block markers and trim whitespace
        $assistantMessageClean = trim($assistantMessage);

        // Remove any backticks or code block formatting
        if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/m', $assistantMessageClean, $matches)) {
            $assistantMessageClean = $matches[1];
        }

        // Alternatively, remove any backticks at the start and end
        $assistantMessageClean = trim($assistantMessageClean, "`");

        // Decode the JSON response
        $decodedJson = json_decode($assistantMessageClean, true);

        // Whether the response contains actions
        $actions = (json_last_error() === JSON_ERROR_NONE && isset($decodedJson['actions']));

        if (!$actions) {
            // Append the assistant's message to the conversation
            $conversation[] = [
                'role' => 'assistant',
                'content' => $assistantMessage
            ];
            $session->set('sidekickConversation', $conversation);

            return $this->asJson([
                'success' => true,
                'message' => $assistantMessage,
            ]);
        }

        // Execute the actions using ActionsService
        $actionsService = Sidekick::$plugin->actionsService;
        $executionResults = $actionsService->executeActions($decodedJson['actions']);

        // Append the assistant's message to the conversation
        $conversation[] = [
            'role' => 'assistant',
            'content' => $assistantMessage
        ];

        // Update the conversation history
        $session->set('sidekickConversation', $conversation);

        // Prepare the response message
        $responseMessage = $executionResults['message'];

        // Return the response message
        return $this->asJson([
            'success' => true,
            'message' => $responseMessage,
        ]);
    }
}

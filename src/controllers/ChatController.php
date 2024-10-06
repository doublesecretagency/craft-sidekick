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

    // ========================================================================= //

    /**
     * Retrieves the conversation history from the session.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws MissingComponentException
     */
    public function actionGetConversation(): Response
    {
        $this->requireAcceptsJson();

        // Retrieve the conversation from the session
        $conversation = Craft::$app->getSession()->get('sidekickConversation', []);

        // Return the conversation
        return $this->asJson([
            'success' => true,
            'conversation' => $conversation,
        ]);
    }

    /**
     * Clears the conversation history from the session.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws MissingComponentException
     */
    public function actionClearConversation(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        // Clear the conversation from the session
        Craft::$app->getSession()->remove('sidekickConversation');

        // Return a success message
        return $this->asJson([
            'success' => true,
            'message' => 'Conversation cleared.',
        ]);
    }

    // ========================================================================= //

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

        // Step 1: Receive the user's message
        $request = Craft::$app->getRequest();
        $message = $request->getRequiredBodyParam('message');

        // Step 2: Append the user's message to the conversation history
        $conversation = $this->_appendUserMessage($message);

        // Step 3: Prepare and send the API request to OpenAI
        $apiResponse = $this->_callOpenAiApi($conversation);

        // Handle API errors
        if (!$apiResponse['success']) {
            Craft::error("AI API Error: " . $apiResponse['error'], __METHOD__);
            return $this->asJson([
                'success' => false,
                'error' => $apiResponse['error'],
            ]);
        }

        // Step 4: Process the assistant's response
        return $this->_processAssistantResponse($apiResponse['results'], $conversation);
    }

    // ========================================================================= //

    /**
     * Appends the user's message to the conversation history.
     *
     * @param string $message
     * @return array The updated conversation history
     * @throws MissingComponentException
     */
    private function _appendUserMessage(string $message): array
    {
        $session = Craft::$app->getSession();
        $conversation = $session->get('sidekickConversation', []);

        // Append the user's message
        $conversation[] = [
            'role' => 'user',
            'content' => $message
        ];

        // Update the session
        $session->set('sidekickConversation', $conversation);

        return $conversation;
    }

    /**
     * Prepares and sends the API request to OpenAI.
     *
     * @param array $conversation
     * @return array The API response
     */
    private function _callOpenAiApi(array $conversation): array
    {
        // Load the assistant's system prompt
        $prompt = Sidekick::$plugin->openAi->getSystemPrompt();

        // Prepare messages for the API request
        $messages = [
            [
                'role' => 'system',
                'content' => $prompt
            ],
        ];

        // Add the conversation history
        $messages = array_merge($messages, $conversation);

        // Prepare the API request
        $apiRequest = [
            'model' => Sidekick::$aiModel,
            'messages' => $messages,
            'max_tokens' => 1500,
            'temperature' => 0.2,
        ];

        // Call the OpenAI API
        return Sidekick::$plugin->openAi->callChatCompletion($apiRequest);
    }

    /**
     * Processes the assistant's response.
     *
     * @param string $assistantMessage
     * @param array $conversation
     * @return Response
     * @throws BadRequestHttpException
     */
    private function _processAssistantResponse(string $assistantMessage, array $conversation): Response
    {
        $session = Craft::$app->getSession();

        // Preprocess the assistant's response
        $assistantMessageClean = $this->_cleanAssistantMessage($assistantMessage);

        // Decode the JSON response
        $decodedJson = json_decode($assistantMessageClean, true);
        $isJsonAction = (json_last_error() === JSON_ERROR_NONE && isset($decodedJson['actions']));

        if ($isJsonAction) {
            // Handle JSON actions
            $actionResponse = $this->_executeActions($decodedJson['actions'], $conversation);
            return $this->asJson($actionResponse);
        } else {
            // Handle conversational message
            $this->_appendAssistantMessage($assistantMessage, $conversation);
            return $this->asJson([
                'success' => true,
                'message' => $assistantMessage,
            ]);
        }
    }

    /**
     * Cleans the assistant's message by removing code block markers and trimming whitespace.
     *
     * @param string $message
     * @return string
     */
    private function _cleanAssistantMessage(string $message): string
    {
        $message = trim($message);

        // Remove code block markers
        if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/m', $message, $matches)) {
            $message = $matches[1];
        }

        // Remove any backticks at the start and end
        $message = trim($message, "`");

        return $message;
    }

    /**
     * Executes actions provided by the assistant.
     *
     * @param array $actions
     * @param array $conversation
     * @return array
     */
    private function _executeActions(array $actions, array $conversation): array
    {
        $actionsService = Sidekick::$plugin->actions;
        $executionResults = $actionsService->executeActions($actions);

        // Prepare the response message
        $responseMessage = $executionResults['message'];

        // Split the message into individual system messages
        $systemMessages = explode("\n", $responseMessage);
        foreach ($systemMessages as $systemMessage) {
            // Append each system message to the conversation history
            $conversation[] = [
                'role' => 'system',
                'content' => $systemMessage
            ];
        }

        // Update the session with the new conversation history
        Craft::$app->getSession()->set('sidekickConversation', $conversation);

        return [
            'success' => true,
            'message' => $responseMessage,
        ];
    }

    /**
     * Appends the assistant's message to the conversation history.
     *
     * @param string $assistantMessage
     * @param array $conversation
     * @throws MissingComponentException
     */
    private function _appendAssistantMessage(string $assistantMessage, array $conversation): void
    {
        // Append the assistant's message
        $conversation[] = [
            'role' => 'assistant',
            'content' => $assistantMessage
        ];

        // Update the session
        Craft::$app->getSession()->set('sidekickConversation', $conversation);
    }
}

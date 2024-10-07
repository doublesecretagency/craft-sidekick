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
     * @var array The conversation history.
     */
    private array $conversation = [];

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

        // Load the conversation from the session
        $this->_loadConversation();

        // Return the conversation
        return $this->asJson([
            'success' => true,
            'conversation' => $this->conversation,
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

        // Reset the conversation property
        $this->conversation = [];

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
        $this->_appendUserMessage($message);

        // Step 3: Prepare and send the API request to OpenAI
        $apiResponse = $this->_callOpenAiApi();

        // Handle API errors
        if (!$apiResponse['success']) {
            Craft::error("AI API Error: " . $apiResponse['error'], __METHOD__);
            return $this->asJson([
                'success' => false,
                'error' => $apiResponse['error'],
            ]);
        }

        // Step 4: Process the assistant's response
        return $this->_processAssistantResponse($apiResponse['results']);
    }

    // ========================================================================= //

    /**
     * Loads the conversation history from the session.
     */
    private function _loadConversation(): void
    {
        $session = Craft::$app->getSession();
        $this->conversation = $session->get('sidekickConversation', []);
    }

    /**
     * Saves the conversation history to the session.
     */
    private function _saveConversation(): void
    {
        $session = Craft::$app->getSession();
        $session->set('sidekickConversation', $this->conversation);
    }

    /**
     * Appends the user's message to the conversation history.
     *
     * @param string $message
     */
    private function _appendUserMessage(string $message): void
    {
        // Load the current conversation
        $this->_loadConversation();

        // Append the user's message
        $this->conversation[] = [
            'role' => 'user',
            'content' => $message,
        ];

        // Save the updated conversation
        $this->_saveConversation();
    }

    /**
     * Prepares and sends the API request to OpenAI.
     *
     * @return array The API response
     */
    private function _callOpenAiApi(): array
    {
        // Load the assistant's system prompt
        $prompt = Sidekick::$plugin->openAi->getSystemPrompt();

        // Prepare messages for the API request
        $messages = [
            [
                'role' => 'system',
                'content' => $prompt,
            ],
        ];

        // Add the conversation history
        $messages = array_merge($messages, $this->conversation);

        // Prepare the API request
        $apiRequest = [
            'model'       => Sidekick::$aiModel,
            'messages'    => $messages,
            'max_tokens'  => 1500,
            'temperature' => 0.2,
        ];

        // Call the OpenAI API
        return Sidekick::$plugin->openAi->callChatCompletion($apiRequest);
    }

    /**
     * Processes the assistant's response.
     *
     * @param string $assistantMessage
     * @return Response
     * @throws MissingComponentException
     */
    private function _processAssistantResponse(string $assistantMessage): Response
    {
        // Load the current conversation
        $this->_loadConversation();

        // Preprocess the assistant's response
        $assistantMessageClean = $this->_cleanAssistantMessage($assistantMessage);

        // Decode the JSON response
        $decodedJson = json_decode($assistantMessageClean, true);
        $isJsonAction = (json_last_error() === JSON_ERROR_NONE && isset($decodedJson['actions']));

        if ($isJsonAction) {
            // Handle JSON actions
            $actionResponse = $this->_executeActions($decodedJson['actions']);
            return $this->asJson($actionResponse);
        } else {
            // Handle conversational message
            $this->_appendAssistantMessage($assistantMessage);
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
     * @return array
     * @throws MissingComponentException
     */
    private function _executeActions(array $actions): array
    {
        // Load the current conversation
        $this->_loadConversation();

        $actionsService   = Sidekick::$plugin->actions;
        $executionResults = $actionsService->executeActions($actions);

        // Collect the messages from action execution
        $actionMessages = $executionResults['messages'] ?? [];

        // Append each action message as a system message
        foreach ($actionMessages as $systemMessage) {
            $this->conversation[] = [
                'role'    => 'system',
                'content' => $systemMessage,
            ];
        }

        // Prepare the final response message
        $responseMessage = $executionResults['message'];

        // Append the final response message as an assistant message
        $this->conversation[] = [
            'role'    => 'assistant',
            'content' => $responseMessage,
        ];

        // Save the updated conversation
        $this->_saveConversation();

        // Return the response message and action messages
        return [
            'success'        => true,
            'message'        => $responseMessage,
            'actionMessages' => $actionMessages,
        ];
    }

    /**
     * Appends the assistant's message to the conversation history.
     *
     * @param string $assistantMessage
     */
    private function _appendAssistantMessage(string $assistantMessage): void
    {
        // Load the current conversation
        $this->_loadConversation();

        // Append the assistant's message
        $this->conversation[] = [
            'role'    => 'assistant',
            'content' => $assistantMessage,
        ];

        // Save the updated conversation
        $this->_saveConversation();
    }
}

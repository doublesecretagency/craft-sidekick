<?php
/**
 * Sidekick plugin for Craft CMS
 *
 * Your AI companion for rapid Craft CMS development.
 *
 * @author    Double Secret Agency
 * @link      https://plugins.doublesecretagency.com/
 * @copyright Copyright (c) 2025 Double Secret Agency
 */

namespace doublesecretagency\sidekick\controllers;

use Craft;
use craft\errors\MissingComponentException;
use craft\web\Controller;
use doublesecretagency\sidekick\constants\AiModel;
use doublesecretagency\sidekick\constants\Session;
use doublesecretagency\sidekick\models\ChatMessage;
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

    // ========================================================================= //

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
     * Retrieves the selected AI model from the session.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws MissingComponentException
     */
    public function actionGetSelectedModel(): Response
    {
        $this->requireAcceptsJson();

        // Get the selected AI model from the session
        $selectedModel = Craft::$app->getSession()->get(Session::AI_MODEL, AiModel::DEFAULT);

        // Return the selected AI model
        return $this->asJson([
            'success' => true,
            'selectedModel' => $selectedModel,
        ]);
    }

    /**
     * Sets the selected AI model in the session.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws MissingComponentException
     */
    public function actionSetSelectedModel(): Response
    {
        $this->requirePostRequest();

        // Get the selected AI model from the request
        $selectedModel = Craft::$app->getRequest()->getBodyParam('selectedModel', AiModel::DEFAULT);

        // Set the selected AI model in the session
        Craft::$app->getSession()->set(Session::AI_MODEL, $selectedModel);

        // Return success
        return $this->asJson(['success' => true]);
    }

    // ========================================================================= //

    /**
     * Retrieves the conversation history from the session.
     *
     * @return Response
     */
    public function actionGetConversation(): Response
    {
        try {
            $this->requireAcceptsJson();

            // Get the existing conversation
            $conversation = Sidekick::$plugin->chat->getConversation();

            // If no conversation exists
            if (!$conversation) {
                // Generate a greeting message
                $greeting = Sidekick::$plugin->openAi->getGreetingMessage();
                // Start conversation with a greeting
                $conversation = [$greeting];
            }

            // Return the conversation
            return $this->asJson([
                'success' => true,
                'conversation' => $conversation,
                'greeting' => $greeting ?? null,
            ]);

        } catch (\Exception $e) {

            // Return an error message
            return $this->asJson([
                'success' => false,
                'message' => "Unable to get the conversation. {$e->getMessage()}"
            ]);

        }
    }

    // ========================================================================= //

    /**
     * Clears the conversation history from the session.
     *
     * @return Response
     */
    public function actionClearConversation(): Response
    {
        try {
            $this->requirePostRequest();
            $this->requireAcceptsJson();

            // Clear the conversation from the session
            Sidekick::$plugin->chat->clearConversation();

            // Log the message
            Craft::info('Cleared the conversation.', __METHOD__);

            // Return a success message
            return $this->asJson([
                'success' => true,
                'message' => 'Conversation cleared.',
            ]);

        } catch (\Exception $e) {

            // Return an error message
            return $this->asJson([
                'success' => false,
                'message' => "Unable to clear the conversation. {$e->getMessage()}",
            ]);

        }
    }

    // ========================================================================= //

    /**
     * Sends a message to the assistant and receives a reply.
     */
    public function actionSendMessage(): void
    {
        // Start the SSE connection
        $sse = Sidekick::$plugin->sse;

        // Start the SSE connection
        $sse->startConnection();

        try {
            // Get services
            $chat   = Sidekick::$plugin->chat;
            $openAi = Sidekick::$plugin->openAi;

            // Receive the user's message
            $request = Craft::$app->getRequest();
            $message = $request->getQueryParam('message');
            $greeting = $request->getQueryParam('greeting');

            // Get size of chat history
            $chatHistory = count($chat->getConversation());

            // If greeting was specified and no chat history exists
            if ($greeting && !$chatHistory) {
                // Start conversation with the greeting message
                (new ChatMessage([
                    'role' => ChatMessage::ASSISTANT,
                    'message' => $greeting
                ]))
                    ->log()
                    ->toChatHistory()
                    ->toOpenAiThread();
            }

            // Append user message to conversation
            (new ChatMessage([
                'role' => ChatMessage::USER,
                'message' => $message
            ]))
                ->log()
                ->toChatHistory()
                ->toOpenAiThread();

            // Run the OpenAI thread
            $openAi->runThread();

        } catch (\Exception $e) {

            // Append error to the chat history
            (new ChatMessage([
                'role' => ChatMessage::ERROR,
                'message' => $e->getMessage()
            ]))
                ->log()
                ->toChatHistory()
                ->toChatWindow();

        }

        // Close the connection
        $sse->closeConnection();
    }
}

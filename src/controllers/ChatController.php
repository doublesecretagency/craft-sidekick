<?php

namespace doublesecretagency\sidekick\controllers;

use Craft;
use craft\errors\MissingComponentException;
use craft\web\Controller;
use doublesecretagency\sidekick\constants\AiModel;
use doublesecretagency\sidekick\constants\Session;
use doublesecretagency\sidekick\helpers\ChatHistory;
//use doublesecretagency\sidekick\models\api\ApiResponse;
use doublesecretagency\sidekick\Sidekick;
use Exception;
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
     * @throws BadRequestHttpException
     */
    public function actionGetConversation(): Response
    {
        $this->requireAcceptsJson();

        // Get the existing conversation
        $conversation = ChatHistory::getConversation();

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
    }

    /**
     * Clears the conversation history from the session.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionClearConversation(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        // Clear the conversation from the session
        ChatHistory::clearConversation();

        // Log the message
        Craft::info("Cleared the conversation.", __METHOD__);

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
     */
    public function actionSendMessage(): Response
    {
        try {
            $this->requirePostRequest();

            // Get the OpenAI service
            $openAi = Sidekick::$plugin->openAi;

            // Receive the user's message
            $request = Craft::$app->getRequest();
            $message = $request->getRequiredBodyParam('message');
            $greeting = $request->getBodyParam('greeting');

            // Get size of chat history
            $chatHistory = count(ChatHistory::getConversation());

            // If greeting was specified and no chat history exists
            if ($greeting && !$chatHistory) {
                // Create the greeting message
                $g = $openAi->newAssistantMessage($greeting);
                // Append it to the chat history
                $g->appendToChatHistory();
            }

            // Create the user message
            $m = $openAi->newUserMessage($message);
            // Log and append to the chat history
            $m->log()->appendToChatHistory();

            // Send the message to the API
            $results = $openAi->sendMessage($m);

            // Return the results
            return $this->asJson($results);



//            $response = $openAi->sendMessage($m);

//            // Get the API response
//            $r = new ApiResponse($response);
//
//            // If the API response was not successful
//            if (!$r->success) {
//                // Return the error message
//                return $this->asJson([
//                    'success' => false,
//                    'error' => $r->error
//                ]);
//            }
//
//            // Return all messages produced by the API response
//            return $this->asJson([
//                'success' => true,
//                'messages' => $r->getMessages(),
//            ]);



        } catch (Exception $e) {

            // Log the exception
            Craft::error("Exception in actionSendMessage: {$e->getMessage()}", __METHOD__);
            return $this->asJson([
                'success' => false,
                'error' => 'An unexpected error occurred.',
            ]);

        }
    }
}

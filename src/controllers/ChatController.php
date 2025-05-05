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
use craft\helpers\StringHelper;
use craft\web\Controller;
use doublesecretagency\sidekick\constants\AiModel;
use doublesecretagency\sidekick\constants\Session;
use doublesecretagency\sidekick\models\ChatMessage;
use doublesecretagency\sidekick\Sidekick;
use Exception;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
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
        return $this->renderTemplate('sidekick/chat', [
            'skillSets' => $this->_getSkillSets(),
        ]);
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
            $conversation = Sidekick::getInstance()?->chat->getConversation();

            // If no conversation exists
            if (!$conversation) {
                // Generate a greeting message
                $greeting = Sidekick::getInstance()?->openAi->getGreetingMessage();
                // Start conversation with a greeting
                $conversation = [$greeting];
            }

            // Return the conversation
            return $this->asJson([
                'success' => true,
                'conversation' => $conversation,
                'greeting' => $greeting ?? null,
            ]);

        } catch (Exception $e) {

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
            Sidekick::getInstance()?->chat->clearConversation();

            // Log the message
            Craft::info('Cleared the conversation.', __METHOD__);

            // Return a success message
            return $this->asJson([
                'success' => true,
                'message' => 'Conversation cleared.',
            ]);

        } catch (Exception $e) {

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
        $sse = Sidekick::getInstance()?->sse;

        // Start the SSE connection
        $sse->startConnection();

        try {
            // Get services
            $chat   = Sidekick::getInstance()?->chat;
            $openAi = Sidekick::getInstance()?->openAi;

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

        } catch (Exception $e) {

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

    // ========================================================================= //

    /**
     * Get the complete list of available skill sets.
     *
     * @return array
     */
    public function _getSkillSets(): array
    {
        // Initialize skill sets
        $skillSets = [];

        // Create a new instance of the DocBlockFactory
        $docFactory = DocBlockFactory::createInstance();

        // Loop through each tool class
        foreach (Sidekick::getInstance()?->getSkillSets() as $skill) {

            // Defaults to uncategorized
            $category = 'Uncategorized';

            // Attempt to get the actual category
            try {

                // Get available tool functions
                $toolFunctions = (new $skill())->getToolFunctions();

                // Get reflection class object
                $reflection = new ReflectionClass($skill);

                // Get the class's docblock
                $classDocsComment = $reflection->getDocComment();

                // If the class has a docblock
                if ($classDocsComment) {

                    // Get the method's docblock
                    $classDocs = $docFactory->create($classDocsComment);

                    // Get the @category value of the class
                    $categories = $classDocs->getTagsByName('category');

                    // If any category tags exist
                    if ($categories) {
                        // Get only the first category
                        $category = $categories[0]->getDescription()->render();
                    }

                }

            } catch (ReflectionException $e) {

                // Something went wrong, skip to the next one
                continue;

            }

            // Loop through each tool function
            foreach ($toolFunctions as $toolFunction) {

                // Get the method's docblock
                $docBlock = $docFactory->create($toolFunction->getDocComment());

                // Get the method name
                $method = $toolFunction->getName();

                // Convert camelCase $method to normal Title Caps
                $name = StringHelper::toPascalCase($method);
                $name = implode(' ', StringHelper::toWords($name));

                // If category array does not yet exist, initialize it
                if (!isset($skillSets[$category])) {
                    $skillSets[$category] = [];
                }

                // Configure the skill info
                $skillSets[$category][] = [
                    'fullPath' => "$skill::$method",
                    'name' => $name,
                    'description' => $docBlock->getSummary()
                ];

            }

        }

        // Return the skill sets
        return $skillSets;
    }
}

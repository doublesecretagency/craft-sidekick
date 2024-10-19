<?php

namespace doublesecretagency\sidekick\controllers;

use Craft;
use craft\errors\MissingComponentException;
use craft\web\Controller;
use doublesecretagency\sidekick\constants\Constants;
use doublesecretagency\sidekick\Sidekick;
use GuzzleHttp\Exception\GuzzleException;
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
    private array $_conversation = [];

    /**
     * @var string|null The initial greeting message.
     */
    private ?string $_greeting = null;

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

        $selectedModel = Craft::$app->getSession()->get(Constants::AI_MODEL_SESSION, Constants::DEFAULT_AI_MODEL);

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

        $selectedModel = Craft::$app->getRequest()->getBodyParam('selectedModel', Constants::DEFAULT_AI_MODEL);
        Craft::$app->getSession()->set(Constants::AI_MODEL_SESSION, $selectedModel);

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

        // Load the conversation from the session
        $this->_loadConversation();

        // Return the conversation
        return $this->asJson([
            'success' => true,
            'conversation' => $this->_conversation,
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
        Craft::$app->getSession()->remove(Constants::CHAT_SESSION);

        // Reset the conversation property
        $this->_conversation = $this->_initConversation();

        // Return a success message
        return $this->asJson([
            'success' => true,
            'message' => 'Conversation cleared.',
        ]);
    }

    /**
     * Initializes a new conversation.
     *
     * @return array
     */
    private function _initConversation(): array
    {
        // If a system greeting already exists
        if ($this->_greeting) {
            // Return the original greeting
            return [
                [
                    'role' => 'assistant',
                    'content' => $this->_greeting,
                ]
            ];
        }

        // Get all greeting options
        $options = Constants::GREETING_OPTIONS;

        // Select a random greeting
        $greeting = $options[array_rand($options)];

        // Return a random greeting
        return [
            [
                'role' => 'assistant',
                'content' => $greeting,
            ]
        ];
    }

    // ========================================================================= //

    /**
     * Handles sending messages to the AI model and processing responses.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws GuzzleException
     * @throws MissingComponentException
     */
    public function actionSendMessage(): Response
    {
        $this->requirePostRequest();

        // Step 1: Receive the user's message
        $request = Craft::$app->getRequest();
        $message = $request->getRequiredBodyParam('message');
        $greeting = $request->getBodyParam('greeting');

        // If system greeting was specified, save it for later
        if ($greeting) {
            $this->_greeting = $greeting;
        }

        // Step 2: Append the user's message to the conversation history
        $this->_appendMessage('user', $message);

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
        // If the conversation is already loaded, bail
        if ($this->_conversation) {
            return;
        }

        try {
            // Load the conversation session
            $this->_conversation = Craft::$app->getSession()->get(
                Constants::CHAT_SESSION,
                $this->_initConversation()
            );
        } catch (MissingComponentException $e) {
            // Do nothing
        }
    }

    /**
     * Saves the conversation history to the session.
     */
    private function _saveConversation(): void
    {
        try {
            // Save the conversation session
            Craft::$app->getSession()->set(
                Constants::CHAT_SESSION,
                $this->_conversation
            );
        } catch (MissingComponentException $e) {
            // Do nothing
        }
    }

    /**
     * Appends a message to the conversation history.
     *
     * @param string $role
     * @param string $message
     */
    private function _appendMessage(string $role, string $message): void
    {
        // Load the current conversation
        $this->_loadConversation();

        // Append the message
        $this->_conversation[] = [
            'role'    => $role,
            'content' => $message,
        ];

        // Save the updated conversation
        $this->_saveConversation();
    }

    // ========================================================================= //

    /**
     * Prepares and sends the API request to OpenAI.
     *
     * @return array The API response
     * @throws GuzzleException
     * @throws MissingComponentException
     */
    private function _callOpenAiApi(): array
    {
        // Get the selected AI model from the session
        $aiModel = Craft::$app->getSession()->get(Constants::AI_MODEL_SESSION, Constants::DEFAULT_AI_MODEL);

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
        $messages = array_merge($messages, $this->_conversation);

        // Prepare the API request
        $apiRequest = [
            'model'       => $aiModel,
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
     */
    private function _processAssistantResponse(string $assistantMessage): Response
    {
        // Load the current conversation
        $this->_loadConversation();

        // Attempt to extract and parse JSON from the assistant's message
        $decodedJson = $this->_extractJson($assistantMessage);
        $isJsonAction = ($decodedJson !== null && isset($decodedJson['actions']));

        // Handle JSON actions
        if ($isJsonAction) {
            $actionResponse = $this->_executeActions($decodedJson['actions']);
            return $this->asJson($actionResponse);
        }

        // If not a JSON action, convert code blocks
        $assistantMessage = $this->_convertCodeBlocks($assistantMessage);

        // Trim the assistant's message before appending
        $assistantMessage = trim($assistantMessage);

        // Handle conversational messages
        $this->_appendMessage('assistant', $assistantMessage);

        // Return the assistant's message
        return $this->asJson([
            'success' => true,
            'message' => $assistantMessage,
        ]);
    }

    /**
     * Attempts to extract JSON from the assistant's message.
     *
     * @param string $message
     * @return array|null
     */
    private function _extractJson(string $message): ?array
    {
        // Remove code block markers if present
        if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/m', $message, $matches)) {
            $jsonString = $matches[1];
        } else {
            $jsonString = $message;
        }

        // Attempt to decode JSON
        $decodedJson = json_decode($jsonString, true);

        // Check for JSON errors
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decodedJson;
        } else {
            return null;
        }
    }

    /**
     * Converts code snippets to HTML <pre><code> blocks.
     *
     * @param string $message
     * @return string
     */
    private function _convertCodeBlocks(string $message): string
    {
        // Trim the message
        $message = trim($message);

        // Convert code snippets to HTML
        $message = preg_replace_callback('/```(.*?)\n([\s\S]*?)```/s', static function ($matches) {

            // Language specified after the backticks (ie: js, html, php)
            $language = htmlspecialchars($matches[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            // Code content between the backticks
            $code = trim($matches[2]);

            // Escape the code content
            $code = htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            // Return the code block
            return "<pre><code class=\"language-{$language}\">{$code}</code></pre>";

        }, $message);

        // Return the converted message
        return $message;
    }

    /**
     * Executes actions provided by the assistant.
     *
     * @param array $actions
     * @return array
     */
    private function _executeActions(array $actions): array
    {
        // Execute the actions
        $executionResults = Sidekick::$plugin->actions->executeActions($actions);

        // Collect the messages from action execution
        $actionMessages = $executionResults['messages'] ?? [];

        // If there's content to display, include it
        $content = $executionResults['content'] ?? null;

        // Append each action message as a system message
        foreach ($actionMessages as $systemMessage) {
            $this->_conversation[] = [
                'role'    => 'system',
                'content' => $systemMessage,
            ];
        }

        // Prepare the final response message
        $responseMessage = $executionResults['message'];

        // Append the final response message as an assistant message
        $this->_appendMessage('assistant', $responseMessage);

        // Return the response message and action messages
        return [
            'success'        => true,
            'message'        => $responseMessage,
            'actionMessages' => $actionMessages,
            'content'        => $content,
        ];
    }
}

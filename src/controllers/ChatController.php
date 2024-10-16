<?php

namespace doublesecretagency\sidekick\controllers;

use Craft;
use craft\errors\MissingComponentException;
use craft\web\Controller;
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
    private array $conversation = [];

    /**
     * @var string|null The initial greeting message.
     */
    private ?string $greeting = null;

    /**
     * @var array List of possible greeting messages.
     */
    private array $greetingOptions = [
        "Bonjour! I'm Sidekick, here to help you manage your template and module files.",
        "Ciao! I'm Sidekick, ready to assist you with your templates and modules.",
        "Good day! I'm Sidekick, here to help you manage your template and module files.",
        "Greetings from Sidekick! How may I assist you with your template or module files?",
        "Greetings! I'm Sidekick, here to help you with your template and module needs.",
        "Greetings! Sidekick here, how can I assist you with your templates or modules?",
        "Hello! I'm Sidekick, here to help you with templates, modules, and more.",
        "Hello! I'm Sidekick, your assistant for managing templates and modules.",
        "Hello! Sidekick at your service, how can I assist with your templates or modules today?",
        "Hello! Sidekick here, how can I support your Craft project today?",
        "Hello! Sidekick here, ready to support any templates or modules you're working on.",
        "Hello again! I'm Sidekick, ready to support your Craft projects.",
        "Hello there! I'm Sidekick, ready to help with your templates and modules.",
        "Hi! I'm Sidekick, ready to help you with any template or module tasks.",
        "Hi! I'm Sidekick, here to support your template and module management.",
        "Hi! I'm Sidekick, your friendly assistant for managing templates and modules.",
        "Hi! Sidekick here, ready to assist with your Craft project templates and modules.",
        "Hi there! I'm Sidekick, ready to support your Craft template and module needs.",
        "Hi there! Sidekick here, how can I assist with your template or module files?",
        "Hi there! Sidekick here, how can I help you streamline your templates or modules?",
        "Hey! I'm Sidekick, your go-to assistant for managing templates and modules.",
        "Hey there! I'm Sidekick, how can I help you today?",
        "Hey there! I'm Sidekick, ready to assist with your Craft templates and modules.",
        "Namaste! I'm Sidekick, here to help you manage your templates and modules.",
        "Salutations! I'm Sidekick, your assistant for all things Craft.",
        "What's up? I'm Sidekick, here to help you with your templates and modules.",
        "Welcome, I'm Sidekick! How can I assist you with your templates or modules today?"
    ];

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
        $this->conversation = $this->_initConversation();

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
        if ($this->greeting) {
            // Return the original greeting
            return [
                [
                    'role' => 'assistant',
                    'content' => $this->greeting,
                ]
            ];
        }

        // Select a random greeting
        $greeting = $this->greetingOptions[array_rand($this->greetingOptions)];

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
            $this->greeting = $greeting;
        }

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
        $this->conversation = Craft::$app->getSession()->get(
            'sidekickConversation',
            $this->_initConversation()
        );
    }

    /**
     * Saves the conversation history to the session.
     */
    private function _saveConversation(): void
    {
        Craft::$app->getSession()->set(
            'sidekickConversation',
            $this->conversation
        );
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
     * @throws GuzzleException
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

        // Handle JSON actions
        if ($isJsonAction) {
            $actionResponse = $this->_executeActions($decodedJson['actions']);
            return $this->asJson($actionResponse);
        }

        // Handle conversational messages
        $this->_appendAssistantMessage($assistantMessage);
        return $this->asJson([
            'success' => true,
            'message' => $assistantMessage,
        ]);
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
     */
    private function _executeActions(array $actions): array
    {
        // Load the current conversation
        $this->_loadConversation();

        // Execute the actions
        $executionResults = Sidekick::$plugin->actions->executeActions($actions);

        // Collect the messages from action execution
        $actionMessages = $executionResults['messages'] ?? [];

        // If there's content to display, include it
        $content = $executionResults['content'] ?? null;

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
            'content'        => $content,
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

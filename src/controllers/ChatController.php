<?php

namespace doublesecretagency\sidekick\controllers;

use Craft;
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
     */
    public function actionSendMessage(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $message = $request->getRequiredBodyParam('message');

        // Log the received user message
        Craft::info("Received user message: {$message}", __METHOD__);

        // Check if the user is requesting to view a file
        $fileRequest = $this->detectFileDisplayRequest($message);

        if ($fileRequest) {
            // Fetch and display the file content directly
            $filePath = $fileRequest['filePath'];
            Craft::info("User requested to view file: {$filePath}", __METHOD__);
            $fileContent = Sidekick::$plugin->fileManagementService->readFile($filePath);

            if ($fileContent !== null) {
                $responseMessage = "Here are the contents of the `{$filePath}` file:\n\n```twig\n{$fileContent}\n```";
                Craft::info("Displaying file contents for: {$filePath}", __METHOD__);
                return $this->asJson([
                    'success' => true,
                    'message' => $responseMessage,
                ]);
            } else {
                Craft::warning("Failed to retrieve contents of: {$filePath}", __METHOD__);
                return $this->asJson([
                    'success' => false,
                    'message' => "Sorry, I couldn't retrieve the contents of the `{$filePath}` file.",
                ]);
            }
        }

        // Proceed with normal AI processing
        // Get the existing conversation from the session
        $session = Craft::$app->getSession();
        $conversation = $session->get('sidekickConversation', []);

        // Append the user's message to the conversation
        $conversation[] = ['role' => 'user', 'content' => $message];

        // Save the updated conversation back to the session
        $session->set('sidekickConversation', $conversation);

        // Prepare the AI API request
        $apiRequest = [
            'model' => Sidekick::$aiModel,
            'messages' => $conversation,
        ];

        // Call the AI API
        $openAIService = Sidekick::$plugin->openAIService;
        $apiResponse = $openAIService->callChatCompletion($apiRequest);

        if (!$apiResponse['success']) {
            // Log the error for debugging
            Craft::error("AI API Error: " . $apiResponse['error'], __METHOD__);

            return $this->asJson([
                'success' => false,
                'error' => $apiResponse['error'],
            ]);
        }

        $assistantMessage = $apiResponse['results'];

        // Log the assistant's raw response
        Craft::info("Assistant's raw response: {$assistantMessage}", __METHOD__);

        // Try to decode JSON from the assistant's message
        $fileOperation = $this->parseJsonResponse($assistantMessage);

        if ($fileOperation) {
            // Execute the file operation
            Craft::info("Executing file operation: " . json_encode($fileOperation), __METHOD__);
            $executionResult = $this->executeFileOperation($fileOperation);

            if ($executionResult['success']) {
                // Append the assistant's message to the conversation
                $conversation[] = ['role' => 'assistant', 'content' => $assistantMessage];
                $session->set('sidekickConversation', $conversation);

                // Respond with a confirmation message
                return $this->asJson([
                    'success' => true,
                    'message' => "File operation executed successfully.",
                ]);
            } else {
                // Append the assistant's message to the conversation
                $conversation[] = ['role' => 'assistant', 'content' => $assistantMessage];
                $session->set('sidekickConversation', $conversation);

                // Respond with an error message
                return $this->asJson([
                    'success' => false,
                    'message' => $executionResult['message'],
                ]);
            }
        } else {
            // Handle regular assistant messages
            $conversation[] = ['role' => 'assistant', 'content' => $assistantMessage];
            $session->set('sidekickConversation', $conversation);

            // Log the final message to be sent to the user
            Craft::info("Sending assistant message to user: {$assistantMessage}", __METHOD__);

            return $this->asJson([
                'success' => true,
                'message' => $assistantMessage,
            ]);
        }
    }

    /**
     * Parses the assistant's response to detect JSON-formatted file operations.
     *
     * @param string $assistantResponse
     * @return array|null
     */
    private function parseJsonResponse(string $assistantResponse): ?array
    {
        $decodedResponse = json_decode($assistantResponse, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            // Validate the required keys
            if (isset($decodedResponse['operation'], $decodedResponse['filePath'])) {
                return $decodedResponse;
            } else {
                Craft::warning("JSON response missing required keys.", __METHOD__);
            }
        } else {
            Craft::info("Assistant's response is not valid JSON.", __METHOD__);
        }

        return null;
    }

    /**
     * Executes a single file operation based on the provided data.
     *
     * @param array $fileOperation
     * @return array
     */
    private function executeFileOperation(array $fileOperation): array
    {
        $operation = $fileOperation['operation'];
        $filePath = $fileOperation['filePath'];
        $fileContent = $fileOperation['content'] ?? null;

        $fileService = Sidekick::$plugin->fileManagementService;

        try {
            switch ($operation) {
                case 'CREATE_FILE':
                    $result = $fileService->createFile($filePath, $fileContent);
                    $message = $result ? "File created: {$filePath}" : "Failed to create file: {$filePath}";
                    break;

                case 'UPDATE_FILE':
                    $result = $fileService->rewriteFile($filePath, $fileContent);
                    $message = $result ? "File updated: {$filePath}" : "Failed to update file: {$filePath}";
                    break;

                case 'DELETE_FILE':
                    $result = $fileService->deleteFile($filePath);
                    $message = $result ? "File deleted: {$filePath}" : "Failed to delete file: {$filePath}";
                    break;

                default:
                    $result = false;
                    $message = "Unsupported file operation: {$operation}";
                    break;
            }

            if ($result) {
                Craft::info($message, __METHOD__);
                return ['success' => true, 'message' => $message];
            } else {
                Craft::error($message, __METHOD__);
                return ['success' => false, 'message' => $message];
            }
        } catch (\Exception $e) {
            $errorMessage = "Error executing file operation: " . $e->getMessage();
            Craft::error($errorMessage, __METHOD__);
            return ['success' => false, 'message' => $errorMessage];
        }
    }

    /**
     * Detects if the user is requesting to display a file's contents.
     *
     * @param string $message
     * @return array|null
     */
    private function detectFileDisplayRequest(string $message): ?array
    {
        // Regex to match phrases like "show me the `index.twig`" or "Get `index.twig`"
        $pattern = '#(?:show me|get)\s+the\s+[`\'"]?(?<fileName>[\w\-\/]+\.twig)[`\'"]?#i';
        Craft::info("Detecting file display request with pattern: {$pattern}", __METHOD__);

        if (preg_match($pattern, $message, $matches)) {
            $fileName = $matches['fileName'];
            Craft::info("Detected file name: {$fileName}", __METHOD__);

            // Construct the relative file path
            $filePath = "/templates/{$fileName}";

            // Validate the file path
            if (Sidekick::$plugin->fileManagementService->isTwigFile($filePath)) {
                Craft::info("Validated Twig file path: {$filePath}", __METHOD__);
                return ['filePath' => $filePath];
            } else {
                Craft::warning("Invalid Twig file path detected: {$filePath}", __METHOD__);
            }
        }

        return null;
    }

    /**
     * Retrieves the conversation history from the session.
     *
     * @return Response
     */
    public function actionGetConversation(): Response
    {
        $this->requireAcceptsJson();

        $session = Craft::$app->getSession();
        $conversation = $session->get('sidekickConversation', []);

        return $this->asJson([
            'success' => true,
            'conversation' => $conversation,
        ]);
    }

    /**
     * Clears the conversation history from the session.
     *
     * @return Response
     */
    public function actionClearConversation(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $session = Craft::$app->getSession();
        $session->remove('sidekickConversation');

        return $this->asJson([
            'success' => true,
            'message' => 'Conversation cleared.',
        ]);
    }
}

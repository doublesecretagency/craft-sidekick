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

        // Process any file mentions in the user's message
        $additionalContext = $this->processUserMessageForFiles($message);

        // Save the updated conversation back to the session
        $session->set('sidekickConversation', $conversation);

        // Prepare the AI API request
        $apiRequest = [
            'model' => Sidekick::$aiModel,
            'messages' => $conversation,
        ];

        // Include additional context if available
        if (!empty($additionalContext)) {
            $apiRequest['additionalContext'] = $additionalContext;
            Craft::info("Added additional context for files: " . implode(', ', array_column($additionalContext, 'filePath')), __METHOD__);
        }

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

        // Extract and execute file operations
        $fileOperations = $this->extractFileOperations($assistantMessage);
        if (!empty($fileOperations)) {
            Craft::info("Extracted file operations: " . json_encode($fileOperations), __METHOD__);
            $this->executeFileOperations($fileOperations);
            // Remove the file operation commands from the assistant's message
            $assistantMessage = preg_replace('#\[(CREATE_FILE|UPDATE_FILE|DELETE_FILE) "([^"]+)"\](.*?)\[/\1\]#s', '', $assistantMessage);
            $assistantMessage = preg_replace('#\[(DELETE_FILE) "([^"]+)" /\]#s', '', $assistantMessage);
            // Log the cleaned assistant message
            Craft::info("Assistant's message after removing file operations: {$assistantMessage}", __METHOD__);
        }

        // Append the assistant's message to the conversation
        $conversation[] = ['role' => 'assistant', 'content' => $assistantMessage];

        // Save the updated conversation back to the session
        $session->set('sidekickConversation', $conversation);

        // Log the final message to be sent to the user
        Craft::info("Sending assistant message to user: {$assistantMessage}", __METHOD__);

        return $this->asJson([
            'success' => true,
            'message' => $assistantMessage,
            'fileOperation' => $fileOperations,
        ]);
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
     * Extracts file operation commands from the assistant's response.
     *
     * @param string $assistantResponse
     * @return array
     */
    private function extractFileOperations(string $assistantResponse): array
    {
        $fileOperations = [];

        // Regex to match CREATE_FILE and UPDATE_FILE commands
        $patternCreateUpdate = '#\[(CREATE_FILE|UPDATE_FILE) "([^"]+)"\](.*?)\[/\1\]#s';
        Craft::info("Extracting CREATE_FILE and UPDATE_FILE commands with pattern: {$patternCreateUpdate}", __METHOD__);

        if (preg_match_all($patternCreateUpdate, $assistantResponse, $matchesCreateUpdate, PREG_SET_ORDER)) {
            foreach ($matchesCreateUpdate as $match) {
                $operation = $match[1];
                $filePath = $match[2];
                $fileContent = $match[3];
                $fileOperations[] = [
                    'operation' => $operation,
                    'filePath' => $filePath,
                    'fileContent' => $fileContent,
                ];
                Craft::info("Extracted {$operation} for file: {$filePath}", __METHOD__);
            }
        }

        // Regex to match DELETE_FILE commands
        $patternDelete = '#\[DELETE_FILE "([^"]+)" /\]#s';
        Craft::info("Extracting DELETE_FILE commands with pattern: {$patternDelete}", __METHOD__);

        if (preg_match_all($patternDelete, $assistantResponse, $matchesDelete, PREG_SET_ORDER)) {
            foreach ($matchesDelete as $match) {
                $filePath = $match[1];
                $fileOperations[] = [
                    'operation' => 'DELETE_FILE',
                    'filePath' => $filePath,
                    'fileContent' => null,
                ];
                Craft::info("Extracted DELETE_FILE for file: {$filePath}", __METHOD__);
            }
        }

        return $fileOperations;
    }

    /**
     * Executes the extracted file operation commands.
     *
     * @param array $fileOperations
     */
    private function executeFileOperations(array $fileOperations): void
    {
        foreach ($fileOperations as $op) {
            $operation = $op['operation'];
            $filePath = $op['filePath'];
            $fileContent = $op['fileContent'];

            Craft::info("Executing operation: {$operation} on file: {$filePath}", __METHOD__);

            switch ($operation) {
                case 'CREATE_FILE':
                    $result = $this->fileManagementService->createFile($filePath, $fileContent);
                    $this->logOperationResult($operation, $filePath, $result);
                    break;

                case 'UPDATE_FILE':
                    $result = $this->fileManagementService->rewriteFile($filePath, $fileContent);
                    $this->logOperationResult($operation, $filePath, $result);
                    break;

                case 'DELETE_FILE':
                    $result = $this->fileManagementService->deleteFile($filePath);
                    $this->logOperationResult($operation, $filePath, $result);
                    break;

                default:
                    Craft::warning("Unsupported file operation: {$operation}", __METHOD__);
                    break;
            }
        }
    }

    /**
     * Logs the result of a file operation.
     *
     * @param string $operation
     * @param string $filePath
     * @param bool $success
     */
    private function logOperationResult(string $operation, string $filePath, bool $success): void
    {
        if ($success) {
            Craft::info("Successfully executed {$operation} on {$filePath}", __METHOD__);
        } else {
            Craft::error("Failed to execute {$operation} on {$filePath}", __METHOD__);
        }
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

    /**
     * Processes the assistant's message to handle file operations.
     *
     * @param string $message
     * @return array|null
     */
    private function processAssistantMessage(string $message): ?array
    {
        $fileService = Sidekick::$plugin->fileManagementService;

        // Match CREATE_FILE and UPDATE_FILE commands
        $patternCreateUpdate = '#\[(CREATE_FILE|UPDATE_FILE) "([^"]+)"\](.*?)\[/\1\]#s';
        Craft::info("Processing assistant message for CREATE_FILE and UPDATE_FILE with pattern: {$patternCreateUpdate}", __METHOD__);

        if (preg_match($patternCreateUpdate, $message, $matchesCreateUpdate)) {
            $operation = $matchesCreateUpdate[1];
            $filePath = $matchesCreateUpdate[2];
            $fileContent = $matchesCreateUpdate[3];

            Craft::info("Detected {$operation} for file: {$filePath}", __METHOD__);

            // Execute the file operation
            if ($operation === 'CREATE_FILE') {
                $result = $fileService->createFile($filePath, $fileContent);
            } else { // UPDATE_FILE
                $result = $fileService->rewriteFile($filePath, $fileContent);
            }

            // Send a message to the UX
            $this->sendFileOperationMessage($operation, $filePath);

            // Remove the file operation command from the assistant's message
            $assistantMessage = preg_replace($patternCreateUpdate, '', $message);
            Craft::info("Assistant message after removing CREATE_FILE/UPDATE_FILE: {$assistantMessage}", __METHOD__);

            return [
                'success' => $result === true,
                'requiresNextChange' => true,
                'assistantMessage' => trim($assistantMessage),
            ];
        }

        // Match DELETE_FILE commands
        $patternDelete = '#\[DELETE_FILE "([^"]+)" /\]#s';
        Craft::info("Processing assistant message for DELETE_FILE with pattern: {$patternDelete}", __METHOD__);

        if (preg_match_all($patternDelete, $message, $matchesDelete)) {
            foreach ($matchesDelete[1] as $filePath) {
                Craft::info("Detected DELETE_FILE for file: {$filePath}", __METHOD__);
                $fileService->deleteFile($filePath);

                // Send a message to the UX
                $this->sendFileOperationMessage('DELETE_FILE', $filePath);
            }

            // Remove the DELETE_FILE commands from the assistant's message
            $assistantMessage = preg_replace($patternDelete, '', $message);
            Craft::info("Assistant message after removing DELETE_FILE: {$assistantMessage}", __METHOD__);

            return [
                'success' => true,
                'requiresNextChange' => true,
                'assistantMessage' => trim($assistantMessage),
            ];
        }

        // Check for unrecognized file operation commands
        $patternRead = '#\[(READ_FILE) "([^"]+)"\]#s';
        Craft::info("Processing assistant message for unrecognized READ_FILE with pattern: {$patternRead}", __METHOD__);

        if (preg_match($patternRead, $message, $matchesRead)) {
            $command = $matchesRead[1];
            $filePath = $matchesRead[2];
            Craft::warning("Unrecognized file operation command: [{$command} \"{$filePath}\"]", __METHOD__);

            // Remove the unrecognized command from the assistant's message
            $assistantMessage = preg_replace($patternRead, '', $message);
            Craft::info("Assistant message after removing unrecognized READ_FILE: {$assistantMessage}", __METHOD__);

            return [
                'success' => false,
                'requiresNextChange' => false,
                'assistantMessage' => trim($assistantMessage),
            ];
        }

        // No file operation detected
        return null;
    }

    /**
     * Sends a file operation message to the UX.
     *
     * @param string $operation
     * @param string $filePath
     */
    private function sendFileOperationMessage(string $operation, string $filePath): void
    {
        $session = Craft::$app->getSession();
        $conversation = $session->get('sidekickConversation', []);

        $operationText = match ($operation) {
            'CREATE_FILE' => 'Created',
            'UPDATE_FILE' => 'Updated',
            'DELETE_FILE' => 'Deleted',
            default => 'Modified',
        };

        $message = "[{$operationText} \"{$filePath}\"]";

        // Append the message to the conversation
        $conversation[] = ['role' => 'system', 'content' => $message];
        $session->set('sidekickConversation', $conversation);

        Craft::info("Sent file operation message to UX: {$message}", __METHOD__);
    }

    /**
     * Processes the user's message to detect file mentions and read their contents.
     *
     * @param string $message
     * @return array|null Additional context to pass to the AI assistant.
     */
    private function processUserMessageForFiles(string $message): ?array
    {
        $fileService = Sidekick::$plugin->fileManagementService;
        $additionalContext = [];

        // Regex pattern to detect file paths, e.g., "/templates/index.twig"
        $pattern = '#\/[\w\-\/\.]+\.twig#';
        Craft::info("Detecting file paths in user message with pattern: {$pattern}", __METHOD__);

        if (preg_match_all($pattern, $message, $matches)) {
            $filePaths = $matches[0];
            Craft::info("Detected file paths: " . implode(', ', $filePaths), __METHOD__);

            foreach ($filePaths as $filePath) {
                // Sanitize and resolve the file path
                $filePath = $fileService->sanitizeFilePath($filePath);
                $absolutePath = $fileService->resolveFilePath($filePath);

                // Ensure the path is allowed
                if ($fileService->isPathAllowed($absolutePath)) {
                    // Read the file contents
                    $content = $fileService->readFile($filePath);

                    if ($content !== null) {
                        // Include the file content in the additional context
                        $additionalContext[] = [
                            'filePath' => $filePath,
                            'content' => $content,
                        ];
                        Craft::info("Added file content to additional context for: {$filePath}", __METHOD__);
                    } else {
                        Craft::warning("Failed to read content for file: {$filePath}", __METHOD__);
                    }
                } else {
                    Craft::warning("Unauthorized file access attempt: {$filePath}", __METHOD__);
                }
            }
        }

        return $additionalContext ?: null;
    }
}

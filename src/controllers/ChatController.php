<?php

namespace doublesecretagency\sidekick\controllers;

use Craft;
use craft\errors\MissingComponentException;
use craft\web\Controller;
use doublesecretagency\sidekick\Sidekick;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\base\Exception;

/**
 * Class ChatController
 *
 * Handles chat interactions and processes file operations based on assistant commands.
 */
class ChatController extends Controller
{
    // Restrict access to authenticated users
    protected array|int|bool $allowAnonymous = false;

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
     * Handles sending messages to ChatGPT and processing responses.
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

        // Get the existing conversation from the session
        $session = Craft::$app->getSession();
        $conversation = $session->get('sidekickConversation', []);

        // Append the user's message to the conversation
        $conversation[] = ['role' => 'user', 'content' => $message];

        // Check if this is the first message to send all Twig files
        if (count($conversation) === 1) { // if only user message exists
            $this->sendAllTwigFiles($conversation);
        }

        // Get the current version of Craft
        $currentVersion = Craft::$app->getVersion();

        // Prepare the system prompt
        $systemPrompt = "You are an assistant that helps manage Twig templates and module files for a Craft CMS website. The current version of Craft is {$currentVersion}. You can read, create, rewrite, and delete Twig files as requested. Use the following tags to denote actions:\n\n" .
            "[CREATE_FILE]\n" .
            "Path: relative/path/to/file.twig\n" .
            "Content: ```twig\n" .
            "<!-- Your Twig content here -->\n" .
            "```\n" .
            "[/CREATE_FILE]\n\n" .
            "[REWRITE_FILE]\n" .
            "Path: relative/path/to/file.twig\n" .
            "Content: ```twig\n" .
            "<!-- New content here -->\n" .
            "```\n" .
            "[/REWRITE_FILE]\n\n" .
            "[DELETE_FILE]\n" .
            "Path: relative/path/to/file.twig\n" .
            "[/DELETE_FILE]\n\n" .
            "Always confirm destructive actions like file deletions with the user.";

        // Prepare the OpenAI API request
        $apiRequest = [
            'model' => Sidekick::$aiModel,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $conversation
            ),
        ];

        // Call the OpenAI API
        $openAIService = Sidekick::$plugin->openAIService;
        $apiResponse = $openAIService->callChatCompletion($apiRequest);

        if (!$apiResponse['success']) {
            // Log the error for debugging
            Craft::error("OpenAI API Error: " . $apiResponse['error'], __METHOD__);

            return $this->asJson([
                'success' => false,
                'error' => $apiResponse['error'],
            ]);
        }

        $assistantMessage = $apiResponse['results'];

        // Append the assistant's response to the conversation
        $conversation[] = ['role' => 'assistant', 'content' => $assistantMessage];

        // Save the updated conversation back to the session
        $session->set('sidekickConversation', $conversation);

        // Process any file operations requested by the assistant
        $fileOperationResponse = $this->processAssistantMessage($assistantMessage);

        return $this->asJson([
            'success' => true,
            'message' => $assistantMessage,
            'fileOperation' => $fileOperationResponse,
        ]);
    }

    /**
     * Retrieves the current conversation from the session.
     *
     * @return Response
     * @throws MissingComponentException
     */
    public function actionGetConversation(): Response
    {
        // Retrieve the conversation from the session
        $session = Craft::$app->getSession();
        $conversation = $session->get('sidekickConversation', []);

        // Return the conversation as JSON
        return $this->asJson([
            'success' => true,
            'conversation' => $conversation,
        ]);
    }

    /**
     * Clears the current conversation from the session.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionClearConversation(): Response
    {
        $this->requirePostRequest();

        try {
            // Get the session component
            $session = Craft::$app->getSession();

            // Clear the conversation from the session
            $session->remove('sidekickConversation');

            return $this->asJson([
                'success' => true,
                'message' => 'Conversation has been cleared.',
            ]);
        } catch (Exception $e) {
            Craft::error('Error clearing conversation: ' . $e->getMessage(), __METHOD__);
            return $this->asJson([
                'success' => false,
                'message' => 'Failed to clear the conversation.',
            ]);
        }
    }

    /**
     * Sends all Twig files as foundational context to the GPT API.
     *
     * @param array &$conversation
     * @return void
     */
    private function sendAllTwigFiles(array &$conversation): void
    {
        $fileService = Sidekick::$plugin->fileManagementService;
        $twigFiles = $fileService->listTwigTemplates();

        Craft::info("Found " . count($twigFiles) . " Twig files to send.", __METHOD__);

        foreach ($twigFiles as $filePath) {
            $content = $fileService->readFile(ltrim($filePath, '/\\'));
            if ($content !== null) {
                Craft::info("Sending file: {$filePath}", __METHOD__);
                $conversation[] = [
                    'role' => 'system',
                    'content' => "File: {$filePath}\nContent:\n```twig\n{$content}\n```",
                ];
            } else {
                Craft::warning("Failed to read file: {$filePath}", __METHOD__);
            }
        }
    }

    /**
     * Processes the assistant's message to handle file operations.
     *
     * @param string $message
     * @return array|null
     */
    private function processAssistantMessage(string $message): ?array
    {
        // Detect if the message contains a file operation command
        if (preg_match('/\[(CREATE_FILE|REWRITE_FILE|DELETE_FILE)\](.*?)\[\1\]/s', $message, $matches)) {
            $operation = $matches[1];
            $content = $matches[2];

            switch ($operation) {
                case 'CREATE_FILE':
                    return $this->handleCreateFile($content);
                case 'REWRITE_FILE':
                    return $this->handleRewriteFile($content);
                case 'DELETE_FILE':
                    return $this->handleDeleteFile($content);
                default:
                    return ['error' => 'Unknown file operation command.'];
            }
        }

        // Check if a deletion confirmation is pending
        if ($this->isDeletionConfirmationPending()) {
            return $this->handleConfirmDeleteFile($message);
        }

        return null;
    }

    /**
     * Checks if a file deletion is pending confirmation.
     *
     * @return bool
     * @throws MissingComponentException
     */
    private function isDeletionConfirmationPending(): bool
    {
        return Craft::$app->getSession()->has('pendingDeleteFile');
    }

    /**
     * Handles the creation of a new Twig file.
     *
     * @param string $content
     * @return array
     */
    private function handleCreateFile(string $content): array
    {
        // Check user permissions
        if (!Craft::$app->user->can('sidekick-create-update-templates')) {
            return ['success' => false, 'message' => 'You do not have permission to create Twig templates.'];
        }

        // Parse the command content
        if (preg_match('/Path:\s*(.*?)\nContent:\s*```twig\s*(.*?)\s*```/s', $content, $matches)) {
            $filePath = trim($matches[1]);
            $fileContent = trim($matches[2]);

            // Get the FileManagementService
            $fileService = Sidekick::$plugin->fileManagementService;

            // Create the file
            $result = $fileService->createFile($filePath, $fileContent);

            if ($result === true) {
                return ['success' => true, 'message' => "File `{$filePath}` created successfully."];
            } else {
                return ['success' => false, 'message' => $result];
            }
        }

        return ['success' => false, 'message' => 'Invalid CREATE_FILE command format.'];
    }

    /**
     * Handles the rewriting of an existing Twig file.
     *
     * @param string $content
     * @return array
     */
    private function handleRewriteFile(string $content): array
    {
        // Check user permissions
        if (!Craft::$app->user->can('sidekick-create-update-templates')) {
            return ['success' => false, 'message' => 'You do not have permission to rewrite Twig templates.'];
        }

        // Parse the command content
        if (preg_match('/Path:\s*(.*?)\nContent:\s*```twig\s*(.*?)\s*```/s', $content, $matches)) {
            $filePath = trim($matches[1]);
            $newContent = trim($matches[2]);

            // Get the FileManagementService
            $fileService = Sidekick::$plugin->fileManagementService;

            // Rewrite the file
            $result = $fileService->rewriteFile($filePath, $newContent);

            if ($result === true) {
                return ['success' => true, 'message' => "File `{$filePath}` rewritten successfully."];
            } else {
                return ['success' => false, 'message' => $result];
            }
        }

        return ['success' => false, 'message' => 'Invalid REWRITE_FILE command format.'];
    }

    /**
     * Handles the deletion of an existing Twig file.
     * Initiates a confirmation step.
     *
     * @param string $content
     * @return array
     * @throws MissingComponentException
     */
    private function handleDeleteFile(string $content): array
    {
        // Check user permissions
        if (!Craft::$app->user->can('sidekick-create-update-templates')) {
            return ['success' => false, 'message' => 'You do not have permission to delete Twig templates.'];
        }

        // Parse the command content
        if (preg_match('/Path:\s*(.*?)\s*/s', $content, $matches)) {
            $filePath = trim($matches[1]);

            // Store the file path in session for confirmation
            Craft::$app->getSession()->set('pendingDeleteFile', $filePath);

            // Respond with a confirmation prompt
            return [
                'success' => true,
                'message' => "Are you sure you want to delete `{$filePath}`? Please confirm by typing 'Yes' or cancel by typing 'No'.",
                'requiresConfirmation' => true,
            ];
        }

        return ['success' => false, 'message' => 'Invalid DELETE_FILE command format.'];
    }

    /**
     * Handles the confirmation response for file deletion.
     *
     * @param string $content
     * @return array
     * @throws MissingComponentException
     */
    private function handleConfirmDeleteFile(string $content): array
    {
        $session = Craft::$app->getSession();
        $filePath = $session->get('pendingDeleteFile');

        if (!$filePath) {
            return ['success' => false, 'message' => 'No pending file deletion to confirm.'];
        }

        $confirmation = strtolower(trim($content));

        if ($confirmation === 'yes') {
            // Proceed with deletion
            $fileService = Sidekick::$plugin->fileManagementService;
            $result = $fileService->deleteFile($filePath);

            // Clear the pending deletion from session
            $session->remove('pendingDeleteFile');

            if ($result === true) {
                return ['success' => true, 'message' => "File `{$filePath}` deleted successfully."];
            } else {
                return ['success' => false, 'message' => $result];
            }
        } elseif ($confirmation === 'no') {
            // Cancel deletion
            $session->remove('pendingDeleteFile');
            return ['success' => true, 'message' => "File deletion for `{$filePath}` has been canceled."];
        } else {
            return ['success' => false, 'message' => "Invalid confirmation response. Please type 'Yes' to confirm or 'No' to cancel."];
        }
    }
}

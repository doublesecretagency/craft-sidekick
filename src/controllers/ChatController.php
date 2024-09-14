<?php

namespace doublesecretagency\sidekick\controllers;

use Craft;
use craft\web\Controller;
use doublesecretagency\sidekick\Sidekick;
use yii\web\Response;
use yii\web\BadRequestHttpException;

class ChatController extends Controller
{
    // Restrict access to authenticated users
    protected array|int|bool $allowAnonymous = false;

    /**
     * Renders the chat interface.
     */
    public function actionIndex(): Response
    {
        return $this->renderTemplate('sidekick/chat');
    }

    /**
     * Handles sending messages to ChatGPT and processing responses.
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

        // Prepare the system prompt
        $systemPrompt = "You are an assistant that helps manage Twig templates and module files for a Craft CMS website. You can read and write files as requested, but always ensure to confirm actions with the user.";

        // Prepare the OpenAI API request
        $apiRequest = [
            'model' => 'gpt-4',
            'messages' => array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $conversation
            ),
        ];

        // Call the OpenAI API
        $openAIService = Sidekick::$plugin->openAIService;
        $apiResponse = $openAIService->callChatCompletion($apiRequest);

        if (!$apiResponse['success']) {
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
     * Processes the assistant's message to handle file operations.
     */
    private function processAssistantMessage(string $message): ?array
    {
        // Simple pattern matching to detect file operations
        if (preg_match('/\[([A-Z_]+)\](.*?)\[\/[A-Z_]+\]/s', $message, $matches)) {
            $operation = $matches[1];
            $content = $matches[2];

            switch ($operation) {
                case 'READ_FILE':
                    return $this->handleReadFile($content);
                case 'WRITE_FILE':
                    return $this->handleWriteFile($content);
                default:
                    return null;
            }
        }

        return null;
    }

    private function handleReadFile(string $content): array
    {
        $filePath = trim($content);

        // Validate and resolve the file path
        if (!$this->isValidFilePath($filePath)) {
            return ['error' => 'Invalid file path.'];
        }

        $fileContent = Sidekick::$plugin->fileManagementService->readFile($filePath);

        if ($fileContent === null) {
            return ['error' => 'Failed to read the file.'];
        }

        // Append the file content to the conversation
        $session = Craft::$app->getSession();
        $conversation = $session->get('sidekickConversation', []);
        $conversation[] = [
            'role' => 'assistant',
            'content' => "Here is the content of `$filePath`:\n\n```twig\n{$fileContent}\n```",
        ];
        $session->set('sidekickConversation', $conversation);

        return ['success' => true];
    }

    private function handleWriteFile(string $content): array
    {
        // Extract file path and new content
        if (preg_match('/Path:\s*(.*?)\nContent:\s*```(.*?)```/s', $content, $matches)) {
            $filePath = trim($matches[1]);
            $newContent = $matches[2];

            // Validate and resolve the file path
            if (!$this->isValidFilePath($filePath)) {
                return ['error' => 'Invalid file path.'];
            }

            // Optionally, ask for user confirmation before writing
            // For simplicity, we'll proceed to write the file

            $writeSuccess = Sidekick::$plugin->fileManagementService->writeFile($filePath, $newContent);

            if (!$writeSuccess) {
                return ['error' => 'Failed to write to the file.'];
            }

            return ['success' => true];
        }

        return ['error' => 'Invalid write format.'];
    }

    private function isValidFilePath(string $filePath): bool
    {
        $allowedDirs = [
            realpath(CRAFT_TEMPLATES_PATH),
            realpath(Craft::getAlias('@modules')),
        ];

        $realPath = realpath($filePath);

        if (!$realPath) {
            return false;
        }

        foreach ($allowedDirs as $dir) {
            if (strpos($realPath, $dir) === 0) {
                return true;
            }
        }

        return false;
    }
}

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

        // Append the user's message to the conversation
        $session = Craft::$app->getSession();
        $conversation = $session->get('sidekickConversation', []);
        $conversation[] = ['role' => 'user', 'content' => $message];
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
            Craft::error("AI API Error: " . $apiResponse['error'], __METHOD__);
            return $this->asJson([
                'success' => false,
                'error' => $apiResponse['error'],
            ]);
        }

        $assistantMessage = $apiResponse['results'];
        $decodedJson = json_decode($assistantMessage, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($decodedJson['actions'])) {
            // Execute the actions
            $executionResults = $this->_executeActions($decodedJson['actions']);

            // Append the assistant's message to the conversation
            $conversation[] = ['role' => 'assistant', 'content' => $assistantMessage];
            $session->set('sidekickConversation', $conversation);

            return $this->asJson([
                'success' => true,
                'message' => $executionResults['message'],
            ]);
        } else {
            // Append the assistant's message to the conversation
            $conversation[] = ['role' => 'assistant', 'content' => $assistantMessage];
            $session->set('sidekickConversation', $conversation);

            return $this->asJson([
                'success' => true,
                'message' => $assistantMessage,
            ]);
        }
    }

    /**
     * Executes a list of actions provided by the assistant.
     *
     * @param array $actions
     * @return array
     */
    private function _executeActions(array $actions): array
    {
        foreach ($actions as $action) {
            $result = $this->_handleAction($action);
            if (!$result['success']) {
                return $result; // Return on first failure
            }
        }
        return ['success' => true, 'message' => 'All actions executed successfully.'];
    }

    /**
     * Handles a single action.
     *
     * @param array $action
     * @return array
     */
    private function _handleAction(array $action): array
    {
        $fileService = Sidekick::$plugin->fileManagementService;

        switch ($action['action']) {
            case 'update_element':
                $filePath = $action['file'];
                $element = $action['element'];
                $newValue = $action['new_value'];

                $content = $fileService->readFile($filePath);
                if ($content === null) {
                    return ['success' => false, 'message' => "File not found: {$filePath}"];
                }

                // Modify the content
                $modifiedContent = $this->_modifyElementInContent($content, $element, $newValue);

                $result = $fileService->rewriteFile($filePath, $modifiedContent);
                if ($result) {
                    return ['success' => true, 'message' => "Element '{$element}' in '{$filePath}' updated successfully."];
                } else {
                    return ['success' => false, 'message' => "Failed to write changes to '{$filePath}'."];
                }

            // Handle other actions...

            default:
                return ['success' => false, 'message' => "Unsupported action: {$action['action']}"];
        }
    }

    /**
     * Modifies a specific element in the content.
     *
     * @param string $content
     * @param string $element
     * @param string $newValue
     * @return string
     */
    private function _modifyElementInContent(string $content, string $element, string $newValue): string
    {
        // Implement logic to modify the specific element in the content
        // For example, using regular expressions or DOM parsing
        // Simplified example using regex:

        $pattern = "/(<{$element}[^>]*>)(.*?)(<\/{$element}>)/s";
        $replacement = "\$1{$newValue}\$3";
        return preg_replace($pattern, $replacement, $content);
    }
}

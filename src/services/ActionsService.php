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

namespace doublesecretagency\sidekick\services;

use craft\base\Component;
use doublesecretagency\sidekick\skills\read\Templates;

/**
 * Class ActionsService
 *
 * Handles the execution of actions received from the assistant.
 */
class ActionsService extends Component
{
    /**
     * Get a list of valid actions.
     *
     * @return array
     */
    public function getValidActions(): array
    {
        $validActions = [];
        $methods = (new \ReflectionClass(Templates::class))->getMethods();

        foreach ($methods as $method) {
            $validActions[] = $method->getName();
        }

        return $validActions;
    }

    /**
     * Executes a list of actions provided by the assistant.
     *
     * @param array $actions
     * @return array
     */
    public function executeActions(array $actions): array
    {
        $messages = [];
        $content = '';

        // Execute each action in the list
        foreach ($actions as $action) {
            // Handle the action
            $result = $this->_handleAction($action);

            // Collect the message from the action result
            if (isset($result['message'])) {
                $messages[] = $result['message'];
            }

            // Collect content if present
            if (isset($result['content'])) {
                $content .= "{$result['content']}\n";
            }

            // Return immediately if an action fails
            if (!$result['success']) {
                return [
                    'success' => false,
                    'messages' => $messages,
                    'message' => $result['message'],
                    'content' => $content,
                ];
            }
        }

        // Return success message if all actions succeed
        return [
            'success' => true,
            'messages' => $messages,
            'message' => 'All actions executed successfully.',
            'content' => $content,
        ];
    }

    /**
     * Handles a single action.
     *
     * @param array $action
     * @return array
     */
    private function _handleAction(array $action): array
    {
        // Get the action type
        $actionType = $action['action'] ?? null;

        // Validate the action type
        if (!$actionType) {
            return ['success' => false, 'message' => "Action type is missing."];
        }

        // Check if the action type is supported
        if (!method_exists(Templates::class, $actionType)) {
            return ['success' => false, 'message' => "Unsupported action: {$actionType}"];
        }

        // Call the static method
        return Templates::$actionType($action);
    }
}

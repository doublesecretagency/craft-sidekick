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

use Craft;
use craft\errors\MissingComponentException;
use doublesecretagency\sidekick\constants\Session;
use doublesecretagency\sidekick\models\ChatMessage;
use yii\base\Component;

class ChatService extends Component
{
    /**
     * Clear the existing conversation from the session.
     */
    public function clearConversation(): void
    {
        try {

            // Get the session service
            $session = Craft::$app->getSession();

            // Clear the assistant and thread IDs from the session
            $session->remove(Session::ASSISTANT_ID);
            $session->remove(Session::THREAD_ID);

            // Clear the conversation from the session
            $session->remove(Session::CHAT_HISTORY);

        } catch (MissingComponentException $e) {

            // Log an error message
            Craft::error("Unable to clear conversation from the session.", __METHOD__);

        }
    }

    /**
     * Retrieve the existing conversation from the session.
     *
     * @return array
     */
    public function getConversation(): array
    {
        try {

            // Get the existing conversation from the session
            $conversation = Craft::$app->getSession()->get(Session::CHAT_HISTORY) ?? [];

            // If not a valid conversation
            if (!$conversation || !is_array($conversation)) {
                // Return an empty conversation
                return [];
            }

            // Return the complete conversation
            return $conversation;

        } catch (MissingComponentException $e) {

            // Log an error message
            Craft::error("Unable to get conversation from the session.", __METHOD__);

            // Return an error message
            return [
                new ChatMessage([
                    'role' => ChatMessage::ERROR,
                    'message' => "Unable to load the conversation."
                ])
            ];

        }
    }

    /**
     * Add a message to the conversation history.
     *
     * @param ChatMessage $message
     */
    public function addMessage(ChatMessage $message): void
    {
        // Track the message
        Craft::info("Appending message to the conversation history.", __METHOD__);

        // Get the existing conversation from the session
        $conversation = $this->getConversation();

        // Append the new message to the conversation
        $conversation[] = $message;

        try {
            // Save the updated conversation to the session
            Craft::$app->getSession()->set(Session::CHAT_HISTORY, $conversation);
        } catch (MissingComponentException $e) {
            // Log an error message
            Craft::error("Unable to save updated conversation to the session.", __METHOD__);
        }
    }
}

<?php

namespace doublesecretagency\sidekick\helpers;

use Craft;
use craft\errors\MissingComponentException;
use doublesecretagency\sidekick\models\Message;
use doublesecretagency\sidekick\Sidekick;

class ChatHistory
{
    /**
     * Session key for storing the conversation.
     *
     * @const
     */
    protected const SESSION = 'sidekickConversation';

    /**
     * Clear the existing conversation from the session.
     */
    public static function clearConversation(): void
    {
        try {

            // Clear the conversation from the session
            Craft::$app->getSession()->remove(static::SESSION);

        } catch (MissingComponentException $e) {

            // Log an error message
            Craft::error('Unable to clear conversation from the session.', __METHOD__);

        }
    }

    /**
     * Retrieve the existing conversation from the session.
     *
     * @return array
     */
    public static function getConversation(): array
    {
        try {

            // Get the existing conversation from the session
            $conversation = Craft::$app->getSession()->get(static::SESSION) ?? [];

            // If not a valid conversation
            if (!$conversation || !is_array($conversation)) {
                // Return an empty conversation
                return [];
            }

            // Return the complete conversation
            return $conversation;

        } catch (MissingComponentException $e) {

            // Log an error message
            Craft::error('Unable to get conversation from the session.', __METHOD__);

            // Return a system message
            return [
                Sidekick::$plugin->openAi->newSystemMessage('Unable to load the conversation.')
            ];

        }
    }

    /**
     * Add a message to the conversation history.
     *
     * @param Message $message
     */
    public static function addMessage(Message $message): void
    {
        // Get the existing conversation from the session
        $conversation = static::getConversation();

        // Append the new message to the conversation
        $conversation[] = $message;

        try {
            // Save the updated conversation to the session
            Craft::$app->getSession()->set(static::SESSION, $conversation);
        } catch (MissingComponentException $e) {
            // Log an error message
            Craft::error('Unable to save updated conversation to the session.', __METHOD__);
        }
    }
}

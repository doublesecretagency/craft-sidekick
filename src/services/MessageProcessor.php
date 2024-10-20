<?php

namespace doublesecretagency\sidekick\services;

use Craft;
use craft\base\Component;
use craft\errors\MissingComponentException;
use doublesecretagency\sidekick\constants\Constants;
use doublesecretagency\sidekick\models\Message;

class MessageProcessor extends Component
{
    /**
     * @var array The conversation history.
     */
    private array $_conversation = [];

    /**
     * Loads the conversation history from the session.
     */
    public function loadConversation(): void
    {
        if (!empty($this->_conversation)) {
            return;
        }

        try {
            $sessionConversation = Craft::$app->getSession()->get(
                Constants::CHAT_SESSION,
                []
            );

            // Reconstruct Message objects if necessary
            $this->_conversation = array_map(function ($messageData) {
                return new Message(
                    $messageData['role'],
                    $messageData['content'],
                    $messageData['messageType'] ?? Constants::MESSAGE_TYPE_CONVERSATIONAL
                );
            }, $sessionConversation);
        } catch (MissingComponentException $e) {
            // Handle exception if necessary
        }
    }

    /**
     * Saves the conversation history to the session.
     */
    public function saveConversation(): void
    {
        try {
            Craft::$app->getSession()->set(
                Constants::CHAT_SESSION,
                $this->_conversation
            );
        } catch (MissingComponentException $e) {
            // Handle exception if necessary
        }
    }

    /**
     * Appends a message to the conversation.
     *
     * @param Message $message
     */
    public function appendMessage(Message $message): void
    {
        $this->loadConversation();
        $this->_conversation[] = $message->toArray();
        $this->saveConversation();
    }

    /**
     * Gets the current conversation.
     *
     * @return array
     */
    public function getConversation(): array
    {
        $this->loadConversation();
        // If _conversation contains Message objects
        return array_map(function ($message) {
            return $message instanceof Message ? $message->toArray() : $message;
        }, $this->_conversation);
    }
}

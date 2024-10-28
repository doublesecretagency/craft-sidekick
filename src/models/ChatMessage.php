<?php

namespace doublesecretagency\sidekick\models;

use Craft;
use craft\base\Model;
use doublesecretagency\sidekick\Sidekick;
use yii\base\Exception;

class ChatMessage extends Model
{
    /**
     * List of message types for the chat interface.
     *
     * @const
     */
    public const CONVERSATIONAL = 'conversational';
    public const ERROR = 'error';
    public const SNIPPET = 'snippet';
    public const SYSTEM = 'system';

    // ========================================================================= //

    /**
     * @var string Role of the message sender.
     */
    public string $role;

    /**
     * @var string Content of the message.
     */
    public string $content;

    /**
     * @var string Type of message.
     */
    public string $messageType;

    /**
     * Message constructor.
     *
     * @param array $message
     * @param array $config
     */
    public function __construct(array $message, array $config = [])
    {
        $this->role        = $message['role']        ?? '';
        $this->content     = $message['content']     ?? '';
        $this->messageType = $message['messageType'] ?? self::CONVERSATIONAL;
        parent::__construct($config);
    }

    // ========================================================================= //

    /**
     * Log the message content.
     *
     * @return ChatMessage for chaining
     */
    public function log(): ChatMessage
    {
        // If the message is an error
        if (self::ERROR === $this->messageType) {
            // Log as an error
            Craft::error("{$this->role}: {$this->content}", __METHOD__);
        } else {
            // Log as info
            Craft::info("{$this->role}: {$this->content}", __METHOD__);
        }

        // Return the message for chaining
        return $this;
    }

    // ========================================================================= //

    /**
     * Add message to the chat history.
     *
     * @return ChatMessage for chaining
     */
    public function addToChatHistory(): ChatMessage
    {
        // Add the message to the chat history
        Sidekick::$plugin->chat->addMessage([
            'role' => $this->role,
            'content' => $this->content,
            'messageType' => $this->messageType,
        ]);

        // Return the message for chaining
        return $this;
    }

    /**
     * Add message to the OpenAI thread.
     *
     * @return ChatMessage for chaining
     * @throws Exception
     */
    public function addToOpenAiThread(): ChatMessage
    {
        // Add the message to the OpenAI thread
        Sidekick::$plugin->openAi->addMessage([
            'role' => $this->role,
            'content' => $this->content,
        ]);

        // Return the message for chaining
        return $this;
    }
}

<?php

namespace doublesecretagency\sidekick\models;

use Craft;
use craft\base\Model;
use doublesecretagency\sidekick\helpers\ChatHistory;

class Message extends Model
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
     * @param string $role
     * @param string $content
     * @param string $messageType
     * @param array $config
     */
    public function __construct(string $role, string $content, string $messageType = self::CONVERSATIONAL, array $config = [])
    {
        $this->role = $role;
        $this->content = $content;
        $this->messageType = $messageType;
        parent::__construct($config);
    }

    // ========================================================================= //

    /**
     * Log the message content.
     */
    public function log(): void
    {
        // If the message is an error
        if (self::ERROR === $this->messageType) {
            // Log as an error
            Craft::error("{$this->role}: {$this->content}", __METHOD__);
        } else {
            // Log as info
            Craft::info("{$this->role}: {$this->content}", __METHOD__);
        }
    }

    /**
     * Append message to the chat history.
     */
    public function appendToChatHistory(): void
    {
        // Add this message to the chat history
        ChatHistory::addMessage($this);
    }
}

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

namespace doublesecretagency\sidekick\models;

use Craft;
use craft\base\Model;
use doublesecretagency\sidekick\Sidekick;
use yii\base\Exception;

class ChatMessage extends Model
{
    /**
     * Compatible message roles for OpenAI.
     *
     * @const
     */
    public const ASSISTANT = 'assistant';
    public const USER = 'user';
    public const SYSTEM = 'system';
    public const TOOL = 'tool';

    /**
     * Additional message types.
     *
     * @const
     */
//    public const CONVERSATIONAL = 'conversational';
    public const ERROR = 'error';
    public const SUCCESS = 'success';

    // ========================================================================= //

    /**
     * @var string Role of the message sender.
     */
    public string $role;

    /**
     * @var string Body of the message.
     */
    public string $message;

    /**
     * Message constructor.
     *
     * @param array $message
     * @param array $config
     */
    public function __construct(array $message, array $config = [])
    {
        $this->role    = $message['role']    ?? '';
        $this->message = $message['message'] ?? '';
        parent::__construct($config);
    }

    // ========================================================================= //

    /**
     * Log the message.
     *
     * @return ChatMessage for chaining
     */
    public function log(): ChatMessage
    {
        // Compile log message
        $message = strtoupper($this->role).": {$this->message}";

        // Default log type
        $logType = 'info';

        // If the message is an error
        if (self::ERROR === $this->role) {
            // Log as an error
            $logType = 'error';
        }

        // Log the message
        Craft::$logType($message, __METHOD__);

        // Return the message for chaining
        return $this;
    }

    // ========================================================================= //

    /**
     * Add message to the chat history.
     *
     * @return ChatMessage for chaining
     */
    public function toChatHistory(): ChatMessage
    {
        // Add the message to the chat history
        Sidekick::getInstance()?->chat->addMessage($this);

        // Return the message for chaining
        return $this;
    }

    /**
     * Send message to the chat window.
     *
     * @return ChatMessage for chaining
     */
    public function toChatWindow(): ChatMessage
    {
        // Send the message to the chat window
        Sidekick::getInstance()?->sse->sendMessage($this);

        // Return the message for chaining
        return $this;
    }

    /**
     * Add message to the OpenAI thread.
     *
     * @return ChatMessage for chaining
     * @throws Exception
     */
    public function toOpenAiThread(): ChatMessage
    {
        // Set the message
        $message = $this->message;

        // Switch on the message role
        switch ($this->role) {
            case self::ASSISTANT:
            case self::USER:
                // Valid role for an OpenAI message
                $role = $this->role;
                break;
            case self::ERROR:
                // Consider error to be a user message
                $role = self::USER;
                $message = "SYSTEM ERROR: {$message}";
                break;
            default:
                // Log a warning
                Craft::warning("Invalid message role for API: {$this->role}", __METHOD__);
                // Return the message for chaining
                return $this;
        }

        // Add the message to the OpenAI thread
        Sidekick::getInstance()?->openAi->addMessage([
            'role' => $role,
            'content' => $message,
        ]);

        // Return the message for chaining
        return $this;
    }
}

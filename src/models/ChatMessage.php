<?php

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

    // ========================================================================= //

    /**
     * @var string Role of the message sender.
     */
    public string $role;

    /**
     * @var string Content of the message.
     */
    public string $content;

//    /**
//     * @var string Type of message.
//     */
//    public string $messageType;

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
//        $this->messageType = $message['messageType'] ?? self::CONVERSATIONAL;
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
        // Compile log message
        $message = strtoupper($this->role).": {$this->content}";

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
    public function addToChatHistory(): ChatMessage
    {
        // Add the message to the chat history
        Sidekick::$plugin->chat->addMessage([
            'role' => $this->role,
            'content' => $this->content,
//            'messageType' => $this->messageType,
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
        // Set the content
        $content = $this->content;

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
                $content = "SYSTEM ERROR: {$content}";
                break;
            default:
                // Log a warning
                Craft::warning("Invalid message role for API: {$this->role}", __METHOD__);
                // Return the message for chaining
                return $this;
        }

        // Add the message to the OpenAI thread
        Sidekick::$plugin->openAi->addMessage([
            'role' => $role,
            'content' => $content,
        ]);

        // Return the message for chaining
        return $this;
    }
}

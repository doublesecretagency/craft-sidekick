<?php

namespace doublesecretagency\sidekick\helpers;

use doublesecretagency\sidekick\models\ChatMessage;
use doublesecretagency\sidekick\constants\Chat;

class MessageFactory
{
    /**
     * Create a user message.
     *
     * @param string $content
     * @return ChatMessage
     */
    public static function createUserMessage(string $content): ChatMessage
    {
        return new ChatMessage(
            'user',
            $content,
            ChatMessage::CONVERSATIONAL
        );
    }

    /**
     * Create an assistant message.
     *
     * @param string $content
     * @return ChatMessage
     */
    public static function createAssistantMessage(string $content): ChatMessage
    {
        return new ChatMessage(
            'assistant',
            $content,
            ChatMessage::CONVERSATIONAL
        );
    }

    /**
     * Create a system message.
     *
     * @param string $content
     * @return ChatMessage
     */
    public static function createSystemMessage(string $content): ChatMessage
    {
        return new ChatMessage(
            'system',
            $content,
            ChatMessage::SYSTEM
        );
    }
}

<?php

namespace doublesecretagency\sidekick\helpers;

use doublesecretagency\sidekick\models\Message;
use doublesecretagency\sidekick\constants\Constants;

class MessageFactory
{
    /**
     * Create a user message.
     *
     * @param string $content
     * @return Message
     */
    public static function createUserMessage(string $content): Message
    {
        return new Message(
            'user',
            $content,
            Message::CONVERSATIONAL
        );
    }

    /**
     * Create an assistant message.
     *
     * @param string $content
     * @return Message
     */
    public static function createAssistantMessage(string $content): Message
    {
        return new Message(
            'assistant',
            $content,
            Message::CONVERSATIONAL
        );
    }

    /**
     * Create a system message.
     *
     * @param string $content
     * @return Message
     */
    public static function createSystemMessage(string $content): Message
    {
        return new Message(
            'system',
            $content,
            Message::SYSTEM
        );
    }
}

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

namespace doublesecretagency\sidekick\constants;

class Session
{
    /**
     * Session key for storing the selected AI model.
     *
     * @const
     */
    public const AI_MODEL = 'sidekickSelectedModel';

    /**
     * Session key for storing the generated assistant ID.
     *
     * @const
     */
    public const ASSISTANT_ID = 'sidekickAssistantId';

    /**
     * Session key for storing the generated thread ID.
     *
     * @const
     */
    public const THREAD_ID = 'sidekickThreadId';

    /**
     * Session key for storing the complete conversation.
     *
     * @const
     */
    public const CHAT_HISTORY = 'sidekickConversation';
}

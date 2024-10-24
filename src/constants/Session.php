<?php

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
}

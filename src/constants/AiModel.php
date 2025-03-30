<?php

namespace doublesecretagency\sidekick\constants;

class AiModel
{
    /**
     * Default AI model to use for OpenAI API.
     *
     * @const
     */
    public const DEFAULT = 'gpt-4.5-preview';

    /**
     * List of available AI models.
     *
     * @const
     */
    public const AVAILABLE = [
        'gpt-4.5-preview' => 'GPT-4.5 preview',
        'gpt-4o'          => 'GPT-4o',
        'gpt-4o-mini'     => 'GPT-4o mini',
        'gpt-4'           => 'GPT-4',
        'gpt-4-turbo'     => 'GPT-4 Turbo',
        'gpt-3.5-turbo'   => 'GPT-3.5 Turbo',
    ];
}

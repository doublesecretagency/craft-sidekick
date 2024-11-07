<?php

namespace doublesecretagency\sidekick\constants;

class AiModel
{
    /**
     * Default AI model to use for OpenAI API.
     *
     * @const
     */
    public const DEFAULT = 'gpt-4o';

    /**
     * List of available AI models.
     *
     * @const
     */
    public const AVAILABLE = [
//        'o1-preview'    => 'o1 preview',
//        'o1-mini'       => 'o1 mini',
        'gpt-4o'        => 'GPT-4o',
        'gpt-4o-mini'   => 'GPT-4o mini',
        'gpt-4'         => 'GPT-4',
        'gpt-4-turbo'   => 'GPT-4 Turbo',
        'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
    ];
}

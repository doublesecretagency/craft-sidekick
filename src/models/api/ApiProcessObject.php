<?php

namespace doublesecretagency\sidekick\models\api;

use doublesecretagency\sidekick\constants\AiModel;

class ApiProcessObject extends ApiObject
{
    /**
     * @var string
     */
    public string $model = AiModel::DEFAULT;

    /**
     * @var string|null
     */
    public ?string $instructions = null;

    /**
     * @var array
     */
    public array $tools = [
        [
            'type' => 'code_interpreter'
        ]
    ];

    /**
     * @var float|null
     */
    public ?float $top_p = null;

    /**
     * @var float|null
     */
    public ?float $temperature = null;

    /**
     * @var string
     */
    public string $response_format = 'auto';
}

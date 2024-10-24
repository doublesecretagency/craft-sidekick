<?php

namespace doublesecretagency\sidekick\models\api;

use doublesecretagency\sidekick\constants\AiModel;
use doublesecretagency\sidekick\helpers\SystemPrompt;

/**
 * @see https://platform.openai.com/docs/api-reference/assistants/object
 */
class Assistant extends ApiProcessObject
{
    /**
     * @var string
     */
    public string $object = 'assistant';

    /**
     * @var string
     */
    public string $name = 'Sidekick';

    /**
     * @var string|null
     */
    public ?string $description = null;

    /**
     * @inheritdoc
     */
    public function __construct(array $payload = [], array $config = [])
    {
        // Call the parent constructor
        parent::__construct($config);

        // Set default values
        $payload['model']        = $payload['model']        ?? AiModel::DEFAULT;
        $payload['instructions'] = $payload['instructions'] ?? SystemPrompt::getPrompt();
        $payload['name']         = $payload['name']         ?? $this->name;

////        $payload['tools'] = $payload['tools'] ?? $this->_getTools();
//        $payload['tools'] = $payload['tools'] ?? [
//            ['type' => 'code_interpreter']
//        ];

        // Create a new assistant
        $this->_createApiObject('v1/assistants', $payload);
    }
}

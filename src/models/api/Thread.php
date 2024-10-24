<?php

namespace doublesecretagency\sidekick\models\api;

/**
 * @see https://platform.openai.com/docs/api-reference/threads/object
 */
class Thread extends ApiObject
{
    /**
     * @var string
     */
    public string $object = 'thread';

    /**
     * @var array
     */
    public array $tool_resources = [];

    /**
     * @inheritdoc
     */
    public function __construct(array $config = [])
    {
        // Call the parent constructor
        parent::__construct($config);

        // Create a new thread
        $this->_createApiObject('v1/threads');
    }
}

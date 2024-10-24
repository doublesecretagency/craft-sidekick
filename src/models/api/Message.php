<?php

namespace doublesecretagency\sidekick\models\api;

/**
 * @see https://platform.openai.com/docs/api-reference/messages/object
 */
class Message extends ApiObject
{
    /**
     * @var string
     */
    public string $object = 'thread.message';

    /**
     * @var string|null
     */
    public ?string $assistant_id = null;

    /**
     * @var string|null
     */
    public ?string $thread_id = null;

    /**
     * @var string|null
     */
    public ?string $run_id = null;

    /**
     * @var string
     */
    public string $role = 'system';

    /**
     * @var array
     */
    public array $content = [
        [
            'type' => 'text',
            'text' => [
                'value' => 'Lorem ipsum dolor sit amet.',
                'annotations' => []
            ]
        ]
    ];

    /**
     * @var array
     */
    public array $attachments = [];

    /**
     * @inheritdoc
     */
    public function __construct(string $threadId, array $payload = [], array $config = [])
    {
        // Call the parent constructor
        parent::__construct($config);

        // Create a new message
        $this->_createApiObject("v1/threads/{$threadId}/messages", $payload);
    }
}

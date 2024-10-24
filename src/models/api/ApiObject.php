<?php

namespace doublesecretagency\sidekick\models\api;

use craft\base\Model;
use doublesecretagency\sidekick\services\OpenAIService;
use doublesecretagency\sidekick\Sidekick;

class ApiObject extends Model
{
    /**
     * @var string|null
     */
    public ?string $id = null;

    /**
     * @var string
     */
    public string $object = '';

    /**
     * @var int|null
     */
    public ?int $created_at = null;

    /**
     * @var array
     */
    public array $metadata = [];

    /**
     * @var OpenAIService|null
     */
    protected ?OpenAIService $_openAi = null;

    // ========================================================================= //

    /**
     * ApiObject constructor.
     *
     * @param string $endpoint
     * @param array $payload
     */
    protected function _createApiObject(string $endpoint, array $payload = []): void
    {
        // Load the OpenAI service
        $this->_openAi = $this->_openAi ?? Sidekick::$plugin->openAi;

        // Get response data from the API
        $response = $this->_openAi->callApi('post', $endpoint, $payload);

        // If the response was unsuccessful, bail
        if (!$response['success']) {
            return;
        }

        // Loop through the response array
        foreach ($response['results'] as $key => $value) {
            // If the key exists as a property of this class
            if (property_exists($this, $key)) {
                // Set the property value
                $this->$key = $value;
            }
        }
    }
}

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

namespace doublesecretagency\sidekick\models;

use Craft;
use craft\base\Model;
use craft\helpers\Json;
use doublesecretagency\sidekick\Sidekick;

class FunctionCall extends Model
{
    /**
     * @var string ID of function call.
     */
    public string $callId;

    /**
     * @var string Tool name.
     */
    public string $name;

    /**
     * @var string Tool arguments.
     */
    public string $arguments;

    /**
     * @var string Tool output.
     */
    public string $output;

    /**
     * Function call output constructor.
     *
     * @param array $response
     * @param array $config
     */
    public function __construct(array $response, array $config = [])
    {
        $this->callId    = $response['callId']    ?? '';
        $this->name      = $response['name']      ?? '';
        $this->arguments = $response['arguments'] ?? '';
        $this->output    = $response['output']    ?? '';
        parent::__construct($config);
    }

    // ========================================================================= //

    /**
     * Log the tool output.
     *
     * @param $method
     * @return FunctionCall for chaining
     */
    public function log($method): FunctionCall
    {
        // Log the tool response
        Craft::info(Json::decodeIfJson($this->output), $method);

        // Return the tool output for chaining
        return $this;
    }

    // ========================================================================= //

    /**
     * Add tool output to the chat history.
     *
     * @return FunctionCall for chaining
     */
    public function toChatHistory(): FunctionCall
    {
        // Add the tool output to the chat history
        Sidekick::getInstance()?->chat->addMessage($this);

        // Return the tool output for chaining
        return $this;
    }
}

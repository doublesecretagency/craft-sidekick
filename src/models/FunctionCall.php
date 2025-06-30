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
        // Get the size of the tool output
        $outputSize = mb_strlen($this->output, '8bit');

        // Get the tool details
        $tool = new ToolFunction($this->name);

        // Log the tool output size
        Craft::info("{$outputSize} bytes output by `{$tool->class}::{$tool->method}`.", $method);

        // Get the output results
        $results = Json::decodeIfJson($this->output);

        // If results are a string, prepend a newline
        if (is_string($results)) {
            $results = "\n{$results}";
        }

        // Log the tool results
        Craft::info($results, $method);

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

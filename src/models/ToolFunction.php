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
use Throwable;
use yii\base\Exception;

class ToolFunction extends Model
{
    /**
     * @var string Original hashed name of the tool function.
     */
    public string $hashed;

    /**
     * @var string Complete namespace of the tool function.
     */
    public string $namespace;

    /**
     * @var string Class name of the tool function.
     */
    public string $class;

    /**
     * @var string Method name of the tool function.
     */
    public string $method;

    /**
     * Tool function constructor.
     *
     * @param string $hashedName
     * @param array $config
     */
    public function __construct(string $hashedName, array $config = [])
    {
        // Get hashed name
        $this->hashed = $hashedName;

        // Get the namespace hashes from the OpenAI instance
        $hashes = Sidekick::getInstance()?->openAi->skillSetsHash;

        // Split the full name into parts
        $nameParts = explode('-', $hashedName);

        // Attempt to convert hash to namespace
        $nameParts[0] = ($hashes[$nameParts[0]] ?? $nameParts[0]);

        // Set the namespace, class, and method names
        $this->namespace = $nameParts[0];
        $this->class = $nameParts[1];
        $this->method = $nameParts[2];

        // Run the parent constructor
        parent::__construct($config);
    }

    /**
     * Run the tool function.
     *
     * @param string $arguments
     * @return SkillResponse
     * @throws Exception
     */
    public function run(string $arguments): SkillResponse
    {
        try {

            // Get the function arguments (cast to array)
            $args = (array) Json::decode($arguments);

            // Get namespaced class and method
            $namespaced = "{$this->namespace}\\{$this->class}";

            // If the tool function does not exist, throw an exception
            if (!method_exists($namespaced, $this->method)) {
                throw new Exception("Tool method does not exist: `{$namespaced}::{$this->method}`");
            }

            // Compile array for logging
            $logArgs = [];

            // Loop through each argument
            foreach ($args as $key => $value) {
                // Decode the argument if it's a JSON string
                $logArgs[$key] = Json::decodeIfJson($value);
            }

            // Denote which tool method is being run
            Craft::info("Calling tool `{$this->class}::{$this->method}`.", __METHOD__);

            // If no parameters, say so
            if (!$logArgs) {
                $logArgs = "(no parameters)";
            }

            // Log the tool arguments
            Craft::info($logArgs, "{$namespaced}::{$this->method}");

            // Call the tool function
            return $namespaced::{$this->method}(...$args);

        } catch (Throwable $e) {

            // Return error message
            return new SkillResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);

        }
    }
}

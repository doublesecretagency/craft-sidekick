<?php

namespace doublesecretagency\sidekick\models\api;

use Craft;
use yii\base\Exception;

/**
 * @see https://platform.openai.com/docs/api-reference/runs/object
 */
class Run extends ApiProcessObject
{
    /**
     * @var string
     */
    public string $object = 'thread.run';

    /**
     * @var string|null
     */
    public ?string $assistant_id = null;

    /**
     * @var string|null
     */
    public ?string $thread_id = null;

    /**
     * queued, in_progress, requires_action,
     * cancelling, cancelled, failed,
     * completed, incomplete, expired
     *
     * @var string
     */
    public string $status = 'queued';

    /**
     * @var array|null
     */
    public ?array $required_action = null;

    /**
     * @var int|null
     */
    public ?int $started_at = null;

    /**
     * @var int|null
     */
    public ?int $expires_at = null;

    /**
     * @var int|null
     */
    public ?int $cancelled_at = null;

    /**
     * @var int|null
     */
    public ?int $failed_at = null;

    /**
     * @var int|null
     */
    public ?int $completed_at = null;

    /**
     * @var string|null
     */
    public ?string $last_error = null;

    /**
     * @var string|null
     */
    public ?string $incomplete_details = null;

    /**
     * @var array|null
     */
    public ?array $usage = null;

    /**
     * @var int|null
     */
    public ?int $max_prompt_tokens = null;

    /**
     * @var int|null
     */
    public ?int $max_completion_tokens = null;

    /**
     * @var array
     */
    public array $truncation_strategy = [
        'type' => 'auto',
        'last_messages' => null
    ];

    /**
     * @var string
     */
    public string $tool_choice = 'auto';

    /**
     * @var bool
     */
    public bool $parallel_tool_calls = true;

    /**
     * @inheritdoc
     */
    public function __construct(string $threadId, array $payload = [], array $config = [])
    {
        // Call the parent constructor
        parent::__construct($config);

        // Create a new run
        $this->_createApiObject("v1/threads/{$threadId}/runs", $payload);
    }

    // ========================================================================= //

    /**
     * Wait for the run to complete by polling its status.
     *
     * @return Run
     * @throws Exception
     */
    public function waitForCompletion(): Run
    {
        // Mark the start time
        $startTime = time();

        // Initialize the retry count
        $retryCount = 0;

        // Configure delay and max retries
        $delaySeconds = 2;
        $maxRetries = 20;

        // Convert delay to milliseconds
        $delay = $delaySeconds * 1000;

        // Poll the run status until it is completed
        do {

            try {
                // Call API to get the run info
                $response = $this->_openAi->callApi('get', "v1/threads/{$this->thread_id}/runs/{$this->id}");

                // If the API response was invalid
                if (!$response) {
                    $error = "Invalid response from API.";
                    Craft::error($error, __METHOD__);
                    throw new Exception($error);
                }

                // If the API call was not successful
                if (!($response['success'] ?? false)) {
                    $error = ($response['error'] ?? "Unknown error.");
                    Craft::error($error, __METHOD__);
                    throw new Exception($error);
                }

            } catch (\Exception $e) {

                // Log error and throw an exception
                $error = "Failed to get the run information. {$e->getMessage()}";
                Craft::error($error, __METHOD__);
                throw new Exception($error);

            }

            // Get the results from the response
            $results = $response['results'] ?? [];

            // Get the status of the run
            $status = ($results['status'] ?? 'unknown');

            // If run has completed, return self for chaining
            if ($status === 'completed') {
                return $this;
            }

            // If run has failed
            if ($status === 'failed') {
                // Log error and throw an exception
                $error = ($results['last_error']['message'] ?? 'Failed with an unknown error.');
                Craft::error($error, __METHOD__);
                throw new Exception($error);
            }

            // Whether we are still waiting for the run to complete
            $waiting = in_array($status, ['queued','in_progress']);

            // If no longer waiting
            if (!$waiting) {
                // Log error and throw an exception
                $error = "Run completed with a \"{$status}\" status.";
                Craft::error($error, __METHOD__);
                throw new Exception($error);
            }

            // Delay before retrying
            usleep($delay);

            // Increment the retry count
            $retryCount++;

        // Loop until max retries reached
        } while ($retryCount <= $maxRetries);

        // Get the number of seconds that have passed
        $seconds = time() - $startTime;

        // Log error and throw an exception
        $error = "Run timed out after {$seconds} seconds with a \"{$status}\" status.";
        Craft::error($error, __METHOD__);
        throw new Exception($error);
    }
}

<?php

namespace doublesecretagency\sidekick\log;

use Exception;
use yii\log\FileTarget;

class RetryFileTarget extends FileTarget
{
    /**
     * @var int Maximum number of retries.
     */
    public int $maxRetries = 20;

    /**
     * @var int Delay between retries in microseconds.
     */
    public int $retryDelay = 100000; // 100ms

    /**
     * Exports log messages.
     *
     * @throws Exception
     */
    public function export(): void
    {
        // Initialize retry count
        $retries = 0;

        // Retry up to the maximum number of retries
        while ($retries < $this->maxRetries) {
            // Attempt to log
            try {
                // Success
                parent::export();
                return;
            } catch (Exception $e) {
                // Unsuccessful, delay and retry
                $retries++;
                usleep($this->retryDelay);
            }
        }

        // Make one final attempt
        try {
            parent::export();
        } catch (Exception $e) {
            // Fail silently
        }
    }
}

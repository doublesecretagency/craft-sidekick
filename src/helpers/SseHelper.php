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

namespace doublesecretagency\sidekick\helpers;

use craft\helpers\Json;

class SseHelper
{
    /**
     * Initialize the SSE connection.
     */
    public static function init(): void
    {
        // Let the script run indefinitely
        @set_time_limit(0);
        ignore_user_abort(true);

        // Disable output buffering and compression
        ini_set('output_buffering', 'off');
        ini_set('zlib.output_compression', 'off');
        ob_implicit_flush(true);

        // Clear any existing output buffers
        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        // Set the appropriate headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('Transfer-Encoding: chunked');
        header('X-Accel-Buffering: no'); // Disable Nginx buffering

        // Pad and flush to ensure the client receives the initial response
        self::pad();
        self::flush();
    }

    /**
     * Send an event to the client.
     *
     * @param string $event The name of the event.
     * @param mixed $data The data to send with the event.
     */
    public static function event(string $event, $data = []): void
    {
        // Encode the data as JSON
        $json = Json::encode($data);

        // Send the event to the client
        echo "event: {$event}\n";
        echo "data: {$json}\n\n";

        // Flush the buffer to ensure the client receives the event
        self::flush();
    }

    /**
     * Send a comment to the client.
     *
     * @param string $comment The comment to send.
     */
    public static function comment(string $comment): void
    {
        echo ": {$comment}\n\n";
        self::flush();
    }

    // ========================================================================= //

    /**
     * Pad the output to ensure the client receives data.
     */
    public static function pad(): void
    {
        // Pad the output
        echo str_repeat(' ', 4096) . "\n";
    }

    /**
     * Flush the buffer to the client.
     */
    public static function flush(): void
    {
        // Flush to push it to the client immediately
        @ob_flush();
        @flush();
    }
}

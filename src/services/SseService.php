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

namespace doublesecretagency\sidekick\services;

use doublesecretagency\sidekick\models\ChatMessage;
use yii\base\Component;

/**
 * Class SseService
 *
 * Handles server sent events (SSE).
 */
class SseService extends Component
{
    /**
     * Send a message to the client via SSE.
     */
    public function sendMessage(ChatMessage $message): void
    {
        // Encode the message as JSON
        $data = json_encode([
            'role' => $message->role,
            'message' => $message->message,
        ]);

        // Send the message to the client
        echo "event: message\n";
        echo "data: {$data}\n\n";

        // Flush the buffer
        $this->_flushBuffer();
    }

    // ========================================================================= //

    /**
     * Start the SSE connection.
     */
    public function startConnection(): void
    {
        // Disable output buffering and compression
        ini_set('output_buffering', 'off');
        ini_set('zlib.output_compression', 'off');
        ob_implicit_flush(true);

        // Disable any output buffering or compression if needed
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Send headers for SSE
        $this->_sendHeaders();
    }

    /**
     * Close the SSE connection.
     */
    public function closeConnection(): void
    {
        // Close the connection
        echo "event: close\n";
        echo "data: {}\n\n";

        // Flush the buffer
        $this->_flushBuffer();
    }

    // ========================================================================= //

    /**
     * Send headers for SSE.
     */
    private function _sendHeaders(): void
    {
        // Set the appropriate headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable Nginx buffering
    }

    /**
     * Flush the buffer to the client.
     */
    private function _flushBuffer(): void
    {
        // Pad the output
        echo str_repeat(' ', 1024) . "\n";

        // Flush to push it to the client immediately
        if (ob_get_length()) {
            ob_flush();
        }
        flush();
    }
}

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

use craft\helpers\Json;
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
        try {

            // If the connection has been aborted
            if (connection_aborted()) {

                // Log the error message
                (new ChatMessage([
                    'role' => ChatMessage::ERROR,
                    'message' => "SSE connection aborted, message could not be sent. [{$message->message}]",
                ]))
                    ->log()
                    ->toChatHistory();

                // Bail
                return;
            }

            // Encode the message as JSON
            $data = Json::encode([
                'role' => $message->role,
                'message' => $message->message,
            ]);

            // Send the message to the client
            echo "event: message\n";
            echo "data: {$data}\n\n";

            // Flush the buffer
            $this->_flushBuffer();

        } catch (\Throwable $e) {

            // Something went wrong, log the error
            (new ChatMessage([
                'role' => ChatMessage::ERROR,
                'message' => "Failed to stream SSE message: {$e->getMessage()}",
            ]))
                ->log()
                ->toChatHistory();

        }
    }

    // ========================================================================= //

    /**
     * Start the SSE connection.
     */
    public function startConnection(): void
    {
        // Let the script run indefinitely
        set_time_limit(0);

        // Disable output buffering and compression
        ini_set('output_buffering', 'off');
        ini_set('zlib.output_compression', 'off');
        ob_implicit_flush(true);

        // Clear any existing output buffers
        while (ob_get_level()) {
            ob_end_flush();
        }

        // Send headers for SSE
        $this->_sendHeaders();

        // Flush the buffer to ensure headers are sent
        $this->_flushBuffer();

        // Send the first heartbeat to establish a connection
        $this->sendHeartbeat();
    }

    /**
     * Send a heartbeat to keep the SSE connection alive.
     */
    public function sendHeartbeat(): void
    {
        // If the connection has already been aborted
        if (connection_aborted()) {

            // Log error message
            (new ChatMessage([
                'role' => ChatMessage::ERROR,
                'message' => "SSE connection aborted before heartbeat."
            ]))
                ->log()
                ->toChatHistory();

            // Bail
            return;
        }

        try {

            // Send a heartbeat
            echo ":\n\n";
            $this->_flushBuffer();

        } catch (\Throwable $e) {

            // Log error message
            (new ChatMessage([
                'role' => ChatMessage::ERROR,
                'message' => "Failed to send SSE heartbeat: {$e->getMessage()}"
            ]))
                ->log()
                ->toChatHistory();

        }
    }

    /**
     * Close the SSE connection.
     */
    public function closeConnection(): void
    {
        // If the connection has already been aborted
        if (connection_aborted()) {

            // Log error message
            (new ChatMessage([
                'role' => ChatMessage::ERROR,
                'message' => "SSE connection aborted before closure."
            ]))
                ->log()
                ->toChatHistory();

            // Bail
            return;
        }

        // Pause to allow final messages to be sent
        usleep(200000); // 200ms

        try {

            // Close the connection
            echo "event: close\n";
            echo "data: {}\n\n";

            // Flush the buffer
            $this->_flushBuffer();

        } catch (\Throwable $e) {

            // Log error message
            (new ChatMessage([
                'role' => ChatMessage::ERROR,
                'message' => "Failed to send SSE close event: {$e->getMessage()}"
            ]))
                ->log()
                ->toChatHistory();

        }
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
        @ob_flush();
        @flush();
    }
}

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

use doublesecretagency\sidekick\helpers\SseHelper as Sse;
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
        // If the connection has been aborted
        if (connection_aborted()) {

            // Log the error message
            (new ChatMessage([
                'role' => ChatMessage::ERROR,
                'message' => "SSE connection aborted, message could not be sent. [{$message->message}]",
            ]))
                ->log(__METHOD__)
                ->toChatHistory();

            // Bail
            return;
        }

        try {

            // Send the message to the client
            Sse::event('message', [
                'role' => $message->role,
                'message' => $message->message,
            ]);

        } catch (\Throwable $e) {

            // Something went wrong, log the error
            (new ChatMessage([
                'role' => ChatMessage::ERROR,
                'message' => "Failed to stream SSE message: {$e->getMessage()}",
            ]))
                ->log(__METHOD__)
                ->toChatHistory();

        }
    }

    // ========================================================================= //

    /**
     * Start the SSE connection.
     */
    public function startConnection(): void
    {
        // Initialize the SSE stream
        Sse::init();

        // Send a connection confirmation
        Sse::event('connected');

        // Send heartbeats
        $this->sendHeartbeat();
        usleep(1000000); // 1s
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
                'message' => "No connection, unable to send heartbeat."
            ]))
                ->log(__METHOD__)
                ->toChatHistory();

            // Bail
            return;
        }

        // Send a heartbeat
        Sse::comment('heartbeat');
    }

    /**
     * Close the SSE connection.
     */
    public function closeConnection(): void
    {
        // If the connection is already closed, bail
        if (connection_aborted()) {
            return;
        }

        // Pause to allow final messages to be sent
        usleep(200000); // 200ms

        try {

            // Close the connection
            Sse::event('close');

        } catch (\Throwable $e) {

            // Log error message
            (new ChatMessage([
                'role' => ChatMessage::ERROR,
                'message' => "Failed to send SSE close event: {$e->getMessage()}"
            ]))
                ->log(__METHOD__)
                ->toChatHistory();

        }
    }
}

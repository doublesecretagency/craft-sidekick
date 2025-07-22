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

use Craft;
use craft\helpers\Json;
use doublesecretagency\sidekick\models\ChatMessage;
use doublesecretagency\sidekick\models\FunctionCall;
use yii\base\Component;

class ChatService extends Component
{
    /**
     * Get the cache key based on the current session.
     */
    public function getCacheKey(): string
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $sessionId = Craft::$app->getSession()->getId();
        return "chathistory-{$sessionId}";
    }

    /**
     * Clear the existing conversation from cache.
     */
    public function clearConversation(): void
    {
        // Delete the chat history
        /** @noinspection NullPointerExceptionInspection */
        $deleted = Craft::$app->getCache()->delete($this->getCacheKey());

        // If not deleted, log an error
        if (!$deleted) {
            Craft::error("Unable to clear the chat history.", __METHOD__);
        }
    }

    /**
     * Retrieve the existing conversation from cache.
     *
     * @return array
     */
    public function getConversation(): array
    {
        // Get the chat history
        /** @noinspection NullPointerExceptionInspection */
        $chatHistory = Craft::$app->getCache()->get($this->getCacheKey());

        // If no chat history, return an empty array
        if (!$chatHistory) {
            return [];
        }

        // Return the JSON decoded chat history
        return Json::decodeIfJson($chatHistory);

//        // Log an error message
//        Craft::error("Unable to get the chat history.", __METHOD__);
//
//        // Return an error message
//        return [
//            new ChatMessage([
//                'role' => ChatMessage::ERROR,
//                'message' => "Unable to load the conversation."
//            ])
//        ];
    }

    /**
     * Add a message or tool output to the conversation history.
     *
     * @param ChatMessage|FunctionCall $message
     */
    public function addMessage(ChatMessage|FunctionCall $message): void
    {
        // Get the existing conversation from cache
        $conversation = $this->getConversation();

        // Append the new message to the conversation
        $conversation[] = $message;

        // Optionally measure the size of the conversation
        $this->measure('CONVERSATION SIZE', $conversation);

        // Set lifespan for the cache entry
        $lifespan = (60 * 60 * 24 * 14); // 14 days

        // Update the chat history
        /** @noinspection NullPointerExceptionInspection */
        Craft::$app->getCache()->set(
            $this->getCacheKey(),
            Json::encode($conversation),
            $lifespan
        );

//        // Log an error message
//        Craft::error("Unable to update the conversation.", __METHOD__);
    }

    /**
     * Optionally display the size of a snippet in the chat window.
     *
     * @param string $label
     * @param array $snippet
     */
    public function measure(string $label, array $snippet): void
    {
        // If not reporting, bail
        if (!Craft::$app->getRequest()->getQueryParam('report')) {
            return;
        }

        // Get the size of the snippet in bytes
        $bytes = mb_strlen(Json::encode($snippet), '8bit');

        // Format the size for display
        if ($bytes > 1000000) {
            $size = round($bytes / 1000000, 2) . ' MB';
        } elseif ($bytes > 1000) {
            $size = round($bytes / 1000, 2) . ' KB';
        } else {
            $size = $bytes . ' bytes';
        }

        // Report the size in the chat window
        (new ChatMessage([
            'role' => ChatMessage::SYSTEM,
            'message' => "{$label}: {$size}"
        ]))->toChatWindow();
    }
}

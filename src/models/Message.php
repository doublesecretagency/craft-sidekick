<?php

namespace doublesecretagency\sidekick\models;

use craft\base\Model;
use doublesecretagency\sidekick\constants\Constants;

class Message extends Model
{
    public string $role;
    public string $content;
    public string $messageType;

    public function __construct(string $role, string $content, string $messageType = Constants::MESSAGE_TYPE_CONVERSATIONAL, $config = [])
    {
        $this->role = $role;
        $this->content = $content;
        $this->messageType = $messageType;
        parent::__construct($config);
    }

    /**
     * Convert code blocks in the message content to HTML.
     */
    public function convertCodeBlocks(): void
    {
        if ($this->messageType === Constants::MESSAGE_TYPE_SNIPPET) {
            // Code blocks are already converted
            return;
        }

        // Convert code snippets to HTML
        $this->content = preg_replace_callback('/```(.*?)\n([\s\S]*?)```/s', static function ($matches) {
            $language = htmlspecialchars($matches[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $code = trim($matches[2]);
            $code = htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return "<pre><code class=\"language-{$language}\">{$code}</code></pre>";
        }, $this->content);

        // Update messageType if code blocks are found
        if (strpos($this->content, '<pre>') !== false) {
            $this->messageType = Constants::MESSAGE_TYPE_SNIPPET;
        }
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['role', 'content', 'messageType'], 'required'],
            [['role', 'messageType'], 'string'],
            ['messageType', 'in', 'range' => [
                Constants::MESSAGE_TYPE_CONVERSATIONAL,
                Constants::MESSAGE_TYPE_SNIPPET,
                Constants::MESSAGE_TYPE_ACTION,
            ]],
        ];
    }
}

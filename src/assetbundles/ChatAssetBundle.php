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

namespace doublesecretagency\sidekick\assetbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Class ChatAssetBundle
 *
 * Manages the assets for the chat interface.
 */
class ChatAssetBundle extends AssetBundle
{
    /**
     * @var string Base URL for the Highlight.js library.
     */
    private string $_highlightJs = 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js';

    /**
     * @var string Version number of the Highlight.js library.
     */
    private string $_highlightVersion = '11.10.0';

    /**
     * @var string Default style for syntax highlighting.
     *
     * @see https://highlightjs.org/static/demo/
     * @see https://cdnjs.com/libraries/highlight.js
     * @see https://github.com/highlightjs/highlight.js/tree/main/src/styles
     */
    private string $_defaultStyle = 'default';

    /**
     * @var array List of supported languages for syntax highlighting.
     */
    private array $_supportedLanguages = [
        'javascript',
        'php',
        'twig',
    ];

    /**
     * Initializes the bundle.
     */
    public function init(): void
    {
        // Syntax highlighting style
        $style = $this->_defaultStyle;

        // Define the source path
        $this->sourcePath = '@doublesecretagency/sidekick/resources';

        // Define dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // Initialize array of language URLs
        $languages = [];

        // Loop through supported languages
        foreach ($this->_supportedLanguages as $language) {
            $languages[] = "{$this->_highlightJs}/{$this->_highlightVersion}/languages/{$language}.min.js";
        }

        // Define JavaScript assets
        $this->js = [
            // Markdown rendering
            'https://cdn.jsdelivr.net/npm/marked/marked.min.js',
            'https://cdn.jsdelivr.net/npm/dompurify@2.3.6/dist/purify.min.js',
            // Syntax highlighting
            "{$this->_highlightJs}/{$this->_highlightVersion}/highlight.min.js",
            ...$languages,
            // Main chat script
            'js/chat.js',
        ];

        // Define CSS assets
        $this->css = [
            "{$this->_highlightJs}/{$this->_highlightVersion}/styles/{$style}.min.css",
            'css/chat.css',
        ];

        // Initialize parent
        parent::init();
    }
}

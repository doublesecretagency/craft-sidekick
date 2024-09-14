<?php

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
     * Initializes the bundle.
     */
    public function init(): void
    {
        $this->sourcePath = '@doublesecretagency/sidekick/resources';

        $this->depends = [
            CpAsset::class,
        ];

        $this->css = [
            'css/chat.css',
        ];

        $this->js = [
            'js/chat.js',
        ];

        parent::init();
    }
}

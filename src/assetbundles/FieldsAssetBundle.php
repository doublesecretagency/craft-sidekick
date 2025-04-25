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

class FieldsAssetBundle extends AssetBundle
{
    /**
     * Initializes the bundle.
     */
    public function init(): void
    {
        $this->sourcePath = '@doublesecretagency/sidekick/resources';

        $this->depends = [
            CpAsset::class,
            CpAssetBundle::class,
        ];

        $this->css = [
            'css/fields.css',
        ];

        $this->js = [
            'js/fields.js',
        ];

        parent::init();
    }
}

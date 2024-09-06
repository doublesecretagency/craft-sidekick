<?php

namespace doublesecretagency\sidekick;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\services\UserPermissions;
use craft\web\View;
use doublesecretagency\sidekick\assetbundles\SidekickAssetBundle;
use doublesecretagency\sidekick\models\Settings;
use doublesecretagency\sidekick\services\OpenAIService;
use doublesecretagency\sidekick\services\AltTagService;
use doublesecretagency\sidekick\services\FileManagementService;
use doublesecretagency\sidekick\services\DummyDataService;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Event;
use yii\base\Exception;

/**
 * Class Sidekick
 *
 * Main plugin class for Sidekick.
 * Registers services, custom fields, and user permissions.
 */
class Sidekick extends Plugin
{

    // Hold an instance of the plugin
    public static $plugin;

    // Indicates the plugin has a settings page in the control panel
    public bool $hasCpSettings = true;

    /**
     * Initializes the plugin.
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        // Register services
        $this->setComponents([
            'openAIService' => OpenAIService::class,
            'altTagService' => AltTagService::class,
            'fileManagementService' => FileManagementService::class,
            'dummyDataService' => DummyDataService::class,
        ]);

        // Register console commands
        if (Craft::$app->request->isConsoleRequest) {
            $this->controllerNamespace = 'doublesecretagency\\sidekick\\console';
        }

        // Register user permissions for plugin features
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            static function (RegisterComponentTypesEvent $event) {
                $event->permissions[] = [
                    'label' => 'Sidekick Plugin Permissions',
                    'permissions' => [
                        'sidekick-create-update-templates' => ['label' => 'Create & update Twig templates'],
                        'sidekick-create-update-files' => ['label' => 'Create & update plugin/module files'],
                        'sidekick-generate-alt-tags' => ['label' => 'Manually generate alt tags'],
                        'sidekick-seed-dummy-data' => ['label' => 'Seed sections with dummy data'],
                    ],
                ];
            }
        );

        // Register the asset bundle for loading JS/CSS
        Event::on(
            View::class,
            View::EVENT_BEFORE_RENDER_TEMPLATE,
            static function () {
                // Check if we're on the asset edit page and load the asset bundle
                if (Craft::$app->request->getSegment(1) === 'assets') {
                    Craft::$app->view->registerAssetBundle(SidekickAssetBundle::class);
                }
            }
        );

    }

    /**
     * Creates the settings model for the plugin.
     *
     * @return Settings|null
     */
    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    /**
     * Renders the settings page HTML for the control panel.
     *
     * @return string|null The rendered HTML.
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate(
            'sidekick/_settings',
            ['settings' => $this->getSettings()]
        );
    }

}

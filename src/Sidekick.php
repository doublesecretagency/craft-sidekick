<?php

namespace doublesecretagency\sidekick;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\web\View;
use doublesecretagency\sidekick\assetbundles\SidekickAssetBundle;
use doublesecretagency\sidekick\models\Settings;
use doublesecretagency\sidekick\services\OpenAIService;
use doublesecretagency\sidekick\services\AltTagService;
use doublesecretagency\sidekick\services\FileManagementService;
use doublesecretagency\sidekick\services\DummyDataService;
use yii\base\Event;
use yii\log\FileTarget;
use craft\console\Application as ConsoleApplication;

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

    /**
     * @var bool $hasCpSection The plugin has a section with subpages.
     */
    public bool $hasCpSection = true;

    // Indicates the plugin has a settings page in the control panel
    public bool $hasCpSettings = true;

    /**
     * @var string The AI model to use.
     */
    public static string $aiModel = 'gpt-4o';
    // public static string $aiModel = 'o1-preview';

    /**
     * Initializes the plugin.
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        // Set path alias
        Craft::setAlias('@sidekick', Craft::getAlias('@vendor/doublesecretagency/craft-sidekick/src'));

        // Register services
        $this->setComponents([
            'openAIService' => OpenAIService::class,
            'altTagService' => AltTagService::class,
            'fileManagementService' => FileManagementService::class,
            'dummyDataService' => DummyDataService::class,
        ]);

        // Register console commands
        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'doublesecretagency\sidekick\console';
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
                        'sidekick-clear-conversation' => ['label' => 'Clear Chat Conversations'],
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

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            static function (RegisterUrlRulesEvent $event) {
                $event->rules['sidekick/chat'] = 'sidekick/chat/index';
                $event->rules['sidekick/chat/send-message'] = 'sidekick/chat/send-message';
                $event->rules['sidekick/chat/get-conversation'] = 'sidekick/chat/get-conversation';
                $event->rules['sidekick/chat/clear-conversation'] = 'sidekick/chat/clear-conversation';
            }
        );

        // Custom Logging Configuration
        $this->initializeCustomLogger();
    }

    /**
     * Initializes a custom logger to write Sidekick logs to sidekick.log
     */
    private function initializeCustomLogger(): void
    {
        // Define the log file path
        $logFilePath = Craft::getAlias('@storage/logs/sidekick.log');

        // Create a new FileTarget instance
        $sidekickLogTarget = new FileTarget([
            'levels' => ['error', 'warning', 'info'],
            'categories' => ['doublesecretagency\sidekick\*'],
            'logFile' => $logFilePath,
            'logVars' => [], // Disable logging of global variables like $_SERVER
            'maxFileSize' => 10240, // 10MB
            'maxLogFiles' => 5,
        ]);

        // Add the custom log target to Craft's logger
        Craft::getLogger()->dispatcher->targets[] = $sidekickLogTarget;
    }

    /**
     * @return array
     */
    public function getCpNavItem(): array
    {
        $navItem = parent::getCpNavItem();
        $navItem['label'] = 'Sidekick';
        $navItem['url'] = 'sidekick/chat';
        return $navItem;
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
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \yii\base\Exception
     */
    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate(
            'sidekick/_settings',
            ['settings' => $this->getSettings()]
        );
    }
}

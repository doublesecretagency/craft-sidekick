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

namespace doublesecretagency\sidekick;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\console\Application as ConsoleApplication;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\web\View;
use doublesecretagency\sidekick\assetbundles\SidekickAssetBundle;
use doublesecretagency\sidekick\events\AddSkillsEvent;
use doublesecretagency\sidekick\log\RetryFileTarget;
use doublesecretagency\sidekick\models\Settings;
use doublesecretagency\sidekick\services\ActionsService;
use doublesecretagency\sidekick\services\AltTagService;
use doublesecretagency\sidekick\services\ChatService;
use doublesecretagency\sidekick\services\DummyDataService;
use doublesecretagency\sidekick\services\FileManagementService;
use doublesecretagency\sidekick\services\OpenAIService;
use doublesecretagency\sidekick\services\SseService;
use doublesecretagency\sidekick\skills\edit\EditTemplates;
use doublesecretagency\sidekick\skills\edit\SettingsFields;
use doublesecretagency\sidekick\skills\edit\SettingsFieldsCraft4;
use doublesecretagency\sidekick\skills\edit\SettingsSections;
use doublesecretagency\sidekick\skills\edit\SettingsSites;
use doublesecretagency\sidekick\skills\read\Entries;
use doublesecretagency\sidekick\skills\read\Fields;
use doublesecretagency\sidekick\skills\read\FieldsCraft4;
use doublesecretagency\sidekick\skills\read\Sections;
use doublesecretagency\sidekick\skills\read\Sites;
use doublesecretagency\sidekick\skills\read\Templates;
use doublesecretagency\sidekick\twigextensions\SidekickTwigExtension;
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
 *
 * @property ActionsService $actions
 * @property AltTagService $altTag
 * @property ChatService $chat
 * @property DummyDataService $dummyData
 * @property FileManagementService $fileManagement
 * @property OpenAIService $openAi
 * @property SseService $sse
 */
class Sidekick extends Plugin
{
    /**
     * @event AddSkillsEvent The event that is triggered when defining extra tools for the AI assistant.
     */
    public const EVENT_ADD_SKILLS = 'addSkills';

    /**
     * @var Sidekick|null The plugin instance.
     */
    public static ?Sidekick $plugin = null;

    /**
     * @var bool $hasCpSection The plugin has a section with subpages.
     */
    public bool $hasCpSection = true;

    /**
     * @var bool $hasCpSettings The plugin has a settings page in the control panel.
     */
    public bool $hasCpSettings = true;

    /**
     * @var array $skills The complete list of skills available to the plugin.
     */
    private array $_skills = [];

    /**
     * Initializes the plugin.
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        // Register services
        $this->setComponents([
            'actions'        => ActionsService::class,
            'altTag'         => AltTagService::class,
            'chat'           => ChatService::class,
            'dummyData'      => DummyDataService::class,
            'fileManagement' => FileManagementService::class,
            'openAi'         => OpenAIService::class,
            'sse'            => SseService::class,
        ]);

        // Register console commands
        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'doublesecretagency\sidekick\console';
        }

        // Register the Twig extension
        Craft::$app->view->registerTwigExtension(new SidekickTwigExtension());

        // Redirect after plugin is installed
        $this->_postInstallRedirect();

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
//                        'sidekick-generate-alt-tags' => ['label' => 'Manually generate alt tags'],
//                        'sidekick-seed-dummy-data' => ['label' => 'Seed sections with dummy data'],
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

        // Register all routing for the control panel
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
        $this->_initializeCustomLogger();
    }

    // ========================================================================= //

    /**
     * After the plugin has been installed,
     * redirect to the chat window page.
     */
    private function _postInstallRedirect(): void
    {
        // After the plugin has been installed
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            static function (PluginEvent $event) {
                // If installed plugin isn't Sidekick, bail
                if ('sidekick' !== $event->plugin->handle) {
                    return;
                }
                // If installed via console, no need for a redirect
                if (Craft::$app->getRequest()->getIsConsoleRequest()) {
                    return;
                }
                // Redirect to the chat window page (with a welcome message)
                $url = UrlHelper::cpUrl('sidekick/chat', ['welcome' => 1]);
                Craft::$app->getResponse()->redirect($url)->send();
            }
        );
    }

    /**
     * Initializes a custom logger to write Sidekick logs to sidekick.log
     */
    private function _initializeCustomLogger(): void
    {
        // Define the log file path
        $logFilePath = Craft::getAlias('@storage/logs/sidekick.log');

        // Create a new FileTarget instance
        $sidekickLogTarget = new RetryFileTarget([
            'levels'      => ['error', 'warning', 'info'],
            'categories'  => ['doublesecretagency\sidekick\*'],
            'logFile'     => $logFilePath,
            'logVars'     => [],
            'maxFileSize' => 10240, // 10MB
            'maxLogFiles' => 5,
        ]);

        // Add the custom log target to Craft's logger
        Craft::getLogger()->dispatcher->targets[] = $sidekickLogTarget;
    }

    // ========================================================================= //

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
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    protected function settingsHtml(): ?string
    {
        // Get data from config file
        $configFile = Craft::$app->getConfig()->getConfigFromFile('sidekick');

        // Load plugin settings template
        return Craft::$app->getView()->renderTemplate('sidekick/settings', [
            'configFile' => $configFile,
            'settings' => $this->getSettings(),
        ]);
    }

    // ========================================================================= //

    /**
     * Get the list of available skills.
     *
     * @return array
     */
    public function getSkills(): array
    {
        // If the skills have already been defined, return them
        if ($this->_skills) {
            return $this->_skills;
        }

        // Get the general config
        $allowAdminChanges = Craft::$app->getConfig()->getGeneral()->allowAdminChanges;

        // Define the default skills
        $this->_skills[] = Sites::class;
        $this->_skills[] = Entries::class;
        $this->_skills[] = Fields::class;
        $this->_skills[] = Sections::class;
        $this->_skills[] = Templates::class;

        // If admin changes are allowed
        if ($allowAdminChanges) {
            // Append development skills
            $this->_skills[] = SettingsSites::class;
            $this->_skills[] = SettingsFields::class;
            $this->_skills[] = SettingsSections::class;
            $this->_skills[] = EditTemplates::class;
        }

        // If running Craft 4
        if ($this->_craftBetween('4.0.0', '5.0.0')) {
            // Append deprecated skills
            $this->_skills[] = FieldsCraft4::class;
            // If admin changes are allowed
            if ($allowAdminChanges) {
                // Append development skills
                $this->_skills[] = SettingsFieldsCraft4::class;
            }
        }

        // Give plugins/modules a chance to add custom skills
        if ($this->hasEventHandlers(self::EVENT_ADD_SKILLS)) {
            // Create a new AddSkillsEvent
            $event = new AddSkillsEvent();
            // Trigger the event
            $this->trigger(self::EVENT_ADD_SKILLS, $event);
            // Append any additional skills
            $this->_skills = array_merge($this->_skills, $event->skills);
        }

        // Return the complete list of skills
        return $this->_skills;
    }

    /**
     * Whether Craft is between the two specified versions.
     *
     * @param string $low The lower version bound.
     * @param string $high The upper version bound.
     * @return bool
     */
    private function _craftBetween(string $low, string $high): bool
    {
        // Get the Craft version
        $v = Craft::$app->getVersion();

        // Whether Craft is between the specified versions
        return (
            version_compare($v, $low, '>=') &&
            version_compare($v, $high, '<')
        );
    }
}

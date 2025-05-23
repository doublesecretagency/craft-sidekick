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
use craft\base\Element;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\ElementEvent;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\services\Elements;
use craft\services\Fields as FieldsService;
use craft\services\Plugins;
use craft\services\Utilities;
use craft\web\UrlManager;
use doublesecretagency\sidekick\events\AddPromptsEvent;
use doublesecretagency\sidekick\events\AddSkillsEvent;
use doublesecretagency\sidekick\fields\AiSummary;
use doublesecretagency\sidekick\helpers\AiSummaryHelper;
use doublesecretagency\sidekick\helpers\VersionHelper;
use doublesecretagency\sidekick\log\RetryFileTarget;
use doublesecretagency\sidekick\models\Settings;
use doublesecretagency\sidekick\services\ChatService;
use doublesecretagency\sidekick\services\OpenAIService;
use doublesecretagency\sidekick\services\SseService;
use doublesecretagency\sidekick\skills\Categories;
use doublesecretagency\sidekick\skills\Entries;
use doublesecretagency\sidekick\skills\Fields;
use doublesecretagency\sidekick\skills\Sections;
use doublesecretagency\sidekick\skills\Sites;
use doublesecretagency\sidekick\skills\Tags;
use doublesecretagency\sidekick\skills\Templates;
use doublesecretagency\sidekick\twigextensions\SidekickTwigExtension;
use doublesecretagency\sidekick\utilities\ChatWindowUtility;
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
 * @property ChatService $chat
 * @property OpenAIService $openAi
 * @property SseService $sse
 */
class Sidekick extends Plugin
{
    /**
     * @event AddPromptsEvent The event that is triggered when defining extra instructions for the AI assistant.
     */
    public const EVENT_ADD_PROMPTS = 'addPrompts';

    /**
     * @event AddSkillsEvent The event that is triggered when defining extra tools for the AI assistant.
     */
    public const EVENT_ADD_SKILLS = 'addSkills';

    /**
     * @var Sidekick|null The plugin instance.
     */
    public static ?Sidekick $plugin = null;

    /**
     * @var bool Whether the plugin has a settings page in the control panel.
     */
    public bool $hasCpSettings = true;

    /**
     * @var array The complete list of prompts to be loaded.
     */
    private array $_prompts = [];

    /**
     * @var array The complete list of skill sets available to the plugin.
     */
    private array $_skills = [];

    /**
     * @var array IDs of elements which have already been parsed.
     */
    private array $_parsedElements;

    /**
     * Initializes the plugin.
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        // Register services
        $this->setComponents([
            'chat'   => ChatService::class,
            'openAi' => OpenAIService::class,
            'sse'    => SseService::class,
        ]);

        // Register the Twig extension
        Craft::$app->view->registerTwigExtension(new SidekickTwigExtension());

        // Redirect after plugin is installed
        $this->_postInstallRedirect();

        // Get the plugin's settings
        $settings = $this->getSettings();

        // Get the link location
        $location = ($settings->sidekickLinkLocation ?? 'mainNav');

        // If the showing the link in Utilities
        if ($location === 'utilities') {
            // Enable the utilities link
            $this->_utilitiesLink();
        } else {
            // Enable the default main nav link
            $this->hasCpSection = true;
        }

        // Register the custom field type
        Event::on(
            FieldsService::class,
            FieldsService::EVENT_REGISTER_FIELD_TYPES,
            static function(RegisterComponentTypesEvent $e) {
                $e->types[] = AiSummary::class;
            }
        );

        // Handle the AI Summary field
        $this->_handleAiSummaryField();

        // Register all routing for the control panel
        $this->_cpRouting();

        // Register user permissions for plugin features
//        $this->_userPermissions();

        // Custom Logging Configuration
        $this->_initializeCustomLogger();
    }

    // ========================================================================= //

    /**
     * Register all routing for the control panel.
     */
    private function _cpRouting(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            static function (RegisterUrlRulesEvent $event) {
                $event->rules['sidekick/chat'] = 'sidekick/chat/index';
                $event->rules['sidekick/chat/send-message'] = 'sidekick/chat/send-message';
                $event->rules['sidekick/chat/get-conversation'] = 'sidekick/chat/get-conversation';
                $event->rules['sidekick/chat/clear-conversation'] = 'sidekick/chat/clear-conversation';
                $event->rules['sidekick/chat/list-skills'] = 'sidekick/chat/list-skills';
            }
        );
    }

//    /**
//     * Register user permissions.
//     */
//    private function _userPermissions(): void
//    {
//        Event::on(
//            UserPermissions::class,
//            UserPermissions::EVENT_REGISTER_PERMISSIONS,
//            static function (RegisterComponentTypesEvent $event) {
//                $event->permissions[] = [
//                    'label' => 'Sidekick Plugin Permissions',
//                    'permissions' => [
//                        'sidekick-create-update-templates' => ['label' => 'Create & update Twig templates'],
//                        'sidekick-create-update-files' => ['label' => 'Create & update plugin/module files'],
////                        'sidekick-generate-alt-tags' => ['label' => 'Manually generate alt tags'],
//                        'sidekick-clear-conversation' => ['label' => 'Clear Chat Conversations'],
//                    ],
//                ];
//            }
//        );
//    }

    /**
     * Register the link in the Utilities section.
     */
    private function _utilitiesLink(): void
    {
        // Gets the right event for registering utilities
        $event = defined('craft\services\Utilities::EVENT_REGISTER_UTILITY_TYPES')
            ? Utilities::EVENT_REGISTER_UTILITY_TYPES  // Craft 4
            : Utilities::EVENT_REGISTER_UTILITIES;     // Craft 5+

        // Register the utilities
        Event::on(
            Utilities::class,
            $event,
            static function(RegisterComponentTypesEvent $event) {
                $event->types[] = ChatWindowUtility::class;
            }
        );
    }

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
        // Get settings
        $settings = $this->getSettings();

        // Get the default configuration
        $navItem = parent::getCpNavItem();

        // Set label and URL of the link
        $navItem['label'] = ($settings->overrideLinkName ?? 'Sidekick');
        $navItem['url'] = 'sidekick/chat';

        // Return the nav item
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
     * Get the list of prompts to load.
     *
     * @return array
     */
    public function getPrompts(): array
    {
        // If the prompts have already been defined, return them
        if ($this->_prompts) {
            return $this->_prompts;
        }

        // Define the default prompts
        $this->_prompts = [
            'basic-instructions.md',
            'general-guidelines.md',
            'tool-functions.md',
            'twig-templates.md',
            'chat-messages.md',
            'saving-fields.md',
            'saving-sections.md',
            'saving-category-groups.md',
            'field-layouts.md',
            'element-configs.md',
            'generating-uids.md',
            'namespace-hashes.md',
        ];

        // Append handling of Matrix fields
        if (VersionHelper::craftBetween('4.0.0', '5.0.0')) {
            // Craft 4
            $this->_prompts[] = 'matrix-fields-c4.md';
        } else {
            // Craft 5+
            $this->_prompts[] = 'matrix-fields-c5.md';
        }

        // Get the path to the Sidekick plugin
        $path = Craft::getAlias('@doublesecretagency/sidekick');

        // Convert all prompts so far to a local path
        foreach ($this->_prompts as &$file) {
            $file = "{$path}/prompts/{$file}";
        }

        // Unset to prevent issues
        unset($file);

        // Give plugins/modules a chance to add custom prompts
        if ($this->hasEventHandlers(self::EVENT_ADD_PROMPTS)) {
            // Create a new event
            $event = new AddPromptsEvent();
            // Trigger the event
            $this->trigger(self::EVENT_ADD_PROMPTS, $event);
            // Append any additional prompts
            $this->_prompts = array_merge($this->_prompts, $event->prompts);
        }

        // Return all prompts
        return $this->_prompts;
    }

    /**
     * Get the list of available skill sets.
     *
     * @return array
     */
    public function getSkills(): array
    {
        // If the skill sets have already been defined, return them
        if ($this->_skills) {
            return $this->_skills;
        }

        // Define the default skill sets
        $this->_skills = [
            Templates::class,
            Entries::class,
            Categories::class,
            Tags::class,
            Fields::class,
            Sections::class,
            Sites::class,
        ];

        // Give plugins/modules a chance to add custom skill sets
        if ($this->hasEventHandlers(self::EVENT_ADD_SKILLS)) {
            // Create a new event
            $event = new AddSkillsEvent();
            // Trigger the event
            $this->trigger(self::EVENT_ADD_SKILLS, $event);
            // Append any additional skill sets
            $this->_skills = array_merge($this->_skills, $event->skills);
        }

        // Return the complete list of skill sets
        return $this->_skills;
    }

    // ========================================================================= //

    /**
     * Handle the AI Summary field.
     */
    private function _handleAiSummaryField(): void
    {
        // Before save element event handler
        Event::on(
            Elements::class,
            Elements::EVENT_BEFORE_SAVE_ELEMENT,
            function (ElementEvent $event) {

                /** @var Element $element */
                $element = $event->element;

                // If the element is a draft, bail
                if ($element->getIsDraft()) {
                    return;
                }

                // If the element is a revision, bail
                if ($element->getIsRevision()) {
                    return;
                }

                // Compile a unique key for each element
                $key = "{$element->id}:{$element->siteId}";

                // If not parsing the element
                if (!isset($this->_parsedElements[$key])) {

                    // Mark the element as being parsed
                    $this->_parsedElements[$key] = true;

                    // Get the AI-generated summary
                    $content = AiSummaryHelper::parseElement($element);

                    // If the content is not empty, set the field values
                    if (!empty($content)) {
                        $element->setFieldValues($content);
                    }

                    // Remove the element from the preparsed elements array
                    unset($this->_parsedElements[$key]);
                }
            }
        );
    }
}

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

namespace doublesecretagency\sidekick\events;

use craft\base\Event;

/**
 * Class AddPromptsEvent
 */
class AddPromptsEvent extends Event
{
    /**
     * @var array Additional prompts to be defined.
     */
    public array $prompts = [];
}

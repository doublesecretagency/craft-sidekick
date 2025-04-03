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
 * Class AddSkillsEvent
 */
class AddSkillsEvent extends Event
{
    /**
     * @var array Additional skills to be defined.
     */
    public array $skills = [];
}

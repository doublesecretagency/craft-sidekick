<?php

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

<?php

namespace doublesecretagency\sidekick\events;

use craft\base\Event;

/**
 * Class DefineExtraToolsEvent
 */
class DefineExtraToolsEvent extends Event
{
    /**
     * @var array Additional tools to be defined.
     */
    public array $extraTools = [];
}

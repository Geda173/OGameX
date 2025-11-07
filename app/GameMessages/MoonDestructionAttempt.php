<?php

namespace OGame\GameMessages;

use OGame\GameMessages\Abstracts\GameMessage;

class MoonDestructionAttempt extends GameMessage
{
    protected function initialize(): void
    {
        $this->key = 'moon_destruction_attempt';
        $this->params = ['coordinates', 'fleet_destroyed'];
        $this->tab = 'fleets';
        $this->subtab = 'other';
    }
}

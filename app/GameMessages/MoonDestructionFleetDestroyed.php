<?php

namespace OGame\GameMessages;

use OGame\GameMessages\Abstracts\GameMessage;

class MoonDestructionFleetDestroyed extends GameMessage
{
    protected function initialize(): void
    {
        $this->key = 'moon_destruction_fleet_destroyed';
        $this->params = ['from', 'to', 'moon_chance', 'deathstar_chance'];
        $this->tab = 'fleets';
        $this->subtab = 'other';
    }
}

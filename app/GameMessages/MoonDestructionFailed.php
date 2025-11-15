<?php

namespace OGame\GameMessages;

use OGame\GameMessages\Abstracts\GameMessage;

class MoonDestructionFailed extends GameMessage
{
    protected function initialize(): void
    {
        $this->key = 'moon_destruction_failed';
        $this->params = ['from', 'to', 'moon_chance', 'deathstar_chance'];
        $this->tab = 'fleets';
        $this->subtab = 'other';
    }
}

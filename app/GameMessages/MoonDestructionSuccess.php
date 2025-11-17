<?php

namespace OGame\GameMessages;

use OGame\GameMessages\Abstracts\GameMessage;

class MoonDestructionSuccess extends GameMessage
{
    protected function initialize(): void
    {
        $this->key = 'moon_destruction_success';
        $this->params = ['from', 'to', 'moon_chance', 'deathstar_chance'];
        $this->tab = 'fleets';
        $this->subtab = 'other';
    }
}

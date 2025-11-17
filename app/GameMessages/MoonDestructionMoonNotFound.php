<?php

namespace OGame\GameMessages;

use OGame\GameMessages\Abstracts\GameMessage;

class MoonDestructionMoonNotFound extends GameMessage
{
    protected function initialize(): void
    {
        $this->key = 'moon_destruction_moon_not_found';
        $this->params = ['from', 'to'];
        $this->tab = 'fleets';
        $this->subtab = 'other';
    }
}

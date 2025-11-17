<?php

namespace OGame\GameMessages;

use OGame\GameMessages\Abstracts\GameMessage;

class MoonDestroyed extends GameMessage
{
    protected function initialize(): void
    {
        $this->key = 'moon_destroyed';
        $this->params = ['coordinates'];
        $this->tab = 'fleets';
        $this->subtab = 'other';
    }
}

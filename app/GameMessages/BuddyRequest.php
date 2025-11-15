<?php

namespace OGame\GameMessages;

use OGame\GameMessages\Abstracts\GameMessage;

class BuddyRequest extends GameMessage
{
    protected function initialize(): void
    {
        $this->key = 'buddy_request';
        $this->params = ['sender_id', 'sender_name', 'message'];
        $this->tab = 'communication';
        $this->subtab = 'messages';
    }
}

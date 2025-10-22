<?php

namespace OGame\GameMessages;

use OGame\GameMessages\Abstracts\GameMessage;
use OGame\Models\Message;
use OGame\Factories\PlanetServiceFactory;
use OGame\Factories\PlayerServiceFactory;

/**
 * Defender notification when an enemy espionage probe was detected.
 * Clean subject, single coordinate display (as link) in the body.
 */
class EspionageDetected extends GameMessage
{
    public function __construct(?Message $message = null, ?PlanetServiceFactory $planetServiceFactory = null, ?PlayerServiceFactory $playerServiceFactory = null)
    {
        $this->message = $message ? clone $message : new Message();
        $this->planetServiceFactory = $planetServiceFactory ?? app(PlanetServiceFactory::class);
        $this->playerServiceFactory = $playerServiceFactory ?? app(PlayerServiceFactory::class);
        $this->initialize();
    }

    protected function initialize(): void
    {
        $this->key    = 'espionage_detected';
        $this->params = [];
        $this->tab    = 'fleets';
        $this->subtab = 'espionage';
    }

    private function readParams(): array
    {
        $p = $this->message->params ?? [];
        if (is_array($p)) return $p;
        if (is_string($p) && $p !== '') {
            $d = json_decode($p, true);
            if (is_array($d)) return $d;
        }
        return [];
    }

    public function getFrom(): string
    {
        return 'Fleet Command';
    }

    // No coordinates in subject anymore (to avoid duplication).
    public function getSubject(): string
    {
        return $this->replacePlaceholders('Enemy espionage probe detected');
    }

    public function getBody(): string
    {
        $p = $this->readParams();

        $attacker   = $p['attacker_username'] ?? 'Unknown';
        $coordStr   = $p['coords'] ?? '';             // RAW like "1:4:5"
        $planetName = $p['planet_name'] ?? null;
        $chanceTxt  = isset($p['chance']) ? ((int)$p['chance']) . '%' : null;

        // Build a single, clean coordinate link if present
        $coordLine = $coordStr ? ('Coordinates: [coordinates]' . $coordStr . '[/coordinates]') : null;

        $lines = [];
        $lines[] = 'An enemy espionage probe was detected on your planet.';
        $lines[] = 'Attacker: ' . $attacker;
        if ($planetName) { $lines[] = 'Planet: ' . $planetName; }
        if ($coordLine)  { $lines[] = $coordLine; }
        if ($chanceTxt !== null) { $lines[] = 'Detection chance: ' . $chanceTxt; }

        return implode("\n", $lines);
    }

    public function getBodyFull(): string
    {
        return $this->getBody();
    }
}

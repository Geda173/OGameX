<?php

namespace OGame\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EspionageAttempted
{
    use Dispatchable, SerializesModels;

    public int $attackerId;
    public int $defenderId;
    public int $planetId;
    public int $probes;
    public int $galaxy;
    public int $system;
    public int $position;
    public ?string $rawReportJson;

    public function __construct(
        int $attackerId,
        int $defenderId,
        int $planetId,
        int $probes,
        int $galaxy,
        int $system,
        int $position,
        ?string $rawReportJson = null
    ) {
        $this->attackerId   = $attackerId;
        $this->defenderId   = $defenderId;
        $this->planetId     = $planetId;
        $this->probes       = $probes;
        $this->galaxy       = $galaxy;
        $this->system       = $system;
        $this->position     = $position;
        $this->rawReportJson = $rawReportJson;
    }
}

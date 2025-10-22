<?php

namespace OGame\GameMessages;

use OGame\GameMessages\Abstracts\GameMessage;
use OGame\Models\Enums\PlanetType;
use OGame\Models\Planet\Coordinate;
use OGame\Models\Resources;
use OGame\Services\ObjectService;
use OGame\ViewModels\UnitViewModel;
use OGame\Factories\PlanetServiceFactory;
use OGame\Factories\PlayerServiceFactory;
use OGame\Models\Message;

/**
 * Restores original EspionageReport behavior (row preview + overlay),
 * with safe guards for nullable JSON and DI/container quirks.
 */
class EspionageReport extends GameMessage
{
    /**
     * @var \OGame\Models\EspionageReport|null
     */
    private ?\OGame\Models\EspionageReport $espionageReportModel = null;

    /**
     * Tolerant ctor so the factory can resolve this class both:
     *  - without args (tab discovery), and
     *  - with just ['message' => $message] (row render).
     */
    public function __construct(
        ?Message $message = null,
        ?PlanetServiceFactory $planetServiceFactory = null,
        ?PlayerServiceFactory $playerServiceFactory = null
    ) {
        // When no message is given (tab discovery), use a dummy Message.
        $this->message = $message ? clone $message : new Message();

        // When factories aren’t injected, pull them from the container.
        $this->planetServiceFactory = $planetServiceFactory ?? app(PlanetServiceFactory::class);
        $this->playerServiceFactory = $playerServiceFactory ?? app(PlayerServiceFactory::class);

        $this->initialize();
    }

    protected function initialize(): void
    {
        $this->key = 'espionage_report';
        $this->params = [];
        $this->tab = 'fleets';
        $this->subtab = 'espionage';
    }

    /**
     * Load espionage report model from database. If already loaded, do nothing.
     */
    private function loadEspionageReportModel(): void
    {
        if ($this->espionageReportModel !== null) {
            return;
        }

        $id = $this->message->espionage_report_id ?? null;
        if ($id) {
            $espionageReport = \OGame\Models\EspionageReport::where('id', $id)->first();
            $this->espionageReportModel = $espionageReport ?: new \OGame\Models\EspionageReport();
        } else {
            // Fallback empty model (prevents crashes in discovery/test code paths).
            $this->espionageReportModel = new \OGame\Models\EspionageReport();
        }
    }

    /**
     * @inheritdoc
     */
    public function getSubject(): string
    {
        $this->loadEspionageReportModel();

        $coord = new Coordinate(
            (int)($this->espionageReportModel->planet_galaxy ?? 0),
            (int)($this->espionageReportModel->planet_system ?? 0),
            (int)($this->espionageReportModel->planet_position ?? 0)
        );

        $planetType = $this->espionageReportModel->planet_type ?? PlanetType::PLANET->value;
        $planet = $this->planetServiceFactory->makeForCoordinate($coord, true, PlanetType::from($planetType));

        if ($planet) {
            $subject = __('Espionage report from :planet', [
                'planet' => '[planet]' . $planet->getPlanetId() . '[/planet]'
            ]);
        } else {
            $subject = __('Espionage report from :planet', [
                'planet' => '[coordinates]' . $coord->asString() . '[/coordinates]'
            ]);
        }

        return $this->replacePlaceholders($subject);
    }

    /**
     * @inheritdoc
     * Renders the original compact row template.
     */
    public function getBody(): string
    {
        $this->loadEspionageReportModel();

        $coord = new Coordinate(
            (int)($this->espionageReportModel->planet_galaxy ?? 0),
            (int)($this->espionageReportModel->planet_system ?? 0),
            (int)($this->espionageReportModel->planet_position ?? 0)
        );
        $planetType = $this->espionageReportModel->planet_type ?? PlanetType::PLANET->value;
        $planet = $this->planetServiceFactory->makeForCoordinate($coord, true, PlanetType::from($planetType));

        if ($planet === null) {
            return __('Planet has been deleted and espionage report is no longer available.');
        }

        $params = $this->getEspionageReportParams();
        return view('ingame.messages.templates.espionage_report', $params)->render();
    }

    /**
     * @inheritdoc
     * Renders the original full overlay template.
     */
    public function getBodyFull(): string
    {
        $this->loadEspionageReportModel();

        $coord = new Coordinate(
            (int)($this->espionageReportModel->planet_galaxy ?? 0),
            (int)($this->espionageReportModel->planet_system ?? 0),
            (int)($this->espionageReportModel->planet_position ?? 0)
        );
        $planetType = $this->espionageReportModel->planet_type ?? PlanetType::PLANET->value;
        $planet = $this->planetServiceFactory->makeForCoordinate($coord, true, PlanetType::from($planetType));

        if ($planet === null) {
            return __('Planet has been deleted and espionage report is no longer available.');
        }

        $params = $this->getEspionageReportParams();
        return view('ingame.messages.templates.espionage_report_full', $params)->render();
    }

    /**
     * @inheritdoc
     */
    public function getFooterDetails(): string
    {
        // Original “More details” overlay trigger.
        return ' <a class="fright txt_link msg_action_link overlay"
                   href="' . route('messages.ajax.getmessage', ['messageId' => $this->message->id])  . '"
                   data-overlay-title="More details">
                    More details
                </a>';
    }

    /**
     * Get the espionage report params for the templates.
     *
     * @return array<string, mixed>
     */
    private function getEspionageReportParams(): array
    {
        $this->loadEspionageReportModel();

        $coord = new Coordinate(
            (int)($this->espionageReportModel->planet_galaxy ?? 0),
            (int)($this->espionageReportModel->planet_system ?? 0),
            (int)($this->espionageReportModel->planet_position ?? 0)
        );
        $planetType = $this->espionageReportModel->planet_type ?? PlanetType::PLANET->value;
        $planet = $this->planetServiceFactory->makeForCoordinate($coord, true, PlanetType::from($planetType));

        if ($planet === null) {
            // Defensive fallback (template will show the message text already).
            return [
                'subject'   => $this->getSubject(),
                'from'      => $this->getFrom(),
                'playername'=> '',
                'resources' => new Resources(0,0,0,0),
                'debris'    => new Resources(0,0,0,0),
                'ships'     => [],
                'defense'   => [],
                'buildings' => [],
                'research'  => [],
            ];
        }

        // Choose player per original logic.
        $planetOwnerId = $planet->getPlayer()->getId();
        $reportUserId  = $this->espionageReportModel->planet_user_id ?? $planetOwnerId;

        if ($reportUserId === $planetOwnerId) {
            $player = $this->playerServiceFactory->make($planetOwnerId);
        } else {
            $player = $this->playerServiceFactory->make($reportUserId);
        }

        // Safe extraction helpers for JSON arrays.
        $arr = static function ($v): array {
            return is_array($v) ? $v : (is_object($v) ? (array)$v : []);
        };

        // Resources (safe defaults).
        $resRaw = $arr($this->espionageReportModel->resources ?? []);
        $resources = new Resources(
            (int)($resRaw['metal']     ?? 0),
            (int)($resRaw['crystal']   ?? 0),
            (int)($resRaw['deuterium'] ?? 0),
            (int)($resRaw['energy']    ?? 0)
        );

        // Debris (safe defaults).
        $debRaw = $arr($this->espionageReportModel->debris ?? []);
        $debris = new Resources(
            (int)($debRaw['metal']     ?? 0),
            (int)($debRaw['crystal']   ?? 0),
            (int)($debRaw['deuterium'] ?? 0),
            0
        );

        // Ships.
        $ships = [];
        foreach ($arr($this->espionageReportModel->ships ?? []) as $machineName => $amount) {
            if (!is_numeric($amount)) { continue; }
            $unit = ObjectService::getUnitObjectByMachineName((string)$machineName);
            if (!$unit) { continue; }
            $vm = new UnitViewModel();
            $vm->amount = (int)$amount;
            $vm->object = $unit;
            $ships[$unit->machine_name] = $vm;
        }

        // Defense.
        $defense = [];
        foreach ($arr($this->espionageReportModel->defense ?? []) as $machineName => $amount) {
            if (!is_numeric($amount)) { continue; }
            $unit = ObjectService::getUnitObjectByMachineName((string)$machineName);
            if (!$unit) { continue; }
            $vm = new UnitViewModel();
            $vm->amount = (int)$amount;
            $vm->object = $unit;
            $defense[$unit->machine_name] = $vm;
        }

        // Buildings.
        $buildings = [];
        foreach ($arr($this->espionageReportModel->buildings ?? []) as $machineName => $amount) {
            if (!is_numeric($amount)) { continue; }
            $obj = ObjectService::getObjectByMachineName((string)$machineName);
            if (!$obj) { continue; }
            $vm = new UnitViewModel();
            $vm->amount = (int)$amount;
            $vm->object = $obj;
            $buildings[$obj->machine_name] = $vm;
        }

        // Research.
        $research = [];
        foreach ($arr($this->espionageReportModel->research ?? []) as $machineName => $amount) {
            if (!is_numeric($amount)) { continue; }
            $obj = ObjectService::getObjectByMachineName((string)$machineName);
            if (!$obj) { continue; }
            $vm = new UnitViewModel();
            $vm->amount = (int)$amount;
            $vm->object = $obj;
            $research[$obj->machine_name] = $vm;
        }

        return [
            'subject'    => $this->getSubject(),
            'from'       => $this->getFrom(),      // will use translation key from base, or customize if needed
            'playername' => $player->getUsername(),
            'resources'  => $resources,
            'debris'     => $debris,
            'ships'      => $ships,
            'defense'    => $defense,
            'buildings'  => $buildings,
            'research'   => $research,
        ];
    }
}

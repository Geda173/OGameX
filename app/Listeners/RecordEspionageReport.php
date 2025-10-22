<?php

namespace OGame\Listeners;

use Illuminate\Support\Facades\DB;
use OGame\Events\EspionageAttempted;

class RecordEspionageReport
{
    public function handle(EspionageAttempted $e): void
    {
        $subject = __('Espionage report');
        $body = sprintf(
            "%s\n\n%s: [%d:%d:%d]\n%s: %d",
            __('You were spied on.'),
            __('Coordinates'), $e->galaxy, $e->system, $e->position,
            __('Espionage Probes'), $e->probes
        );

        // Safe params blob the UI expects
        $params = json_encode([
            'player_info' => (object)[],
            'resources'   => ['metal'=>0,'crystal'=>0,'deuterium'=>0,'energy'=>0],
            'debris'      => (object)[],
            'buildings'   => (object)[],
            'research'    => (object)[],
            'ships'       => (object)[],
            'defense'     => (object)[],
        ], JSON_UNESCAPED_SLASHES);

        DB::table('messages')->insert([
            'user_id'    => $e->defenderId,
            'key'        => 'espionage_report',
            'subject'    => $subject,
            'body'       => $body,
            'params'     => $params,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

<?php

namespace OGame\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecordLoginActivity
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        // With TrustProxies, this is the real client IP from X-Forwarded-For
        $ip = request()->ip();
        $ua = substr(request()->userAgent() ?? '', 255);

        // Persist quick reference on the user
        $user->last_ip = $ip;
        $user->save();

        // Optional: insert a row if the table exists
        $hasTable = DB::table('information_schema.tables')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', 'login_activities')
            ->exists();

        if ($hasTable) {
            DB::table('login_activities')->insert([
                'user_id' => $user->id,
                'ip'      => $ip,
                'ua'      => $ua,
            ]);
        }

        // Also write to laravel.log for quick greps
        Log::info('login', ['user' => $user->username ?? $user->email, 'ip' => $ip]);
    }
}

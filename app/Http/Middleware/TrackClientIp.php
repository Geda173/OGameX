<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TrackClientIp
{
    /**
     * Log the real client IP for authenticated users on each request,
     * but avoid spamming: only write if IP changed or last record older than 15 minutes.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (Auth::check()) {
            $user = Auth::user();
            $ip   = $request->ip();
            $ua   = substr((string) $request->userAgent(), 0, 255); // keep it short/safe

            // Update users.last_ip if different.
            if ($user->last_ip !== $ip) {
                DB::table('users')->where('id', $user->id)->update(['last_ip' => $ip]);
            }

            // Insert into login_activities if table exists and we should write.
            try {
                $hasTable = DB::table('information_schema.tables')
                    ->where('table_schema', DB::raw('DATABASE()'))
                    ->where('table_name', 'login_activities')
                    ->exists();

                if ($hasTable) {
                    // Find recent record to throttle inserts (15 minutes window per user+ip).
                    $recent = DB::table('login_activities')
                        ->where('user_id', $user->id)
                        ->where('ip', $ip)
                        ->orderByDesc('created_at')
                        ->limit(1)
                        ->value('created_at');

                    $tooRecent = $recent && Carbon::parse($recent)->gt(Carbon::now()->subMinutes(15));

                    if (!$tooRecent) {
                        DB::table('login_activities')->insert([
                            'user_id'    => $user->id,
                            'ip'         => $ip,
                            'ua'         => $ua,
                            'created_at' => Carbon::now(),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                // Swallow errors to never break gameplay because of logging.
            }
        }

        return $response;
    }
}

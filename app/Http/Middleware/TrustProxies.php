<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Symfony\Component\HttpFoundation\Request;

class TrustProxies extends Middleware
{
    /**
     * Trusted proxies for this application.
     *
     * You can hardcode your LAN ranges or just trust all because your
     * Pi reverse proxy + internal nginx are under your control.
     */
    protected $proxies = '*';

    /**
     * Headers that should be used to detect proxies.
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_FORWARDED;
}

<?php

namespace OGame\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use OGame\Events\EspionageAttempted;
use OGame\Listeners\RecordEspionageReport;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \Illuminate\Auth\Events\Login::class => [
            \OGame\Listeners\RecordLoginActivity::class,
        ],

        EspionageAttempted::class => [
            RecordEspionageReport::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('devices:mark-offline')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('notifications:weekly-report')
    ->weeklyOn(1, '08:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();

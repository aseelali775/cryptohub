<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('crypto:fetch-prices')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('crypto:fetch-news')
    ->cron('10 */2 * * *')
    ->withoutOverlapping();

Schedule::command('news:process-ai')
    ->cron('15 */2 * * *')
    ->withoutOverlapping();

Schedule::command('crypto:generate-ai-reports')
    ->twiceDaily(2, 14)
    ->withoutOverlapping();

Schedule::command('model:prune')
    ->dailyAt('04:00');
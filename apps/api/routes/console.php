<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('pishkhan:expire-action-required-cases')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('pishkhan:expire-dispatch-offers')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('payments:reconcile --older-than=10')
    ->everyFiveMinutes()
    ->withoutOverlapping();

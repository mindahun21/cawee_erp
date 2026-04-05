<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('hr:car-rent-alerts')->dailyAt('08:00');
Schedule::command('recruitment:expire-offers')->daily();
Schedule::command('recruitment:daily-maintenance')->daily();

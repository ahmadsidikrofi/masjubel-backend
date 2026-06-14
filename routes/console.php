<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('scrape:hartadinata-gold')->dailyAt('10:03');
Schedule::command('scrape:sampoerna-gold')->dailyAt('10:06');
Schedule::command('scrape:logam-mulia-gold')->dailyAt('10:09');
Schedule::command('scrape:ubs-gold')->dailyAt('10:11');

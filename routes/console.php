<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// Mengagregasi data hari sebelumnya setiap jam 00:30
Schedule::command('ai:aggregate-metrics')->dailyAt('00:30')->withoutOverlapping();

// Prune AI Memories (dari sprint sebelumnya)
Schedule::command('ai:prune-memories')->dailyAt('01:00')->withoutOverlapping();
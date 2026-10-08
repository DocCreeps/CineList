<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Compte démo : efface les bacs à sable expirés (nécessite le scheduler : `schedule:run` chaque minute).
Schedule::command('demo:purge')->hourly();

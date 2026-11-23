<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cierra cada hora las tutorías vencidas que nadie confirmó ni reportó.
Schedule::command('tutorias:cerrar-vencidas')->hourly()->withoutOverlapping(30);

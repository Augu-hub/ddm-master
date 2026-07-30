<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Relance quotidienne des plans d'action en retard (tous les tenants).
Schedule::command('risk:action-plans-overdue')
    ->dailyAt('07:00')
    ->withoutOverlapping();

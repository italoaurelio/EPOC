<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('escalada:dispatch-notifications lembrete_1_dia')->dailyAt('08:00');
Schedule::command('escalada:dispatch-notifications lembrete_1_hora')->hourly();
Schedule::command('escalada:dispatch-notifications cobranca_presenca')->everyThirtyMinutes();
Schedule::command('escalada:dispatch-notifications resumo_24h')->hourlyAt(10);
Schedule::command('escalada:dispatch-notifications aviso_vagas')->everyFifteenMinutes();

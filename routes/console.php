<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programar la regeneración de tokens digitales cada minuto
// NOTA: Para ejecutar cada 30 segundos, usar el daemon: php regenerate-tokens-daemon.php
// El scheduler de Laravel tiene un mínimo de 1 minuto, por lo que para 30 segundos
// es necesario usar el daemon o un proceso en segundo plano
Schedule::command('tokens:regenerate')->everyMinute();

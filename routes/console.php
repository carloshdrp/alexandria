<?php

use Emprestimos\Domain\Services\ExpiracaoReservaService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reservas:expirar', function (ExpiracaoReservaService $service) {
    $this->info("Reservas expiradas: {$service->expirarVencidas()}");
})->purpose('Expira reservas disponíveis cuja janela de retirada venceu');

Schedule::command('reservas:expirar')->hourly();

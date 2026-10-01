<?php

use Emprestimos\Domain\Services\AvisoVencimentoService;
use Emprestimos\Domain\Services\ExpiracaoReservaService;
use Emprestimos\Domain\Services\MarcacaoAtrasoService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reservas:expirar', function (ExpiracaoReservaService $service) {
    $this->info("Reservas expiradas: {$service->expirarVencidas()}");
})->purpose('Expira reservas disponíveis cuja janela de retirada venceu');

Artisan::command('emprestimos:marcar-atrasados', function (MarcacaoAtrasoService $service) {
    $this->info("Empréstimos marcados como atrasados: {$service->marcarVencidos()}");
})->purpose('Marca como atrasados os empréstimos em andamento com prazo vencido');

Artisan::command('emprestimos:avisar-vencimento', function (AvisoVencimentoService $service) {
    $this->info("Empréstimos avisados do vencimento: {$service->avisarProximos()}");
})->purpose('Avisa os leitores cujo prazo de devolução termina em até um dia');

Schedule::command('reservas:expirar')->hourly();
Schedule::command('emprestimos:marcar-atrasados')->daily();
Schedule::command('emprestimos:avisar-vencimento')->daily();

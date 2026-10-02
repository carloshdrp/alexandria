<?php

use Carbon\CarbonImmutable;
use Emprestimos\Application\Listeners\DisponibilizarProximaReservaAposDevolucao;
use Emprestimos\Domain\Enums\ReservaSituacao;
use Emprestimos\Domain\Services\DevolucaoEmprestimoService;
use Emprestimos\Domain\Services\ReconciliacaoFilaReservaService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Inventario\Domain\Enums\ExemplarSituacao;

it('retoma a fila quando o job de promocao nunca roda', function () {
    Queue::fake();
    Notification::fake();

    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $emprestimo = emprestimoEmAndamento(usuario('ana'), $exemplar);
    $bob = usuario('bob');
    $reserva = reservaAguardando($bob, $exemplar);

    app(DevolucaoEmprestimoService::class)->devolver($emprestimo);

    Queue::assertPushed(
        CallQueuedListener::class,
        fn ($job) => $job->class === DisponibilizarProximaReservaAposDevolucao::class,
    );

    expect($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::Reservado)
        ->and($reserva->refresh()->situacao)->toBe(ReservaSituacao::Aguardando);

    expect(app(ReconciliacaoFilaReservaService::class)->reconciliar())->toBe(1);

    expect($reserva->refresh()->situacao)->toBe(ReservaSituacao::Disponivel)
        ->and($reserva->exemplar_id)->toBe($exemplar->id);
});

it('nao mexe numa fila que ja tem reserva disponivel', function () {
    Notification::fake();

    $exemplar = acervo(ExemplarSituacao::Reservado);
    reservaDisponivel(usuario('ana'), $exemplar, CarbonImmutable::now());
    reservaAguardando(usuario('bob'), $exemplar);

    expect(app(ReconciliacaoFilaReservaService::class)->reconciliar())->toBe(0);
});

it('nao mexe em fila sem exemplar reservado', function () {
    Notification::fake();

    $exemplar = acervo(ExemplarSituacao::Emprestado);
    reservaAguardando(usuario('ana'), $exemplar);

    expect(app(ReconciliacaoFilaReservaService::class)->reconciliar())->toBe(0);
});

it('reconcilia pelo comando agendado', function () {
    Queue::fake();
    Notification::fake();

    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $emprestimo = emprestimoEmAndamento(usuario('ana'), $exemplar);
    reservaAguardando(usuario('bob'), $exemplar);

    app(DevolucaoEmprestimoService::class)->devolver($emprestimo);

    $this->artisan('reservas:reconciliar')
        ->expectsOutputToContain('Filas retomadas: 1')
        ->assertSuccessful();
});

it('deixa o listener da cadeia retentar antes de desistir', function () {
    expect((new ReflectionClass(DisponibilizarProximaReservaAposDevolucao::class))
        ->getDefaultProperties()['tries'])->toBeGreaterThan(1);
});

<?php

use Carbon\CarbonImmutable;
use Emprestimos\Application\Listeners\DisponibilizarProximaReservaAposDevolucao;
use Emprestimos\Application\Notifications\ReservaDisponivel;
use Emprestimos\Domain\Enums\ReservaSituacao;
use Emprestimos\Domain\Events\EmprestimoFoiDevolvido;
use Emprestimos\Domain\Services\CancelamentoReservaService;
use Emprestimos\Domain\Services\DevolucaoEmprestimoService;
use Emprestimos\Domain\Services\ExpiracaoReservaService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Inventario\Domain\Enums\ExemplarSituacao;

it('marca o exemplar como reservado na propria transacao da devolucao quando ha fila', function () {
    Queue::fake();

    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $emprestimo = emprestimoEmAndamento(usuario('ana@x.test'), $exemplar);
    reservaAguardando(usuario('bob@x.test'), $exemplar);

    app(DevolucaoEmprestimoService::class)->devolver($emprestimo);

    expect($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::Reservado);
});

it('devolve o exemplar ao acervo quando nao ha fila', function () {
    Queue::fake();

    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $emprestimo = emprestimoEmAndamento(usuario('ana@x.test'), $exemplar);

    app(DevolucaoEmprestimoService::class)->devolver($emprestimo);

    expect($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::NoAcervo);
});

it('promove a fila pela fila de jobs, nao no request da devolucao', function () {
    Queue::fake();

    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $emprestimo = emprestimoEmAndamento(usuario('ana@x.test'), $exemplar);
    $reserva = reservaAguardando(usuario('bob@x.test'), $exemplar);

    app(DevolucaoEmprestimoService::class)->devolver($emprestimo);

    expect($reserva->refresh()->situacao)->toBe(ReservaSituacao::Aguardando);

    Queue::assertPushed(
        CallQueuedListener::class,
        fn (CallQueuedListener $job) => $job->class === DisponibilizarProximaReservaAposDevolucao::class,
    );
});

it('percorre a cadeia da devolucao ate a notificacao', function () {
    Notification::fake();

    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $emprestimo = emprestimoEmAndamento(usuario('ana@x.test'), $exemplar);
    $bob = usuario('bob@x.test');
    $reserva = reservaAguardando($bob, $exemplar);

    app(DevolucaoEmprestimoService::class)->devolver($emprestimo);

    $reserva->refresh();

    expect($reserva->situacao)->toBe(ReservaSituacao::Disponivel)
        ->and($reserva->exemplar_id)->toBe($exemplar->id)
        ->and($reserva->janela)->not->toBeNull()
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::Reservado);

    Notification::assertSentTo($bob, ReservaDisponivel::class);
});

it('repassa o exemplar ao proximo da fila quando a reserva expira', function () {
    Notification::fake();

    $exemplar = acervo(ExemplarSituacao::Reservado);
    $daAna = reservaDisponivel(usuario('ana@x.test'), $exemplar, CarbonImmutable::now()->subHours(72));
    $carla = usuario('carla@x.test');
    $daCarla = reservaAguardando($carla, $exemplar);

    $expiradas = app(ExpiracaoReservaService::class)->expirarVencidas();

    expect($expiradas)->toBe(1)
        ->and($daAna->refresh()->situacao)->toBe(ReservaSituacao::Expirada)
        ->and($daCarla->refresh()->situacao)->toBe(ReservaSituacao::Disponivel)
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::Reservado);

    Notification::assertSentTo($carla, ReservaDisponivel::class);
});

it('libera o exemplar para o acervo quando a reserva expira e a fila acaba', function () {
    Notification::fake();

    $exemplar = acervo(ExemplarSituacao::Reservado);
    reservaDisponivel(usuario('ana@x.test'), $exemplar, CarbonImmutable::now()->subHours(72));

    app(ExpiracaoReservaService::class)->expirarVencidas();

    expect($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::NoAcervo);
});

it('repassa o exemplar ao proximo quando uma reserva disponivel e cancelada', function () {
    Notification::fake();

    $exemplar = acervo(ExemplarSituacao::Reservado);
    $daAna = reservaDisponivel(usuario('ana@x.test'), $exemplar, CarbonImmutable::now());
    $carla = usuario('carla@x.test');
    $daCarla = reservaAguardando($carla, $exemplar);

    app(CancelamentoReservaService::class)->cancelar($daAna);

    expect($daAna->refresh()->situacao)->toBe(ReservaSituacao::Cancelada)
        ->and($daCarla->refresh()->situacao)->toBe(ReservaSituacao::Disponivel)
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::Reservado);

    Notification::assertSentTo($carla, ReservaDisponivel::class);
});

it('libera o exemplar quando a reserva disponivel e cancelada e a fila acaba', function () {
    Notification::fake();

    $exemplar = acervo(ExemplarSituacao::Reservado);
    $daAna = reservaDisponivel(usuario('ana@x.test'), $exemplar, CarbonImmutable::now());

    app(CancelamentoReservaService::class)->cancelar($daAna);

    expect($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::NoAcervo);
});

it('sobrevive a serializacao da fila reidratando o model do banco', function () {
    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $emprestimo = emprestimoEmAndamento(usuario('ana@x.test'), $exemplar);

    $payload = serialize(new EmprestimoFoiDevolvido($emprestimo));

    expect($payload)->toContain('ModelIdentifier');

    $volta = unserialize($payload);

    expect($volta->emprestimo->is($emprestimo))->toBeTrue();
});

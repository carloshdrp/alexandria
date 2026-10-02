<?php

use Carbon\CarbonImmutable;
use Emprestimos\Domain\ValueObjects\JanelaReserva;
use Emprestimos\Domain\ValueObjects\PrazoEmprestimo;

afterEach(fn () => CarbonImmutable::setTestNow());

it('dá 14 dias corridos a partir da retirada', function () {
    $prazo = PrazoEmprestimo::iniciar(CarbonImmutable::parse('2026-03-01 10:00'));

    expect($prazo->prazoDevolucao()->format('Y-m-d'))->toBe('2026-03-15')
        ->and($prazo->retiradoEm()->format('Y-m-d H:i'))->toBe('2026-03-01 10:00');
});

it('só considera atraso a partir do dia seguinte ao prazo', function () {
    $prazo = PrazoEmprestimo::iniciar(CarbonImmutable::parse('2026-03-01'));

    expect($prazo->estaAtrasado(CarbonImmutable::parse('2026-03-15 23:59:59')))->toBeFalse()
        ->and($prazo->diasAtraso(CarbonImmutable::parse('2026-03-15 23:59:59')))->toBe(0)
        ->and($prazo->estaAtrasado(CarbonImmutable::parse('2026-03-16 00:00:00')))->toBeTrue()
        ->and($prazo->diasAtraso(CarbonImmutable::parse('2026-03-16 00:00:00')))->toBe(1)
        ->and($prazo->diasAtraso(CarbonImmutable::parse('2026-03-20 18:00:00')))->toBe(5);
});

it('usa o relógio quando não recebe referência', function () {
    CarbonImmutable::setTestNow('2026-03-16 08:00:00');

    $prazo = PrazoEmprestimo::iniciar(CarbonImmutable::parse('2026-03-01'));

    expect($prazo->estaAtrasado())->toBeTrue()
        ->and($prazo->diasAtraso())->toBe(1)
        ->and($prazo->diasParaDevolucao())->toBe(-1);
});

it('estende 14 dias a partir do prazo anterior, não da data atual', function () {
    CarbonImmutable::setTestNow('2026-03-14');

    $prazo = PrazoEmprestimo::iniciar(CarbonImmutable::parse('2026-03-01'));
    $estendido = $prazo->estender();

    expect($estendido->prazoDevolucao()->format('Y-m-d'))->toBe('2026-03-29')
        ->and($estendido->retiradoEm()->equalTo($prazo->retiradoEm()))->toBeTrue()
        ->and($prazo->prazoDevolucao()->format('Y-m-d'))->toBe('2026-03-15');
});

it('abre a janela de retirada por exatamente 48 horas', function () {
    $janela = JanelaReserva::abrir(CarbonImmutable::parse('2026-03-01 10:00:00'));

    expect($janela->disponibilizadaEm()->format('Y-m-d H:i:s'))->toBe('2026-03-01 10:00:00')
        ->and($janela->expiraEm()->format('Y-m-d H:i:s'))->toBe('2026-03-03 10:00:00')
        ->and($janela->expirou(CarbonImmutable::parse('2026-03-03 10:00:00')))->toBeFalse()
        ->and($janela->expirou(CarbonImmutable::parse('2026-03-03 10:00:01')))->toBeTrue();
});

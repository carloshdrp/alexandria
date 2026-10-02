<?php

use Carbon\CarbonImmutable;
use Emprestimos\Domain\Enums\EmprestimoSituacao;
use Emprestimos\Domain\Events\EmprestimoFoiAtrasado;
use Emprestimos\Domain\Services\DevolucaoEmprestimoService;
use Emprestimos\Domain\Services\MarcacaoAtrasoService;
use Illuminate\Support\Facades\Event;
use Inventario\Domain\Enums\ExemplarSituacao;

it('marca como atrasado o emprestimo em andamento com prazo vencido', function () {
    Event::fake([EmprestimoFoiAtrasado::class]);

    $vencido = emprestimoEmAndamento(
        usuario('ana'),
        acervo(ExemplarSituacao::Emprestado),
        CarbonImmutable::now()->subDays(20),
    );

    expect(app(MarcacaoAtrasoService::class)->marcarVencidos())->toBe(1)
        ->and($vencido->refresh()->situacao)->toBe(EmprestimoSituacao::Atrasado);

    Event::assertDispatched(
        EmprestimoFoiAtrasado::class,
        fn (EmprestimoFoiAtrasado $evento) => $evento->emprestimo->is($vencido),
    );
});

it('nao toca em emprestimo dentro do prazo', function () {
    $noPrazo = emprestimoEmAndamento(
        usuario('ana'),
        acervo(ExemplarSituacao::Emprestado),
        CarbonImmutable::now()->subDays(2),
    );

    expect(app(MarcacaoAtrasoService::class)->marcarVencidos())->toBe(0)
        ->and($noPrazo->refresh()->situacao)->toBe(EmprestimoSituacao::Andamento);
});

it('nao marca duas vezes o mesmo emprestimo', function () {
    emprestimoEmAndamento(
        usuario('ana'),
        acervo(ExemplarSituacao::Emprestado),
        CarbonImmutable::now()->subDays(20),
    );

    $servico = app(MarcacaoAtrasoService::class);

    expect($servico->marcarVencidos())->toBe(1)
        ->and($servico->marcarVencidos())->toBe(0);
});

it('marca atrasados pelo comando agendado', function () {
    emprestimoEmAndamento(
        usuario('ana'),
        acervo(ExemplarSituacao::Emprestado),
        CarbonImmutable::now()->subDays(20),
    );

    $this->artisan('emprestimos:marcar-atrasados')
        ->expectsOutputToContain('Empréstimos marcados como atrasados: 1')
        ->assertSuccessful();
});

it('nao impede a devolucao de um emprestimo ja marcado como atrasado', function () {
    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $emprestimo = emprestimoEmAndamento(usuario('ana'), $exemplar, CarbonImmutable::now()->subDays(20));

    app(MarcacaoAtrasoService::class)->marcarVencidos();

    app(DevolucaoEmprestimoService::class)->devolver($emprestimo->refresh());

    expect($emprestimo->refresh()->situacao)->toBe(EmprestimoSituacao::Devolvido)
        ->and($emprestimo->multa)->not->toBeNull();
});

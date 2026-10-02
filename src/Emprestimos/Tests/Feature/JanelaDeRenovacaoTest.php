<?php

use Carbon\CarbonImmutable;
use Emprestimos\Application\Notifications\VencimentoProximo;
use Emprestimos\Domain\Services\AvisoVencimentoService;
use Emprestimos\Domain\Services\RenovacaoEmprestimoService;
use Emprestimos\Domain\ValueObjects\PrazoEmprestimo;
use Illuminate\Support\Facades\Notification;
use Inventario\Domain\Enums\ExemplarSituacao;

it('recusa renovar quando ainda faltam mais de um dia para o prazo', function () {
    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $emprestimo = emprestimoEmAndamento($leitor, $exemplar, CarbonImmutable::now()->subDays(3));

    expect(fn () => app(RenovacaoEmprestimoService::class)->renovar($emprestimo))
        ->toThrow(DomainException::class, 'A renovação fica disponível a partir de');

    expect($emprestimo->refresh()->qtd_renovacoes)->toBe(0);
});

it('recusa renovar um empréstimo já vencido', function () {
    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $emprestimo = emprestimoEmAndamento($leitor, $exemplar, CarbonImmutable::now()->subDays(20));

    expect(fn () => app(RenovacaoEmprestimoService::class)->renovar($emprestimo))
        ->toThrow(DomainException::class, 'Não é possível renovar um empréstimo atrasado.');

    expect($emprestimo->refresh()->qtd_renovacoes)->toBe(0);
});

it('renova quando falta um dia para o prazo', function () {
    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $emprestimo = emprestimoEmAndamento($leitor, $exemplar, CarbonImmutable::now()->subDays(13));

    app(RenovacaoEmprestimoService::class)->renovar($emprestimo);

    expect($emprestimo->refresh()->qtd_renovacoes)->toBe(1);
});

it('avisa o leitor cujo prazo termina em até um dia', function () {
    Notification::fake();

    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $emprestimo = emprestimoEmAndamento($leitor, $exemplar, CarbonImmutable::now()->subDays(13));

    expect(app(AvisoVencimentoService::class)->avisarProximos())->toBe(1);

    expect($emprestimo->refresh()->aviso_vencimento_em)->not->toBeNull();

    Notification::assertSentTo($leitor, VencimentoProximo::class);
});

it('não avisa duas vezes o mesmo empréstimo', function () {
    Notification::fake();

    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::Emprestado);
    emprestimoEmAndamento($leitor, $exemplar, CarbonImmutable::now()->subDays(13));

    app(AvisoVencimentoService::class)->avisarProximos();

    expect(app(AvisoVencimentoService::class)->avisarProximos())->toBe(0);

    Notification::assertSentToTimes($leitor, VencimentoProximo::class, 1);
});

it('não avisa empréstimo longe do prazo nem já devolvido', function () {
    Notification::fake();

    $leitor = usuario('leitor');
    emprestimoEmAndamento($leitor, acervo(ExemplarSituacao::Emprestado), CarbonImmutable::now()->subDays(3));

    $devolvido = emprestimoEmAndamento($leitor, acervo(ExemplarSituacao::Emprestado), CarbonImmutable::now()->subDays(13));
    $devolvido->devolver();
    $devolvido->save();

    expect(app(AvisoVencimentoService::class)->avisarProximos())->toBe(0);

    Notification::assertNothingSent();
});

it('reabre o aviso quando o empréstimo é renovado', function () {
    Notification::fake();

    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $emprestimo = emprestimoEmAndamento($leitor, $exemplar, CarbonImmutable::now()->subDays(13));

    app(AvisoVencimentoService::class)->avisarProximos();

    app(RenovacaoEmprestimoService::class)->renovar($emprestimo->refresh());

    expect($emprestimo->refresh()->aviso_vencimento_em)->toBeNull();
});

it('conta os dias que faltam para a devolução', function () {
    $prazo = PrazoEmprestimo::iniciar(CarbonImmutable::now()->subDays(13));

    expect($prazo->diasParaDevolucao())->toBe(1)
        ->and($prazo->estaNaJanelaDeRenovacao())->toBeTrue()
        ->and($prazo->inicioDaJanelaDeRenovacao()->format('d/m/Y'))
        ->toBe(CarbonImmutable::now()->format('d/m/Y'));

    $longe = PrazoEmprestimo::iniciar(CarbonImmutable::now());

    expect($longe->diasParaDevolucao())->toBe(14)
        ->and($longe->estaNaJanelaDeRenovacao())->toBeFalse();
});

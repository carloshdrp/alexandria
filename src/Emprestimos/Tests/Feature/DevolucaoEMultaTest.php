<?php

use Acesso\Domain\Enums\UsuarioPapel;
use Carbon\CarbonImmutable;
use Emprestimos\Application\Livewire\EmprestimosEmAberto;
use Emprestimos\Application\Livewire\MinhasMultas;
use Emprestimos\Application\Livewire\MultasPendentes;
use Emprestimos\Application\Livewire\SituacaoDoCliente;
use Emprestimos\Application\Notifications\EmprestimoDevolvido;
use Emprestimos\Application\Notifications\MultaGerada;
use Emprestimos\Domain\Enums\EmprestimoSituacao;
use Emprestimos\Domain\Enums\MultaSituacao;
use Emprestimos\Domain\Events\MultaFoiPaga;
use Emprestimos\Domain\Models\Multa;
use Emprestimos\Domain\Services\DevolucaoEmprestimoService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Inventario\Domain\Enums\ExemplarSituacao;
use Livewire\Livewire;

it('encerra o empréstimo devolvido no prazo sem multa e avisa o leitor', function () {
    Notification::fake();
    $this->travelTo(CarbonImmutable::parse('2026-03-01 10:00:00'));

    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $emprestimo = emprestimoEmAndamento($leitor, $exemplar, CarbonImmutable::now());

    $this->travelTo(CarbonImmutable::parse('2026-03-10 15:00:00'));

    app(DevolucaoEmprestimoService::class)->devolver($emprestimo);

    $emprestimo->refresh();

    expect($emprestimo->situacao)->toBe(EmprestimoSituacao::Devolvido)
        ->and($emprestimo->devolvido_em?->format('Y-m-d H:i'))->toBe('2026-03-10 15:00')
        ->and($emprestimo->multa)->toBeNull()
        ->and(Multa::count())->toBe(0)
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::NoAcervo);

    Notification::assertSentTo($leitor, EmprestimoDevolvido::class, function (EmprestimoDevolvido $notificacao) use ($leitor) {
        return str_contains(implode(' ', $notificacao->toMail($leitor)->introLines), '10/03/2026 15:00');
    });
    Notification::assertNotSentTo($leitor, MultaGerada::class);
});

it('não gera multa quando devolve no dia do vencimento', function () {
    Notification::fake();
    $this->travelTo(CarbonImmutable::parse('2026-03-01 10:00:00'));

    $leitor = usuario('leitor');
    $emprestimo = emprestimoEmAndamento($leitor, acervo(ExemplarSituacao::Emprestado), CarbonImmutable::now());

    $this->travelTo(CarbonImmutable::parse('2026-03-15 23:30:00'));

    app(DevolucaoEmprestimoService::class)->devolver($emprestimo);

    expect($emprestimo->refresh()->situacao)->toBe(EmprestimoSituacao::Devolvido)
        ->and(Multa::count())->toBe(0);

    Notification::assertNotSentTo($leitor, MultaGerada::class);
});

it('cobra um real por dia de atraso', function (int $dias) {
    Notification::fake();
    $this->travelTo(CarbonImmutable::parse('2026-03-01 10:00:00'));

    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $emprestimo = emprestimoEmAndamento($leitor, $exemplar, CarbonImmutable::now());

    $this->travelTo(CarbonImmutable::parse('2026-03-15 08:00:00')->addDays($dias));

    app(DevolucaoEmprestimoService::class)->devolver($emprestimo);

    $multa = $emprestimo->refresh()->multa;

    expect($multa)->not->toBeNull()
        ->and($multa->situacao)->toBe(MultaSituacao::Pendente)
        ->and($multa->user_id)->toBe($leitor->id)
        ->and($multa->dias_atraso)->toBe($dias)
        ->and($multa->valor->getNativeValue())->toBe((float) $dias)
        ->and($multa->paga_em)->toBeNull()
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::NoAcervo);

    $valorEsperado = number_format($dias, 2, ',', '.');

    Notification::assertSentTo($leitor, MultaGerada::class, function (MultaGerada $notificacao) use ($leitor, $valorEsperado) {
        return str_contains(implode(' ', $notificacao->toMail($leitor)->introLines), "R$ {$valorEsperado}");
    });
})->with(['um dia' => 1, 'cinco dias' => 5]);

it('não devolve duas vezes', function () {
    $leitor = usuario('leitor');
    $emprestimo = emprestimoEmAndamento($leitor, acervo(ExemplarSituacao::Emprestado), CarbonImmutable::now()->subDays(20));

    app(DevolucaoEmprestimoService::class)->devolver($emprestimo);

    $devolvidoEm = $emprestimo->refresh()->devolvido_em;

    $this->travel(1)->days();

    expect(fn () => app(DevolucaoEmprestimoService::class)->devolver($emprestimo))
        ->toThrow(DomainException::class, 'já foi devolvido');

    expect($emprestimo->refresh()->devolvido_em?->equalTo($devolvidoEm))->toBeTrue()
        ->and(Multa::count())->toBe(1);
});

it('registra o pagamento da multa no balcão', function () {
    Event::fake([MultaFoiPaga::class]);
    $this->travelTo(CarbonImmutable::parse('2026-03-20 14:00:00'));

    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');
    $multa = multaPendente($cliente);

    Livewire::actingAs($bibliotecario)
        ->test(MultasPendentes::class)
        ->call('registrarPagamento', $multa->id)
        ->assertHasNoErrors();

    $multa->refresh();

    expect($multa->situacao)->toBe(MultaSituacao::Paga)
        ->and($multa->paga_em?->format('Y-m-d H:i'))->toBe('2026-03-20 14:00');

    Event::assertDispatched(MultaFoiPaga::class, fn (MultaFoiPaga $evento) => $evento->multa->is($multa));
});

it('não paga a mesma multa duas vezes', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $multa = multaPendente(usuario('cliente'));

    $multa->pagar();
    $multa->save();

    $pagaEm = $multa->refresh()->paga_em;

    $this->travel(1)->days();

    Livewire::actingAs($bibliotecario)
        ->test(MultasPendentes::class)
        ->call('registrarPagamento', $multa->id)
        ->assertHasErrors('dominio');

    expect($multa->refresh()->paga_em?->equalTo($pagaEm))->toBeTrue();
});

it('só o bibliotecário registra pagamento', function () {
    $leitor = usuario('leitor');
    $multa = multaPendente($leitor);

    Livewire::actingAs($leitor)
        ->test(MultasPendentes::class)
        ->call('registrarPagamento', $multa->id)
        ->assertForbidden();

    expect($multa->refresh()->situacao)->toBe(MultaSituacao::Pendente);
});

it('a ficha do cliente só paga multas daquele cliente', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');
    $multaDeOutro = multaPendente(usuario('outro'));

    expect(fn () => Livewire::actingAs($bibliotecario)
        ->test(SituacaoDoCliente::class, ['user' => $cliente])
        ->call('registrarPagamento', $multaDeOutro->id))
        ->toThrow(ModelNotFoundException::class);

    expect($multaDeOutro->refresh()->situacao)->toBe(MultaSituacao::Pendente);
});

it('não altera o valor nem os dias de uma multa registrada', function () {
    $multa = multaPendente(usuario('leitor'));

    $multa->dias_atraso = 9;

    expect(fn () => $multa->save())->toThrow(DomainException::class);

    expect($multa->refresh()->dias_atraso)->toBe(1);
});

it('separa as multas pendentes das pagas do próprio leitor', function () {
    $leitor = usuario('leitor');
    $pendente = multaPendente($leitor);
    $paga = multaPendente($leitor);
    $paga->pagar();
    $paga->save();
    multaPendente(usuario('outro'));

    Livewire::actingAs($leitor)
        ->test(MinhasMultas::class)
        ->assertViewHas('pendentes', fn (Collection $multas) => $multas->pluck('id')->all() === [$pendente->id])
        ->assertViewHas('pagas', fn (Collection $multas) => $multas->pluck('id')->all() === [$paga->id]);
});

it('só o bibliotecário registra devolução', function () {
    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $emprestimo = emprestimoEmAndamento($leitor, $exemplar);

    Livewire::actingAs($leitor)
        ->test(EmprestimosEmAberto::class)
        ->call('devolver', $emprestimo->id)
        ->assertForbidden();

    expect($emprestimo->refresh()->situacao)->toBe(EmprestimoSituacao::Andamento)
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::Emprestado);
});

it('registra a devolução atrasada pela lista do balcão', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $emprestimo = emprestimoEmAndamento(usuario('leitor'), $exemplar, CarbonImmutable::now()->subDays(20));

    Livewire::actingAs($bibliotecario)
        ->test(EmprestimosEmAberto::class)
        ->call('devolver', $emprestimo->id)
        ->assertHasNoErrors();

    expect($emprestimo->refresh()->situacao)->toBe(EmprestimoSituacao::Devolvido)
        ->and($emprestimo->multa?->dias_atraso)->toBe(6)
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::NoAcervo);
});

it('a ficha do cliente só devolve empréstimos daquele cliente', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');
    $deOutro = emprestimoEmAndamento(usuario('outro'), acervo(ExemplarSituacao::Emprestado));

    expect(fn () => Livewire::actingAs($bibliotecario)
        ->test(SituacaoDoCliente::class, ['user' => $cliente])
        ->call('devolver', $deOutro->id))
        ->toThrow(ModelNotFoundException::class);

    expect($deOutro->refresh()->situacao)->toBe(EmprestimoSituacao::Andamento);
});

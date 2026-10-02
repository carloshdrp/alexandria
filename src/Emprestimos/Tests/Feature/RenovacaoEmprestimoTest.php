<?php

use App\Acesso\Domain\Enums\UsuarioPapel;
use Carbon\CarbonImmutable;
use Emprestimos\Application\Livewire\EmprestimosEmAberto;
use Emprestimos\Application\Livewire\MeusEmprestimos;
use Emprestimos\Application\Livewire\SituacaoDoCliente;
use Emprestimos\Application\Notifications\EmprestimoRenovado;
use Emprestimos\Domain\Models\EmprestimoRenovacao;
use Emprestimos\Domain\Services\RenovacaoEmprestimoService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Notification;
use Inventario\Domain\Enums\ExemplarSituacao;
use Livewire\Livewire;

it('renova por mais 14 dias a partir do prazo anterior e registra a auditoria', function () {
    Notification::fake();
    $this->travelTo(CarbonImmutable::parse('2026-03-01 10:00:00'));

    $leitor = usuario('leitor');
    $emprestimo = emprestimoEmAndamento($leitor, acervo(ExemplarSituacao::Emprestado), CarbonImmutable::now());

    $this->travelTo(CarbonImmutable::parse('2026-03-14 09:00:00'));

    app(RenovacaoEmprestimoService::class)->renovar($emprestimo);

    $emprestimo->refresh();

    expect($emprestimo->prazo->prazoDevolucao()->format('Y-m-d'))->toBe('2026-03-29')
        ->and($emprestimo->prazo->retiradoEm()->format('Y-m-d'))->toBe('2026-03-01')
        ->and($emprestimo->qtd_renovacoes)->toBe(1)
        ->and($emprestimo->renovacoes)->toHaveCount(1);

    $renovacao = $emprestimo->renovacoes->first();

    expect($renovacao->sequencia)->toBe(1)
        ->and($renovacao->user_id)->toBe($leitor->id)
        ->and($renovacao->prazo_anterior->format('Y-m-d'))->toBe('2026-03-15')
        ->and($renovacao->prazo_novo->format('Y-m-d'))->toBe('2026-03-29')
        ->and($renovacao->renovado_em->format('Y-m-d H:i'))->toBe('2026-03-14 09:00');

    Notification::assertSentTo($leitor, EmprestimoRenovado::class, function (EmprestimoRenovado $notificacao) use ($leitor) {
        $linhas = implode(' ', $notificacao->toMail($leitor)->introLines);

        return str_contains($linhas, '29/03/2026') && str_contains($linhas, 'Resta 1 renovação');
    });
});

it('permite duas renovações e recusa a terceira', function () {
    $this->travelTo(CarbonImmutable::parse('2026-03-01 10:00:00'));

    $emprestimo = emprestimoEmAndamento(usuario('leitor'), acervo(ExemplarSituacao::Emprestado), CarbonImmutable::now());
    $servico = app(RenovacaoEmprestimoService::class);

    $this->travelTo(CarbonImmutable::parse('2026-03-14 10:00:00'));
    $servico->renovar($emprestimo);

    $this->travelTo(CarbonImmutable::parse('2026-03-28 10:00:00'));
    $servico->renovar($emprestimo->refresh());

    expect($emprestimo->refresh()->prazo->prazoDevolucao()->format('Y-m-d'))->toBe('2026-04-12')
        ->and($emprestimo->qtd_renovacoes)->toBe(2);

    $this->travelTo(CarbonImmutable::parse('2026-04-11 10:00:00'));

    expect(fn () => $servico->renovar($emprestimo->refresh()))
        ->toThrow(DomainException::class, 'máximo de renovações');

    expect($emprestimo->refresh()->qtd_renovacoes)->toBe(2)
        ->and($emprestimo->prazo->prazoDevolucao()->format('Y-m-d'))->toBe('2026-04-12')
        ->and(EmprestimoRenovacao::where('emprestimo_id', $emprestimo->id)->pluck('sequencia')->all())->toBe([1, 2]);
});

it('recusa renovar quando outro leitor aguarda a obra na fila', function () {
    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $emprestimo = emprestimoEmAndamento($leitor, $exemplar, CarbonImmutable::now()->subDays(13));

    reservaAguardando(usuario('outro'), $exemplar);

    expect(fn () => app(RenovacaoEmprestimoService::class)->renovar($emprestimo))
        ->toThrow(DomainException::class, 'reserva pendente');

    expect($emprestimo->refresh()->qtd_renovacoes)->toBe(0)
        ->and(EmprestimoRenovacao::count())->toBe(0);
});

it('recusa renovar quando outro leitor já tem um exemplar da obra disponível para retirar', function () {
    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $emprestimo = emprestimoEmAndamento($leitor, $exemplar, CarbonImmutable::now()->subDays(13));

    reservaDisponivel(usuario('outro'), exemplarIrmao($exemplar, ExemplarSituacao::Reservado), CarbonImmutable::now());

    expect(fn () => app(RenovacaoEmprestimoService::class)->renovar($emprestimo))
        ->toThrow(DomainException::class, 'reserva pendente');

    expect($emprestimo->refresh()->qtd_renovacoes)->toBe(0);
});

it('recusa renovar com multa pendente', function () {
    $leitor = usuario('leitor');
    $emprestimo = emprestimoEmAndamento($leitor, acervo(ExemplarSituacao::Emprestado), CarbonImmutable::now()->subDays(13));

    multaPendente($leitor);

    expect(fn () => app(RenovacaoEmprestimoService::class)->renovar($emprestimo))
        ->toThrow(DomainException::class, 'multa pendente');

    expect($emprestimo->refresh()->qtd_renovacoes)->toBe(0)
        ->and(EmprestimoRenovacao::count())->toBe(0);
});

it('recusa renovar empréstimo devolvido', function () {
    $emprestimo = emprestimoDevolvido(usuario('leitor'), acervo(ExemplarSituacao::NoAcervo), CarbonImmutable::now()->subDays(13), CarbonImmutable::now());

    expect(fn () => app(RenovacaoEmprestimoService::class)->renovar($emprestimo))
        ->toThrow(DomainException::class, 'devolvido');

    expect($emprestimo->refresh()->qtd_renovacoes)->toBe(0);
});

it('o registro de renovação é imutável', function () {
    $emprestimo = emprestimoEmAndamento(usuario('leitor'), acervo(ExemplarSituacao::Emprestado), CarbonImmutable::now()->subDays(13));

    app(RenovacaoEmprestimoService::class)->renovar($emprestimo);

    $renovacao = EmprestimoRenovacao::firstOrFail();
    $renovacao->sequencia = 5;

    expect(fn () => $renovacao->save())->toThrow(DomainException::class, 'não pode ser atualizado');

    expect($renovacao->refresh()->sequencia)->toBe(1);
});

it('o leitor renova o próprio empréstimo pela tela', function () {
    $leitor = usuario('leitor');
    $emprestimo = emprestimoEmAndamento($leitor, acervo(ExemplarSituacao::Emprestado), CarbonImmutable::now()->subDays(13));

    Livewire::actingAs($leitor)
        ->test(MeusEmprestimos::class)
        ->call('renovar', $emprestimo->id)
        ->assertHasNoErrors();

    expect($emprestimo->refresh()->qtd_renovacoes)->toBe(1);
});

it('o leitor não renova empréstimo de outro', function () {
    $leitor = usuario('leitor');
    $deOutro = emprestimoEmAndamento(usuario('outro'), acervo(ExemplarSituacao::Emprestado), CarbonImmutable::now()->subDays(13));

    Livewire::actingAs($leitor)
        ->test(MeusEmprestimos::class)
        ->call('renovar', $deOutro->id)
        ->assertForbidden();

    expect($deOutro->refresh()->qtd_renovacoes)->toBe(0);
});

it('mostra a recusa do domínio na tela do leitor', function () {
    $leitor = usuario('leitor');
    $emprestimo = emprestimoEmAndamento($leitor, acervo(ExemplarSituacao::Emprestado), CarbonImmutable::now()->subDays(3));

    Livewire::actingAs($leitor)
        ->test(MeusEmprestimos::class)
        ->call('renovar', $emprestimo->id)
        ->assertHasErrors('dominio');

    expect($emprestimo->refresh()->qtd_renovacoes)->toBe(0);
});

it('o bibliotecário renova pela lista do balcão', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $emprestimo = emprestimoEmAndamento(usuario('leitor'), acervo(ExemplarSituacao::Emprestado), CarbonImmutable::now()->subDays(13));

    Livewire::actingAs($bibliotecario)
        ->test(EmprestimosEmAberto::class)
        ->call('renovar', $emprestimo->id)
        ->assertHasNoErrors();

    expect($emprestimo->refresh()->qtd_renovacoes)->toBe(1);
});

it('a ficha do cliente só renova empréstimos daquele cliente', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');
    $deOutro = emprestimoEmAndamento(usuario('outro'), acervo(ExemplarSituacao::Emprestado), CarbonImmutable::now()->subDays(13));

    expect(fn () => Livewire::actingAs($bibliotecario)
        ->test(SituacaoDoCliente::class, ['user' => $cliente])
        ->call('renovar', $deOutro->id))
        ->toThrow(ModelNotFoundException::class);

    expect($deOutro->refresh()->qtd_renovacoes)->toBe(0);
});

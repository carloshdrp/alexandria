<?php

use Acesso\Domain\Enums\UsuarioPapel;
use Acesso\Domain\Enums\UsuarioSituacao;
use Acesso\Domain\Events\UsuarioFoiDesbloqueado;
use Carbon\CarbonImmutable;
use Emprestimos\Application\Livewire\EmprestimosEmAberto;
use Emprestimos\Application\Livewire\MeusEmprestimos;
use Emprestimos\Application\Livewire\SituacaoDoCliente;
use Emprestimos\Domain\Enums\EmprestimoSituacao;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Inventario\Domain\Enums\ExemplarSituacao;
use Livewire\Livewire;

function idsEmOrdem(iterable $registros): array
{
    if ($registros instanceof LengthAwarePaginator) {
        $registros = $registros->getCollection();
    }

    return collect($registros)->pluck('id')->sort()->values()->all();
}

it('resume a situação do cliente para o balcão', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');
    $outro = usuario('outro');

    $andamento = emprestimoEmAndamento($cliente, acervo(ExemplarSituacao::Emprestado));
    $atrasado = emprestimoEmAndamento($cliente, acervo(ExemplarSituacao::Emprestado), CarbonImmutable::now()->subDays(20));
    $atrasado->marcarAtrasado();
    $atrasado->save();
    $devolvido = emprestimoDevolvido($cliente, acervo(ExemplarSituacao::NoAcervo), CarbonImmutable::now()->subDays(30), CarbonImmutable::now()->subDays(20));

    $pendente = multaPendente($cliente);
    $paga = multaPendente($cliente);
    $paga->pagar();
    $paga->save();

    $exemplarReservado = acervo(ExemplarSituacao::Emprestado);
    $aguardando = reservaAguardando($cliente, $exemplarReservado);
    $cancelada = reservaAguardando($cliente, acervo(ExemplarSituacao::Emprestado));
    $cancelada->cancelar();
    $cancelada->save();

    emprestimoEmAndamento($outro, acervo(ExemplarSituacao::Emprestado));
    multaPendente($outro);
    reservaAguardando($outro, acervo(ExemplarSituacao::Emprestado));

    Livewire::actingAs($bibliotecario)
        ->test(SituacaoDoCliente::class, ['user' => $cliente])
        ->assertViewHas('ativos', fn (Collection $ativos) => idsEmOrdem($ativos) === idsEmOrdem([$andamento, $atrasado]))
        ->assertViewHas('historico', fn (Collection $historico) => idsEmOrdem($historico) === idsEmOrdem([$devolvido, $pendente->emprestimo, $paga->emprestimo]))
        ->assertViewHas('multasPendentes', fn (Collection $multas) => idsEmOrdem($multas) === [$pendente->id])
        ->assertViewHas('multasPagas', fn (Collection $multas) => idsEmOrdem($multas) === [$paga->id])
        ->assertViewHas('reservasAtivas', fn (Collection $reservas) => idsEmOrdem($reservas) === [$aguardando->id])
        ->assertViewHas('obras', fn (Collection $obras) => $obras->get($aguardando->obra_id) === obraPublicada($exemplarReservado)->titulo);
});

it('bloqueia o cliente e derruba a sessão que ele tinha aberta', function () {
    $this->travelTo(CarbonImmutable::parse('2026-03-01 10:00:00'));

    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');
    $outro = usuario('outro');

    Livewire::actingAs($bibliotecario)
        ->test(SituacaoDoCliente::class, ['user' => $cliente])
        ->call('bloquear')
        ->assertHasNoErrors();

    $cliente->refresh();

    expect($cliente->situacao)->toBe(UsuarioSituacao::Bloqueado)
        ->and($cliente->bloqueado_em?->format('Y-m-d H:i'))->toBe('2026-03-01 10:00');

    $this->actingAs($cliente)
        ->get(route('emprestimos.catalogo'))
        ->assertRedirect(route('login', ['bloqueado' => 1]));

    $this->actingAs($outro)
        ->get(route('emprestimos.catalogo'))
        ->assertOk();
});

it('não bloqueia duas vezes', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');
    $cliente->bloquear();
    $cliente->save();

    $bloqueadoEm = $cliente->refresh()->bloqueado_em;

    $this->travel(1)->days();

    Livewire::actingAs($bibliotecario)
        ->test(SituacaoDoCliente::class, ['user' => $cliente])
        ->call('bloquear')
        ->assertHasErrors('dominio');

    expect($cliente->refresh()->bloqueado_em?->equalTo($bloqueadoEm))->toBeTrue();
});

it('desbloqueia o cliente', function () {
    Event::fake([UsuarioFoiDesbloqueado::class]);

    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');
    $cliente->bloquear();
    $cliente->save();

    Livewire::actingAs($bibliotecario)
        ->test(SituacaoDoCliente::class, ['user' => $cliente])
        ->call('desbloquear')
        ->assertHasNoErrors();

    $cliente->refresh();

    expect($cliente->situacao)->toBe(UsuarioSituacao::Ativo)
        ->and($cliente->bloqueado_em)->toBeNull();

    Event::assertDispatched(UsuarioFoiDesbloqueado::class, fn (UsuarioFoiDesbloqueado $evento) => $evento->user->is($cliente));
});

it('não desbloqueia quem está ativo', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');

    Livewire::actingAs($bibliotecario)
        ->test(SituacaoDoCliente::class, ['user' => $cliente])
        ->call('desbloquear')
        ->assertHasErrors('dominio');

    expect($cliente->refresh()->situacao)->toBe(UsuarioSituacao::Ativo);
});

it('só o bibliotecário vê e bloqueia clientes', function () {
    $leitor = usuario('leitor');
    $cliente = usuario('cliente');

    $this->actingAs($leitor)
        ->get(route('emprestimos.clientes.situacao', $cliente))
        ->assertForbidden();

    Livewire::actingAs($leitor)
        ->test(SituacaoDoCliente::class, ['user' => $cliente])
        ->call('bloquear')
        ->assertForbidden();

    expect($cliente->refresh()->situacao)->toBe(UsuarioSituacao::Ativo);
});

it('a ficha não abre para um bibliotecário', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $outroBibliotecario = usuario('outro', UsuarioPapel::Bibliotecario);

    Livewire::actingAs($bibliotecario)
        ->test(SituacaoDoCliente::class, ['user' => $outroBibliotecario])
        ->assertStatus(404);
});

it('separa os empréstimos ativos do histórico do leitor', function () {
    $leitor = usuario('leitor');
    $outro = usuario('outro');

    $andamento = emprestimoEmAndamento($leitor, acervo(ExemplarSituacao::Emprestado));
    $atrasado = emprestimoEmAndamento($leitor, acervo(ExemplarSituacao::Emprestado), CarbonImmutable::now()->subDays(20));
    $atrasado->marcarAtrasado();
    $atrasado->save();
    $devolvido = emprestimoDevolvido($leitor, acervo(ExemplarSituacao::NoAcervo), CarbonImmutable::now()->subDays(30), CarbonImmutable::now()->subDays(20));

    emprestimoEmAndamento($outro, acervo(ExemplarSituacao::Emprestado));
    emprestimoDevolvido($outro, acervo(ExemplarSituacao::NoAcervo), CarbonImmutable::now()->subDays(30), CarbonImmutable::now()->subDays(20));

    Livewire::actingAs($leitor)
        ->test(MeusEmprestimos::class)
        ->assertViewHas('emprestimos', fn (Collection $emprestimos) => idsEmOrdem($emprestimos) === idsEmOrdem([$andamento, $atrasado]))
        ->set('aba', 'historico')
        ->assertViewHas('emprestimos', fn (Collection $emprestimos) => idsEmOrdem($emprestimos) === [$devolvido->id]);
});

it('lista o histórico de um cliente pelo nome e pela situação', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $ana = usuario('ana');
    $bob = usuario('bob');

    $aberto = emprestimoEmAndamento($ana, acervo(ExemplarSituacao::Emprestado));
    $devolvidoDaAna = emprestimoDevolvido($ana, acervo(ExemplarSituacao::NoAcervo), CarbonImmutable::now()->subDays(30), CarbonImmutable::now()->subDays(20));
    $doBob = emprestimoEmAndamento($bob, acervo(ExemplarSituacao::Emprestado));

    Livewire::actingAs($bibliotecario)
        ->test(EmprestimosEmAberto::class)
        ->assertViewHas('emprestimos', fn (LengthAwarePaginator $emprestimos) => idsEmOrdem($emprestimos) === idsEmOrdem([$aberto, $doBob]))
        ->set('busca', 'ana')
        ->assertViewHas('emprestimos', fn (LengthAwarePaginator $emprestimos) => idsEmOrdem($emprestimos) === [$aberto->id])
        ->set('situacao', (string) EmprestimoSituacao::Devolvido->value)
        ->assertViewHas('emprestimos', fn (LengthAwarePaginator $emprestimos) => idsEmOrdem($emprestimos) === [$devolvidoDaAna->id])
        ->set('busca', $bob->email)
        ->assertViewHas('emprestimos', fn (LengthAwarePaginator $emprestimos) => $emprestimos->isEmpty());
});

it('lista o histórico de um exemplar pelo código de patrimônio', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $outroExemplar = acervo(ExemplarSituacao::Emprestado);

    $antigo = emprestimoDevolvido(usuario('ana'), $exemplar, CarbonImmutable::now()->subDays(40), CarbonImmutable::now()->subDays(30));
    $atual = emprestimoEmAndamento(usuario('bob'), $exemplar);
    emprestimoEmAndamento(usuario('carla'), $outroExemplar);

    Livewire::actingAs($bibliotecario)
        ->test(EmprestimosEmAberto::class)
        ->set('busca', strtolower((string) $exemplar->codigo_patrimonio))
        ->assertViewHas('emprestimos', fn (LengthAwarePaginator $emprestimos) => idsEmOrdem($emprestimos) === [$atual->id])
        ->set('situacao', (string) EmprestimoSituacao::Devolvido->value)
        ->assertViewHas('emprestimos', fn (LengthAwarePaginator $emprestimos) => idsEmOrdem($emprestimos) === [$antigo->id])
        ->set('situacao', '')
        ->set('busca', 'EX-000000')
        ->assertViewHas('emprestimos', fn (LengthAwarePaginator $emprestimos) => $emprestimos->isEmpty());
});

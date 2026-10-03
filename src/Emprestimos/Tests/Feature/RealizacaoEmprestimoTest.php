<?php

use Acesso\Domain\Enums\UsuarioPapel;
use Carbon\CarbonImmutable;
use Emprestimos\Application\Livewire\FichaDaObra;
use Emprestimos\Application\Livewire\SituacaoDoCliente;
use Emprestimos\Application\Notifications\EmprestimoRealizado;
use Emprestimos\Domain\Enums\EmprestimoSituacao;
use Emprestimos\Domain\Models\Emprestimo;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Notification;
use Inventario\Domain\Enums\ExemplarSituacao;
use Livewire\Livewire;

it('empresta um exemplar disponível por 14 dias e avisa o leitor', function () {
    Notification::fake();
    $this->travelTo(CarbonImmutable::parse('2026-03-01 10:00:00'));

    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::NoAcervo);

    $emprestimo = realizarEmprestimo($leitor, $exemplar);

    $emprestimo->refresh();

    expect($emprestimo->situacao)->toBe(EmprestimoSituacao::Andamento)
        ->and($emprestimo->user_id)->toBe($leitor->id)
        ->and($emprestimo->exemplar_id)->toBe($exemplar->id)
        ->and($emprestimo->qtd_renovacoes)->toBe(0)
        ->and($emprestimo->devolvido_em)->toBeNull()
        ->and($emprestimo->prazo->retiradoEm()->format('Y-m-d H:i'))->toBe('2026-03-01 10:00')
        ->and($emprestimo->prazo->prazoDevolucao()->format('Y-m-d'))->toBe('2026-03-15')
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::Emprestado);

    Notification::assertSentTo($leitor, EmprestimoRealizado::class, function (EmprestimoRealizado $notificacao) use ($leitor, $exemplar) {
        $mensagem = $notificacao->toMail($leitor);

        return $mensagem->subject === 'Empréstimo realizado: '.obraPublicada($exemplar)->titulo
            && str_contains(implode(' ', $mensagem->introLines), '15/03/2026');
    });
});

it('só empresta exemplar que está no acervo', function (ExemplarSituacao $situacao) {
    $leitor = usuario('leitor');
    $exemplar = acervo($situacao);

    expect(fn () => realizarEmprestimo($leitor, $exemplar))
        ->toThrow(DomainException::class, 'Exemplar não está disponível para empréstimos.');

    expect(Emprestimo::count())->toBe(0)
        ->and($exemplar->refresh()->situacao)->toBe($situacao);
})->with([
    'emprestado a outro leitor' => ExemplarSituacao::Emprestado,
    'baixado' => ExemplarSituacao::Baixado,
    'reservado para a fila' => ExemplarSituacao::Reservado,
]);

it('permite o terceiro empréstimo ativo e recusa o quarto', function () {
    $leitor = usuario('leitor');

    emprestimoEmAndamento($leitor, acervo(ExemplarSituacao::Emprestado));

    $atrasado = emprestimoEmAndamento($leitor, acervo(ExemplarSituacao::Emprestado), CarbonImmutable::now()->subDays(20));
    $atrasado->marcarAtrasado();
    $atrasado->save();

    realizarEmprestimo($leitor, acervo(ExemplarSituacao::NoAcervo));

    expect(Emprestimo::ativosPorUsuario($leitor->id)->count())->toBe(3);

    $quarto = acervo(ExemplarSituacao::NoAcervo);

    expect(fn () => realizarEmprestimo($leitor, $quarto))
        ->toThrow(DomainException::class, 'limite de empréstimos ativos');

    expect(Emprestimo::count())->toBe(3)
        ->and($quarto->refresh()->situacao)->toBe(ExemplarSituacao::NoAcervo);
});

it('não conta empréstimos devolvidos no limite', function () {
    $leitor = usuario('leitor');

    foreach (range(1, 3) as $vez) {
        emprestimoDevolvido($leitor, acervo(ExemplarSituacao::NoAcervo), CarbonImmutable::now()->subDays(10), CarbonImmutable::now()->subDay());
    }

    realizarEmprestimo($leitor, acervo(ExemplarSituacao::NoAcervo));

    expect(Emprestimo::ativosPorUsuario($leitor->id)->count())->toBe(1)
        ->and(Emprestimo::where('user_id', $leitor->id)->count())->toBe(4);
});

it('recusa quem tem multa pendente e libera depois do pagamento', function () {
    $leitor = usuario('leitor');
    $multa = multaPendente($leitor);
    $exemplar = acervo(ExemplarSituacao::NoAcervo);

    expect(fn () => realizarEmprestimo($leitor, $exemplar))
        ->toThrow(DomainException::class, 'multa pendente');

    expect(Emprestimo::ativosPorUsuario($leitor->id)->count())->toBe(0)
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::NoAcervo);

    $multa->pagar();
    $multa->save();

    realizarEmprestimo($leitor, $exemplar);

    expect(Emprestimo::ativosPorUsuario($leitor->id)->count())->toBe(1)
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::Emprestado);
});

it('recusa usuário bloqueado', function () {
    $leitor = usuario('leitor');
    $leitor->bloquear();
    $leitor->save();

    $exemplar = acervo(ExemplarSituacao::NoAcervo);

    expect(fn () => realizarEmprestimo($leitor, $exemplar))
        ->toThrow(DomainException::class, 'Usuário está bloqueado');

    expect(Emprestimo::count())->toBe(0)
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::NoAcervo);
});

it('não empresta o mesmo exemplar a dois leitores', function () {
    $exemplar = acervo(ExemplarSituacao::NoAcervo);
    $bob = usuario('bob');

    realizarEmprestimo(usuario('ana'), $exemplar);

    expect(fn () => realizarEmprestimo($bob, $exemplar))->toThrow(DomainException::class);

    expect(Emprestimo::where('exemplar_id', $exemplar->id)->count())->toBe(1);
});

it('o banco recusa um segundo empréstimo em aberto do mesmo exemplar mesmo sem passar pelo serviço', function () {
    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $bob = usuario('bob');

    emprestimoEmAndamento(usuario('ana'), $exemplar);

    expect(fn () => emprestimoEmAndamento($bob, $exemplar))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('empresta no balcão pelo código de patrimônio, mesmo digitado em minúsculas com espaços', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');
    $exemplar = acervo(ExemplarSituacao::NoAcervo);

    Livewire::actingAs($bibliotecario)
        ->test(SituacaoDoCliente::class, ['user' => $cliente])
        ->set('codigoPatrimonio', '  '.strtolower((string) $exemplar->codigo_patrimonio).' ')
        ->call('emprestarPorCodigo')
        ->assertHasNoErrors()
        ->assertSet('codigoPatrimonio', '');

    expect(Emprestimo::where('user_id', $cliente->id)->where('exemplar_id', $exemplar->id)->exists())->toBeTrue()
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::Emprestado);
});

it('avisa quando o código de patrimônio não existe', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');

    Livewire::actingAs($bibliotecario)
        ->test(SituacaoDoCliente::class, ['user' => $cliente])
        ->set('codigoPatrimonio', 'EX-999999')
        ->call('emprestarPorCodigo')
        ->assertHasErrors('codigoPatrimonio');

    expect(Emprestimo::count())->toBe(0);
});

it('mostra a recusa do domínio na ficha do cliente', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');
    multaPendente($cliente);
    $exemplar = acervo(ExemplarSituacao::NoAcervo);

    Livewire::actingAs($bibliotecario)
        ->test(SituacaoDoCliente::class, ['user' => $cliente])
        ->set('codigoPatrimonio', (string) $exemplar->codigo_patrimonio)
        ->call('emprestarPorCodigo')
        ->assertHasErrors('dominio');

    expect(Emprestimo::ativosPorUsuario($cliente->id)->count())->toBe(0)
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::NoAcervo);
});

it('empresta pela ficha da obra', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');
    $exemplar = acervo(ExemplarSituacao::NoAcervo);

    Livewire::actingAs($bibliotecario)
        ->test(FichaDaObra::class, ['obra' => $exemplar->obra_id])
        ->set('exemplarId', $exemplar->id)
        ->set('cliente', $cliente->email)
        ->call('emprestar')
        ->assertHasNoErrors()
        ->assertSet('exemplarId', null)
        ->assertSet('cliente', '');

    expect(Emprestimo::where('user_id', $cliente->id)->where('exemplar_id', $exemplar->id)->exists())->toBeTrue()
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::Emprestado);
});

it('recusa na ficha um exemplar de outra obra', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');
    $exemplar = acervo(ExemplarSituacao::NoAcervo);
    $deOutraObra = acervo(ExemplarSituacao::NoAcervo);

    Livewire::actingAs($bibliotecario)
        ->test(FichaDaObra::class, ['obra' => $exemplar->obra_id])
        ->set('exemplarId', $deOutraObra->id)
        ->set('cliente', $cliente->email)
        ->call('emprestar')
        ->assertHasErrors('exemplarId');

    expect(Emprestimo::count())->toBe(0)
        ->and($deOutraObra->refresh()->situacao)->toBe(ExemplarSituacao::NoAcervo);
});

it('recusa na ficha um cliente inexistente', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $exemplar = acervo(ExemplarSituacao::NoAcervo);

    Livewire::actingAs($bibliotecario)
        ->test(FichaDaObra::class, ['obra' => $exemplar->obra_id])
        ->set('exemplarId', $exemplar->id)
        ->set('cliente', 'ninguem@alexandria.test')
        ->call('emprestar')
        ->assertHasErrors('cliente');

    expect(Emprestimo::count())->toBe(0);
});

it('não deixa um leitor registrar empréstimo pela ficha', function () {
    $leitor = usuario('leitor');
    $outro = usuario('outro');
    $exemplar = acervo(ExemplarSituacao::NoAcervo);

    Livewire::actingAs($leitor)
        ->test(FichaDaObra::class, ['obra' => $exemplar->obra_id])
        ->set('exemplarId', $exemplar->id)
        ->set('cliente', $outro->email)
        ->call('emprestar')
        ->assertForbidden();

    expect(Emprestimo::count())->toBe(0)
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::NoAcervo);
});

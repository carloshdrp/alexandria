<?php

use App\Acesso\Domain\Enums\UsuarioPapel;
use Emprestimos\Application\Livewire\FichaDaObra;
use Emprestimos\Domain\Enums\ReservaSituacao;
use Emprestimos\Domain\Models\Reserva;
use Inventario\Domain\Enums\ExemplarSituacao;
use Livewire\Livewire;

it('deixa o bibliotecário reservar em nome de um cliente quando não há exemplar disponível', function () {
    $bibliotecario = usuario('bibliotecario', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');
    $exemplar = acervo(ExemplarSituacao::Emprestado);

    Livewire::actingAs($bibliotecario)
        ->test(FichaDaObra::class, ['obra' => $exemplar->obra_id])
        ->set('cliente', $cliente->email)
        ->call('reservarParaCliente')
        ->assertHasNoErrors();

    $reserva = Reserva::where('user_id', $cliente->id)->first();

    expect($reserva)->not->toBeNull()
        ->and($reserva->obra_id)->toBe($exemplar->obra_id)
        ->and($reserva->situacao)->toBe(ReservaSituacao::Aguardando);
});

it('localiza o cliente pelo CPF com máscara', function () {
    $bibliotecario = usuario('bibliotecario', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');
    $exemplar = acervo(ExemplarSituacao::Emprestado);

    Livewire::actingAs($bibliotecario)
        ->test(FichaDaObra::class, ['obra' => $exemplar->obra_id])
        ->set('cliente', $cliente->documento->formatado())
        ->call('reservarParaCliente')
        ->assertHasNoErrors();

    expect(Reserva::where('user_id', $cliente->id)->exists())->toBeTrue();
});

it('recusa reservar no balcão quando existe exemplar disponível', function () {
    $bibliotecario = usuario('bibliotecario', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');
    $exemplar = acervo(ExemplarSituacao::NoAcervo);

    Livewire::actingAs($bibliotecario)
        ->test(FichaDaObra::class, ['obra' => $exemplar->obra_id])
        ->set('cliente', $cliente->email)
        ->call('reservarParaCliente')
        ->assertHasErrors('dominio');

    expect(Reserva::where('user_id', $cliente->id)->exists())->toBeFalse();
});

it('recusa uma segunda reserva do mesmo cliente para a mesma obra', function () {
    $bibliotecario = usuario('bibliotecario', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');
    $exemplar = acervo(ExemplarSituacao::Emprestado);

    reservaAguardando($cliente, $exemplar);

    Livewire::actingAs($bibliotecario)
        ->test(FichaDaObra::class, ['obra' => $exemplar->obra_id])
        ->set('cliente', $cliente->email)
        ->call('reservarParaCliente')
        ->assertHasErrors('dominio');

    expect(Reserva::where('user_id', $cliente->id)->count())->toBe(1);
});

it('recusa reservar para um cliente inexistente', function () {
    $bibliotecario = usuario('bibliotecario', UsuarioPapel::Bibliotecario);
    $exemplar = acervo(ExemplarSituacao::Emprestado);

    Livewire::actingAs($bibliotecario)
        ->test(FichaDaObra::class, ['obra' => $exemplar->obra_id])
        ->set('cliente', 'ninguem@alexandria.test')
        ->call('reservarParaCliente')
        ->assertHasErrors('cliente');
});

it('não deixa um leitor reservar em nome de outra pessoa', function () {
    $leitor = usuario('leitor');
    $outro = usuario('outro');
    $exemplar = acervo(ExemplarSituacao::Emprestado);

    Livewire::actingAs($leitor)
        ->test(FichaDaObra::class, ['obra' => $exemplar->obra_id])
        ->set('cliente', $outro->email)
        ->call('reservarParaCliente')
        ->assertForbidden();

    expect(Reserva::where('user_id', $outro->id)->exists())->toBeFalse();
});

it('recusa reservar uma obra que já está emprestada ao próprio cliente', function () {
    $bibliotecario = usuario('bibliotecario', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');
    $exemplar = acervo(ExemplarSituacao::Emprestado);

    emprestimoEmAndamento($cliente, $exemplar);

    Livewire::actingAs($bibliotecario)
        ->test(FichaDaObra::class, ['obra' => $exemplar->obra_id])
        ->set('cliente', $cliente->email)
        ->call('reservarParaCliente')
        ->assertHasErrors('dominio');

    expect(Reserva::where('user_id', $cliente->id)->exists())->toBeFalse();
});

it('não oferece reserva ao leitor que já está com um exemplar da obra', function () {
    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::Emprestado);

    emprestimoEmAndamento($leitor, $exemplar);

    Livewire::actingAs($leitor)
        ->test(FichaDaObra::class, ['obra' => $exemplar->obra_id])
        ->assertViewHas('jaEmprestado', true)
        ->call('reservar')
        ->assertHasErrors('dominio');

    expect(Reserva::where('user_id', $leitor->id)->exists())->toBeFalse();
});

it('deixa reservar outra obra mesmo com um empréstimo em andamento', function () {
    $leitor = usuario('leitor');
    $emprestado = acervo(ExemplarSituacao::Emprestado);
    $outra = acervo(ExemplarSituacao::Emprestado);

    emprestimoEmAndamento($leitor, $emprestado);

    Livewire::actingAs($leitor)
        ->test(FichaDaObra::class, ['obra' => $outra->obra_id])
        ->call('reservar')
        ->assertHasNoErrors();

    expect(Reserva::where('user_id', $leitor->id)->where('obra_id', $outra->obra_id)->exists())->toBeTrue();
});

it('deixa reservar de novo depois de devolver o exemplar', function () {
    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::Emprestado);

    $emprestimo = emprestimoEmAndamento($leitor, $exemplar);
    $emprestimo->devolver();
    $emprestimo->save();

    Livewire::actingAs($leitor)
        ->test(FichaDaObra::class, ['obra' => $exemplar->obra_id])
        ->call('reservar')
        ->assertHasNoErrors();

    expect(Reserva::where('user_id', $leitor->id)->exists())->toBeTrue();
});

it('recusa reservar para cliente bloqueado', function () {
    $bibliotecario = usuario('bibliotecario', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');
    $cliente->bloquear();
    $cliente->save();

    $exemplar = acervo(ExemplarSituacao::Emprestado);

    Livewire::actingAs($bibliotecario)
        ->test(FichaDaObra::class, ['obra' => $exemplar->obra_id])
        ->set('cliente', $cliente->email)
        ->call('reservarParaCliente')
        ->assertHasErrors('dominio');
});

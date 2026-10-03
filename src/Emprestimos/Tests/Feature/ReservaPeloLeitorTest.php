<?php

use Acesso\Domain\Enums\UsuarioPapel;
use Emprestimos\Application\Livewire\Catalogo;
use Emprestimos\Application\Livewire\FichaDaObra;
use Emprestimos\Domain\Enums\ReservaSituacao;
use Emprestimos\Domain\Events\ReservaFoiCadastrada;
use Emprestimos\Domain\Models\Reserva;
use Emprestimos\Domain\Services\ReservaObraService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Event;
use Inventario\Domain\Enums\ExemplarSituacao;
use Inventario\Domain\Models\Obra;
use Inventario\Domain\Services\AcervoService;
use Livewire\Livewire;

it('reserva a obra quando todos os exemplares estão emprestados', function () {
    Event::fake([ReservaFoiCadastrada::class]);

    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::Emprestado);

    Livewire::actingAs($leitor)
        ->test(FichaDaObra::class, ['obra' => $exemplar->obra_id])
        ->assertViewHas('jaReservou', false)
        ->assertViewHas('tamanhoDaFila', 0)
        ->call('reservar')
        ->assertHasNoErrors()
        ->assertViewHas('jaReservou', true)
        ->assertViewHas('tamanhoDaFila', 1);

    $reserva = Reserva::where('user_id', $leitor->id)->first();

    expect($reserva)->not->toBeNull()
        ->and($reserva->situacao)->toBe(ReservaSituacao::Aguardando)
        ->and($reserva->obra_id)->toBe($exemplar->obra_id)
        ->and($reserva->exemplar_id)->toBeNull()
        ->and($reserva->janela)->toBeNull();

    Event::assertDispatched(ReservaFoiCadastrada::class, fn (ReservaFoiCadastrada $evento) => $evento->reserva->is($reserva));
});

it('não reserva quando há exemplar disponível para retirar no balcão', function () {
    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::NoAcervo);

    Livewire::actingAs($leitor)
        ->test(FichaDaObra::class, ['obra' => $exemplar->obra_id])
        ->call('reservar')
        ->assertHasErrors('dominio');

    expect(Reserva::count())->toBe(0);
});

it('exemplar baixado não conta como disponível para a reserva', function () {
    $leitor = usuario('leitor');
    $emprestado = acervo(ExemplarSituacao::Emprestado);
    exemplarIrmao($emprestado, ExemplarSituacao::Baixado);

    $obra = obraPublicada($emprestado);

    expect($obra->exemplaresTotal)->toBe(1)
        ->and($obra->exemplaresDisponiveis)->toBe(0)
        ->and($obra->temExemplarDisponivel())->toBeFalse();

    Livewire::actingAs($leitor)
        ->test(FichaDaObra::class, ['obra' => $emprestado->obra_id])
        ->call('reservar')
        ->assertHasNoErrors();

    expect(Reserva::where('user_id', $leitor->id)->where('obra_id', $emprestado->obra_id)->exists())->toBeTrue();
});

it('recusa reservar obra sem exemplar ativo', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $leitor = usuario('leitor');
    $cliente = usuario('cliente');

    $soBaixado = acervo(ExemplarSituacao::Baixado);
    $modelo = Obra::findOrFail($soBaixado->obra_id);
    $semExemplar = Obra::forceCreate([
        'titulo' => 'Obra sem exemplar',
        'editora_id' => $modelo->editora_id,
        'categoria_id' => $modelo->categoria_id,
        'user_id' => $modelo->user_id,
    ]);

    foreach ([$soBaixado->obra_id, $semExemplar->id] as $obraId) {
        $obra = app(AcervoService::class)->obra($obraId);

        expect(fn () => app(ReservaObraService::class)->reservar($leitor, $obra))
            ->toThrow(DomainException::class, 'não possui exemplares ativos');

        Livewire::actingAs($leitor)
            ->test(FichaDaObra::class, ['obra' => $obraId])
            ->assertSee('Sem exemplares no acervo')
            ->call('reservar')
            ->assertHasErrors('dominio');

        Livewire::actingAs($bibliotecario)
            ->test(FichaDaObra::class, ['obra' => $obraId])
            ->assertSee('Sem exemplares no acervo')
            ->assertDontSee('Reservar para um cliente')
            ->assertDontSee('Emprestar no balcão')
            ->set('cliente', $cliente->email)
            ->call('reservarParaCliente')
            ->assertHasErrors('dominio');
    }

    expect(Reserva::count())->toBe(0);
});

it('o catálogo acha a obra pelo título e pelo autor', function () {
    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::NoAcervo);

    $obra = Obra::findOrFail($exemplar->obra_id);
    $obra->titulo = 'Dom Casmurro';
    $obra->save();
    $obra->definirAutores([autor('Machado de Assis')->id], $obra->user_id);

    acervo(ExemplarSituacao::NoAcervo);

    Livewire::actingAs($leitor)
        ->test(Catalogo::class)
        ->assertViewHas('obras', fn (LengthAwarePaginator $obras) => $obras->total() === 2)
        ->set('busca', 'casmurro')
        ->assertViewHas('obras', fn (LengthAwarePaginator $obras) => $obras->pluck('id')->all() === [$obra->id])
        ->set('busca', 'machado')
        ->assertViewHas('obras', fn (LengthAwarePaginator $obras) => $obras->pluck('id')->all() === [$obra->id])
        ->set('busca', 'inexistente')
        ->assertViewHas('obras', fn (LengthAwarePaginator $obras) => $obras->isEmpty());
});

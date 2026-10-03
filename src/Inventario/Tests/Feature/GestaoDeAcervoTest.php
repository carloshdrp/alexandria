<?php

use Acesso\Domain\Enums\UsuarioPapel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Inventario\Application\Livewire\Autores;
use Inventario\Application\Livewire\Categorias;
use Inventario\Application\Livewire\Editoras;
use Inventario\Application\Livewire\ExemplaresDaObra;
use Inventario\Application\Livewire\FormularioObra;
use Inventario\Application\Livewire\Obras;
use Inventario\Domain\Enums\ExemplarEstadoConservacao;
use Inventario\Domain\Enums\ExemplarMotivoBaixa;
use Inventario\Domain\Enums\ExemplarSituacao;
use Inventario\Domain\Events\ExemplarFoiAtualizado;
use Inventario\Domain\Events\ExemplarFoiCadastrado;
use Inventario\Domain\Events\Integracao\ExemplarFoiBaixado;
use Inventario\Domain\Events\ObraFoiAtualizada;
use Inventario\Domain\Events\ObraFoiCadastrada;
use Inventario\Domain\Events\ObraFoiRemovida;
use Inventario\Domain\Models\Categoria;
use Inventario\Domain\Models\Editora;
use Inventario\Domain\Models\Exemplar;
use Inventario\Domain\Models\Obra;
use Inventario\Domain\ValueObjects\Isbn;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function dadosDeObra(array $sobrescrever = []): array
{
    static $sequencia = 0;

    $sequencia++;

    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);

    return array_merge([
        'titulo' => 'Dom Casmurro',
        'isbn' => '978-0-306-40615-7',
        'editora_id' => (string) Editora::forceCreate(['nome' => "Casa Editorial {$sequencia}", 'user_id' => $bibliotecario->id])->id,
        'categoria_id' => (string) Categoria::forceCreate(['nome' => "Assunto {$sequencia}", 'user_id' => $bibliotecario->id])->id,
        'ano_publicacao' => '1899',
        'autores' => [autor("Autor {$sequencia}")->id],
    ], $sobrescrever);
}

function preencher(Testable $componente, array $dados): Testable
{
    foreach ($dados as $campo => $valor) {
        $componente = $componente->set($campo, $valor);
    }

    return $componente;
}

it('cadastra uma obra com autores na ordem informada', function () {
    Event::fake([ObraFoiCadastrada::class]);

    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $capistrano = autor('Capistrano de Abreu');
    $machado = autor('Machado de Assis');
    $dados = dadosDeObra(['autores' => [$capistrano->id, $machado->id]]);

    $formulario = preencher(Livewire::actingAs($bibliotecario)->test(FormularioObra::class), $dados)
        ->call('salvar')
        ->assertHasNoErrors();

    $obra = Obra::where('titulo', 'Dom Casmurro')->first();

    expect($obra)->not->toBeNull()
        ->and($obra->isbn?->getNativeValue())->toBe('9780306406157')
        ->and($obra->ano_publicacao)->toBe(1899)
        ->and($obra->editora_id)->toBe((int) $dados['editora_id'])
        ->and($obra->categoria_id)->toBe((int) $dados['categoria_id'])
        ->and($obra->user_id)->toBe($bibliotecario->id)
        ->and($obra->capa_path)->toBeNull()
        ->and($obra->autores->pluck('id')->all())->toBe([$capistrano->id, $machado->id])
        ->and($obra->autores->pluck('pivot.ordem')->all())->toBe([1, 2]);

    $formulario->assertRedirect(route('inventario.obras.exemplares', $obra));

    Event::assertDispatched(ObraFoiCadastrada::class, fn (ObraFoiCadastrada $evento) => $evento->obra->is($obra));
});

it('guarda a capa enviada', function () {
    Storage::fake('public');

    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);

    preencher(Livewire::actingAs($bibliotecario)->test(FormularioObra::class), dadosDeObra())
        ->set('capa', UploadedFile::fake()->image('capa.jpg'))
        ->call('salvar')
        ->assertHasNoErrors();

    $obra = Obra::where('titulo', 'Dom Casmurro')->firstOrFail();

    expect($obra->capa_path)->toStartWith("capas/obras/{$obra->id}/");

    Storage::disk('public')->assertExists($obra->capa_path);
});

it('recusa obra inválida', function (array $campos, string $erro) {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);

    preencher(Livewire::actingAs($bibliotecario)->test(FormularioObra::class), dadosDeObra($campos))
        ->call('salvar')
        ->assertHasErrors($erro);

    expect(Obra::count())->toBe(0);
})->with([
    'sem título' => [['titulo' => ''], 'titulo'],
    'ISBN inválido' => [['isbn' => '978-0-306-40615-8'], 'isbn'],
    'sem autores' => [['autores' => []], 'autores'],
    'editora inexistente' => [['editora_id' => '999999'], 'editora_id'],
    'ano no futuro' => [['ano_publicacao' => (string) ((int) date('Y') + 1)], 'ano_publicacao'],
]);

it('recusa autor repetido na mesma obra', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $machado = autor('Machado de Assis');

    preencher(Livewire::actingAs($bibliotecario)->test(FormularioObra::class), dadosDeObra(['autores' => [$machado->id, $machado->id]]))
        ->call('salvar')
        ->assertHasErrors('autores.1');

    expect(Obra::count())->toBe(0);
});

it('não repete ISBN entre obras', function (string $isbnRepetido) {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);

    preencher(Livewire::actingAs($bibliotecario)->test(FormularioObra::class), dadosDeObra(['isbn' => '9780306406157']))
        ->call('salvar')
        ->assertHasNoErrors();

    preencher(Livewire::actingAs($bibliotecario)->test(FormularioObra::class), dadosDeObra(['titulo' => 'Outra obra', 'isbn' => $isbnRepetido]))
        ->call('salvar')
        ->assertHasErrors('isbn');

    expect(Obra::count())->toBe(1);
})->with([
    'digitado igual' => '9780306406157',
    'digitado com hífens' => '978-0-306-40615-7',
]);

it('edita a obra trocando autores e substituindo a capa', function () {
    Storage::fake('public');
    Event::fake([ObraFoiAtualizada::class]);

    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $dados = dadosDeObra();
    $primeiro = autor('Primeiro Autor');
    $segundo = autor('Segundo Autor');
    $terceiro = autor('Terceiro Autor');

    $obra = Obra::forceCreate([
        'titulo' => 'Título Antigo',
        'isbn' => Isbn::fromNative('978-0-306-40615-7'),
        'editora_id' => $dados['editora_id'],
        'categoria_id' => $dados['categoria_id'],
        'user_id' => $bibliotecario->id,
    ]);
    $obra->definirAutores([$primeiro->id, $segundo->id], $bibliotecario->id);

    $capaAntiga = "capas/obras/{$obra->id}/antiga.jpg";
    Storage::disk('public')->put($capaAntiga, 'antiga');
    $obra->capa_path = $capaAntiga;
    $obra->save();

    Livewire::actingAs($bibliotecario)
        ->test(FormularioObra::class, ['obra' => $obra])
        ->assertSet('titulo', 'Título Antigo')
        ->assertSet('isbn', '9780306406157')
        ->assertSet('autores', [$primeiro->id, $segundo->id])
        ->set('titulo', 'Título Novo')
        ->set('autores', [$terceiro->id])
        ->set('capa', UploadedFile::fake()->image('nova.png'))
        ->call('salvar')
        ->assertHasNoErrors();

    $obra->refresh();

    expect($obra->titulo)->toBe('Título Novo')
        ->and($obra->isbn?->getNativeValue())->toBe('9780306406157')
        ->and($obra->autores->pluck('id')->all())->toBe([$terceiro->id])
        ->and($obra->capa_path)->not->toBe($capaAntiga)
        ->and($obra->capa_path)->toStartWith("capas/obras/{$obra->id}/");

    Storage::disk('public')->assertExists($obra->capa_path);
    Storage::disk('public')->assertMissing($capaAntiga);

    Event::assertDispatched(ObraFoiAtualizada::class, fn (ObraFoiAtualizada $evento) => $evento->obra->is($obra));
});

it('remove a obra sem apagar a linha', function () {
    Event::fake([ObraFoiRemovida::class]);

    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $obraId = acervo(ExemplarSituacao::Baixado)->obra_id;

    Livewire::actingAs($bibliotecario)
        ->test(Obras::class)
        ->call('remover', $obraId)
        ->assertHasNoErrors();

    expect(Obra::find($obraId))->toBeNull()
        ->and(Obra::withTrashed()->find($obraId)?->deleted_at)->not->toBeNull();

    Event::assertDispatched(ObraFoiRemovida::class, fn (ObraFoiRemovida $evento) => $evento->obra->id === $obraId);
});

it('não remove obra com exemplar que ainda não foi baixado', function (ExemplarSituacao $situacao) {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $exemplar = acervo($situacao);

    Livewire::actingAs($bibliotecario)
        ->test(Obras::class)
        ->call('remover', $exemplar->obra_id)
        ->assertHasErrors('dominio');

    expect(Obra::find($exemplar->obra_id))->not->toBeNull();
})->with([
    'no acervo' => ExemplarSituacao::NoAcervo,
    'emprestado' => ExemplarSituacao::Emprestado,
    'reservado' => ExemplarSituacao::Reservado,
]);

it('cadastra um exemplar no acervo', function () {
    Event::fake([ExemplarFoiCadastrado::class]);

    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $obra = Obra::findOrFail(acervo(ExemplarSituacao::NoAcervo)->obra_id);

    Livewire::actingAs($bibliotecario)
        ->test(ExemplaresDaObra::class, ['obra' => $obra])
        ->set('codigo_patrimonio', 'ex-555555')
        ->set('estado_conservacao', (string) ExemplarEstadoConservacao::Novo->value)
        ->call('cadastrar')
        ->assertHasNoErrors()
        ->assertSet('codigo_patrimonio', '')
        ->assertSet('estado_conservacao', '');

    $exemplar = Exemplar::where('codigo_patrimonio', 'EX-555555')->first();

    expect($exemplar)->not->toBeNull()
        ->and($exemplar->obra_id)->toBe($obra->id)
        ->and($exemplar->situacao)->toBe(ExemplarSituacao::NoAcervo)
        ->and($exemplar->estado_conservacao)->toBe(ExemplarEstadoConservacao::Novo)
        ->and($exemplar->user_id)->toBe($bibliotecario->id)
        ->and($exemplar->baixado_em)->toBeNull();

    Event::assertDispatched(ExemplarFoiCadastrado::class, fn (ExemplarFoiCadastrado $evento) => $evento->exemplar->is($exemplar));
});

it('recusa exemplar inválido', function (string $codigo, string $estado, string $erro) {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $obra = Obra::findOrFail(acervo(ExemplarSituacao::NoAcervo)->obra_id);

    Livewire::actingAs($bibliotecario)
        ->test(ExemplaresDaObra::class, ['obra' => $obra])
        ->set('codigo_patrimonio', $codigo)
        ->set('estado_conservacao', $estado)
        ->call('cadastrar')
        ->assertHasErrors($erro);

    expect(Exemplar::count())->toBe(1);
})->with([
    'código fora do padrão' => ['EX-1', '2', 'codigo_patrimonio'],
    'estado inválido' => ['EX-555555', '9', 'estado_conservacao'],
]);

it('não repete código de patrimônio', function (bool $emMinusculas) {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $existente = acervo(ExemplarSituacao::NoAcervo);
    $obra = Obra::findOrFail(acervo(ExemplarSituacao::NoAcervo)->obra_id);
    $codigo = (string) $existente->codigo_patrimonio;

    Livewire::actingAs($bibliotecario)
        ->test(ExemplaresDaObra::class, ['obra' => $obra])
        ->set('codigo_patrimonio', $emMinusculas ? strtolower($codigo) : $codigo)
        ->set('estado_conservacao', (string) ExemplarEstadoConservacao::Bom->value)
        ->call('cadastrar')
        ->assertHasErrors('codigo_patrimonio');

    expect(Exemplar::count())->toBe(2);
})->with([
    'digitado igual' => false,
    'digitado em minúsculas' => true,
]);

it('altera o estado de conservação', function () {
    Event::fake([ExemplarFoiAtualizado::class]);

    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $exemplar = acervo(ExemplarSituacao::NoAcervo);

    Livewire::actingAs($bibliotecario)
        ->test(ExemplaresDaObra::class, ['obra' => Obra::findOrFail($exemplar->obra_id)])
        ->call('alterarConservacao', $exemplar->id, ExemplarEstadoConservacao::Ruim->value)
        ->assertHasNoErrors();

    expect($exemplar->refresh()->estado_conservacao)->toBe(ExemplarEstadoConservacao::Ruim);

    Event::assertDispatched(ExemplarFoiAtualizado::class, fn (ExemplarFoiAtualizado $evento) => $evento->exemplar->is($exemplar));
});

it('não edita exemplar baixado', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $exemplar = acervo(ExemplarSituacao::Baixado);

    Livewire::actingAs($bibliotecario)
        ->test(ExemplaresDaObra::class, ['obra' => Obra::findOrFail($exemplar->obra_id)])
        ->call('alterarConservacao', $exemplar->id, ExemplarEstadoConservacao::Ruim->value)
        ->assertHasErrors('dominio');

    expect($exemplar->refresh()->estado_conservacao)->toBe(ExemplarEstadoConservacao::Bom);
});

it('dá baixa por perda e tira o exemplar do catálogo', function () {
    Event::fake([ExemplarFoiBaixado::class]);
    $this->travelTo(CarbonImmutable::parse('2026-03-01 10:00:00'));

    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $exemplar = acervo(ExemplarSituacao::NoAcervo);

    expect(obraPublicada($exemplar)->exemplaresTotal)->toBe(1);

    Livewire::actingAs($bibliotecario)
        ->test(ExemplaresDaObra::class, ['obra' => Obra::findOrFail($exemplar->obra_id)])
        ->call('baixar', $exemplar->id, ExemplarMotivoBaixa::Perdido->value)
        ->assertHasNoErrors();

    $exemplar->refresh();

    expect($exemplar->situacao)->toBe(ExemplarSituacao::Baixado)
        ->and($exemplar->motivo_baixa)->toBe(ExemplarMotivoBaixa::Perdido)
        ->and($exemplar->baixado_em?->format('Y-m-d H:i'))->toBe('2026-03-01 10:00')
        ->and(obraPublicada($exemplar)->exemplaresTotal)->toBe(0)
        ->and(obraPublicada($exemplar)->exemplaresDisponiveis)->toBe(0);

    Event::assertDispatched(
        ExemplarFoiBaixado::class,
        fn (ExemplarFoiBaixado $evento) => $evento->exemplarId === $exemplar->id
            && $evento->obraId === $exemplar->obra_id,
    );
});

it('não baixa duas vezes', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $exemplar = acervo(ExemplarSituacao::Baixado);

    Livewire::actingAs($bibliotecario)
        ->test(ExemplaresDaObra::class, ['obra' => Obra::findOrFail($exemplar->obra_id)])
        ->call('baixar', $exemplar->id, ExemplarMotivoBaixa::Danificado->value)
        ->assertHasErrors('dominio');

    expect($exemplar->refresh()->motivo_baixa)->toBeNull();
});

it('só mexe em exemplares da própria obra', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $exemplar = acervo(ExemplarSituacao::NoAcervo);
    $deOutraObra = acervo(ExemplarSituacao::NoAcervo);

    expect(fn () => Livewire::actingAs($bibliotecario)
        ->test(ExemplaresDaObra::class, ['obra' => Obra::findOrFail($exemplar->obra_id)])
        ->call('baixar', $deOutraObra->id, ExemplarMotivoBaixa::Perdido->value))
        ->toThrow(ModelNotFoundException::class);

    expect($deOutraObra->refresh()->situacao)->toBe(ExemplarSituacao::NoAcervo);
});

it('cadastra referências e recusa nome repetido', function (string $componente, string $tabela) {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);

    Livewire::actingAs($bibliotecario)
        ->test($componente)
        ->set('nome', 'Nome Único')
        ->call('cadastrar')
        ->assertHasNoErrors()
        ->assertSet('nome', '');

    expect(DB::table($tabela)->where('nome', 'Nome Único')->count())->toBe(1);

    Livewire::actingAs($bibliotecario)
        ->test($componente)
        ->set('nome', 'Nome Único')
        ->call('cadastrar')
        ->assertHasErrors('nome');

    expect(DB::table($tabela)->where('nome', 'Nome Único')->count())->toBe(1);
})->with([
    'autores' => [Autores::class, 'autores'],
    'editoras' => [Editoras::class, 'editoras'],
    'categorias' => [Categorias::class, 'categorias'],
]);

it('o inventário é só do bibliotecário', function () {
    $this->get(route('inventario.obras'))->assertRedirect('/login');

    $this->actingAs(usuario('leitor'))->get(route('inventario.obras'))->assertForbidden();

    $this->actingAs(usuario('bib', UsuarioPapel::Bibliotecario))->get(route('inventario.obras'))->assertOk();
});

<?php

use Acesso\Domain\Enums\UsuarioPapel;
use Inventario\Domain\Enums\ExemplarEstadoConservacao;
use Inventario\Domain\Enums\ExemplarSituacao;
use Inventario\Domain\Models\Autor;
use Inventario\Domain\Models\Categoria;
use Inventario\Domain\Models\Editora;
use Inventario\Domain\Models\Exemplar;
use Inventario\Domain\Models\Obra;
use Inventario\Domain\Services\AcervoService;
use Inventario\Domain\ValueObjects\CodigoPatrimonio;
use Inventario\Domain\ValueObjects\ExemplarDoAcervo;
use Inventario\Domain\ValueObjects\ObraDoAcervo;

function acervo(ExemplarSituacao $situacao): Exemplar
{
    static $sequencia = 0;

    $sequencia++;

    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);

    $obra = Obra::forceCreate([
        'titulo' => "Obra {$sequencia}",
        'editora_id' => Editora::forceCreate(['nome' => "Editora {$sequencia}", 'user_id' => $bibliotecario->id])->id,
        'categoria_id' => Categoria::forceCreate(['nome' => "Categoria {$sequencia}", 'user_id' => $bibliotecario->id])->id,
        'user_id' => $bibliotecario->id,
    ]);

    return Exemplar::forceCreate([
        'obra_id' => $obra->id,
        'codigo_patrimonio' => CodigoPatrimonio::fromNative('EX-'.str_pad((string) $sequencia, 6, '0', STR_PAD_LEFT)),
        'estado_conservacao' => ExemplarEstadoConservacao::Bom,
        'situacao' => $situacao,
        'user_id' => $bibliotecario->id,
    ]);
}

function exemplarIrmao(Exemplar $referencia, ExemplarSituacao $situacao): Exemplar
{
    static $sequencia = 0;

    $sequencia++;

    return Exemplar::forceCreate([
        'obra_id' => $referencia->obra_id,
        'codigo_patrimonio' => CodigoPatrimonio::fromNative('EX-9'.str_pad((string) $sequencia, 5, '0', STR_PAD_LEFT)),
        'estado_conservacao' => ExemplarEstadoConservacao::Bom,
        'situacao' => $situacao,
        'user_id' => $referencia->user_id,
    ]);
}

function autor(string $nome): Autor
{
    return Autor::forceCreate(['nome' => $nome, 'user_id' => usuario('bib', UsuarioPapel::Bibliotecario)->id]);
}

function exemplarPublicado(Exemplar $exemplar): ExemplarDoAcervo
{
    return app(AcervoService::class)->exemplar($exemplar->id);
}

function obraPublicada(Exemplar $exemplar): ObraDoAcervo
{
    return app(AcervoService::class)->obra($exemplar->obra_id);
}

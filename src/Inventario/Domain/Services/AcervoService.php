<?php

namespace Inventario\Domain\Services;

use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Inventario\Domain\Enums\ExemplarSituacao;
use Inventario\Domain\Models\Autor;
use Inventario\Domain\Models\Exemplar;
use Inventario\Domain\Models\Obra;
use Inventario\Domain\ValueObjects\ExemplarDoAcervo;
use Inventario\Domain\ValueObjects\ObraDoAcervo;

class AcervoService
{
    public function exemplar(int $exemplarId): ?ExemplarDoAcervo
    {
        $exemplar = Exemplar::with('obra')->find($exemplarId);

        return $exemplar === null ? null : $this->publicarExemplar($exemplar);
    }

    private function publicarExemplar(Exemplar $exemplar): ExemplarDoAcervo
    {
        return new ExemplarDoAcervo(
            id: $exemplar->id,
            obraId: $exemplar->obra_id,
            codigoPatrimonio: (string) $exemplar->codigo_patrimonio,
            disponivel: $exemplar->situacao === ExemplarSituacao::NoAcervo,
            reservado: $exemplar->situacao === ExemplarSituacao::Reservado,
            tituloObra: $exemplar->obra->titulo,
            situacao: $exemplar->situacao->rotulo(),
            estadoConservacao: $exemplar->estado_conservacao->rotulo(),
        );
    }

    public function exemplarPorCodigo(string $codigoPatrimonio): ?ExemplarDoAcervo
    {
        $exemplar = Exemplar::with('obra')
            ->where('codigo_patrimonio', mb_strtoupper(trim($codigoPatrimonio)))
            ->first();

        return $exemplar === null ? null : $this->publicarExemplar($exemplar);
    }

    /**
     * @param  array<int, int>  $exemplarIds
     * @return Collection<int, ExemplarDoAcervo>
     */
    public function exemplares(array $exemplarIds): Collection
    {
        return Exemplar::with('obra')
            ->whereIn('id', $exemplarIds)
            ->get()
            ->mapWithKeys(fn (Exemplar $exemplar) => [$exemplar->id => $this->publicarExemplar($exemplar)]);
    }

    /**
     * @return Collection<int, ExemplarDoAcervo>
     */
    public function exemplaresDaObra(int $obraId): Collection
    {
        return Exemplar::with('obra')
            ->where('obra_id', $obraId)
            ->orderBy('codigo_patrimonio')
            ->get()
            ->map(fn (Exemplar $exemplar) => $this->publicarExemplar($exemplar));
    }

    /**
     * @return LengthAwarePaginator<int, ObraDoAcervo>
     */
    public function catalogo(?string $busca, int $porPagina = 12): LengthAwarePaginator
    {
        $consulta = $this->consultaDeCatalogo()->orderBy('titulo');

        if ($busca !== null && trim($busca) !== '') {
            $termo = '%'.trim($busca).'%';

            $consulta->where(function (Builder $query) use ($termo) {
                $query->where('titulo', 'ilike', $termo)
                    ->orWhereHas('autores', fn (Builder $autores) => $autores->where('nome', 'ilike', $termo));
            });
        }

        return $consulta->paginate($porPagina)->through(fn (Obra $obra) => $this->publicarObra($obra));
    }

    /**
     * @return Builder<Obra>
     */
    private function consultaDeCatalogo(): Builder
    {
        return Obra::query()
            ->with(['autores', 'editora', 'categoria'])
            ->withCount([
                'exemplares as exemplares_total' => fn (Builder $query) => $query->where('situacao', '!=', ExemplarSituacao::Baixado),
                'exemplares as exemplares_disponiveis' => fn (Builder $query) => $query->where('situacao', ExemplarSituacao::NoAcervo),
            ]);
    }

    private function publicarObra(Obra $obra): ObraDoAcervo
    {
        return new ObraDoAcervo(
            id: $obra->id,
            titulo: $obra->titulo,
            autores: array_values($obra->autores->map(fn (Autor $autor) => $autor->nome)->all()),
            editora: $obra->editora->nome,
            categoria: $obra->categoria->nome,
            anoPublicacao: $obra->ano_publicacao,
            isbn: $obra->isbn?->getNativeValue(),
            capaUrl: $obra->capa_url,
            capaMiniaturaUrl: $obra->capa_miniatura_url,
            exemplaresDisponiveis: (int) $obra->exemplares_disponiveis,
            exemplaresTotal: (int) $obra->exemplares_total,
        );
    }

    public function obraDoExemplar(int $exemplarId): ?ObraDoAcervo
    {
        $exemplar = Exemplar::find($exemplarId);

        return $exemplar === null ? null : $this->obra($exemplar->obra_id);
    }

    public function obra(int $obraId): ?ObraDoAcervo
    {
        $obra = $this->consultaDeCatalogo()->find($obraId);

        return $obra === null ? null : $this->publicarObra($obra);
    }

    public function exemplarReservado(int $obraId): ?ExemplarDoAcervo
    {
        $exemplar = Exemplar::with('obra')
            ->where('obra_id', $obraId)
            ->where('situacao', ExemplarSituacao::Reservado)
            ->first();

        return $exemplar === null ? null : $this->publicarExemplar($exemplar);
    }

    /**
     * @return Collection<int, ExemplarDoAcervo>
     */
    public function exemplaresReservados(int $obraId): Collection
    {
        return Exemplar::with('obra')
            ->where('obra_id', $obraId)
            ->where('situacao', ExemplarSituacao::Reservado)
            ->get()
            ->map(fn (Exemplar $exemplar) => $this->publicarExemplar($exemplar));
    }

    /**
     * @return Collection<int, int>
     */
    public function obrasComExemplarReservado(): Collection
    {
        return Exemplar::query()
            ->where('situacao', ExemplarSituacao::Reservado)
            ->distinct()
            ->pluck('obra_id');
    }

    public function existeExemplarDisponivel(int $obraId): bool
    {
        return Exemplar::disponivelPorObra($obraId)->exists();
    }

    public function marcarEmprestado(int $exemplarId): void
    {
        $this->transicionar($exemplarId, fn (Exemplar $exemplar) => $exemplar->emprestar());
    }

    private function transicionar(int $exemplarId, callable $transicao): void
    {
        $exemplar = Exemplar::find($exemplarId);

        if ($exemplar === null) {
            throw new DomainException("Exemplar $exemplarId não existe no acervo.");
        }

        $transicao($exemplar);
        $exemplar->save();
    }

    public function marcarEmprestadoParaReserva(int $exemplarId): void
    {
        $this->transicionar($exemplarId, fn (Exemplar $exemplar) => $exemplar->emprestarParaReserva());
    }

    public function marcarDevolvido(int $exemplarId): void
    {
        $this->transicionar($exemplarId, fn (Exemplar $exemplar) => $exemplar->devolver());
    }

    public function marcarReservado(int $exemplarId): void
    {
        $this->transicionar($exemplarId, fn (Exemplar $exemplar) => $exemplar->reservar());
    }

    public function liberarReserva(int $exemplarId): void
    {
        $this->transicionar($exemplarId, fn (Exemplar $exemplar) => $exemplar->liberarReserva());
    }
}

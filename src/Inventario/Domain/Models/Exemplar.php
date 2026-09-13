<?php

namespace Inventario\Domain\Models;

use App\Casts\ValueObjectCast;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Inventario\Domain\Enums\ExemplarEstadoConservacao;
use Inventario\Domain\Enums\ExemplarMotivoBaixa;
use Inventario\Domain\Enums\ExemplarSituacao;
use Inventario\Domain\Observers\ExemplarObserver;
use Inventario\Domain\ValueObjects\CodigoPatrimonio;

#[ObservedBy([ExemplarObserver::class])]
#[Fillable(['obra_id', 'codigo_patrimonio', 'estado_conservacao', 'user_id'])]
#[Table('exemplares')]
class Exemplar extends Model
{
    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obra::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function emprestar(): void
    {
        if ($this->situacao !== ExemplarSituacao::NoAcervo) {
            throw new DomainException('Exemplar não está disponível para empréstimos.');
        }

        $this->situacao = ExemplarSituacao::Emprestado;
    }

    public function devolver(): void
    {
        if ($this->situacao !== ExemplarSituacao::Emprestado) {
            throw new DomainException('Exemplar não está emprestado.');
        }

        $this->situacao = ExemplarSituacao::NoAcervo;
    }

    public function baixar(ExemplarMotivoBaixa $motivo): void
    {
        if ($this->situacao === ExemplarSituacao::Baixado) {
            throw new DomainException('Exemplar já está baixado.');
        }

        $this->situacao = ExemplarSituacao::Baixado;
        $this->motivo_baixa = $motivo;
        $this->baixado_em = CarbonImmutable::now();
    }

    protected function casts(): array
    {
        return [
            'estado_conservacao' => ExemplarEstadoConservacao::class,
            'situacao' => ExemplarSituacao::class,
            'motivo_baixa' => ExemplarMotivoBaixa::class,
            'baixado_em' => 'immutable_datetime',
            'codigo_patrimonio' => ValueObjectCast::class.':'.CodigoPatrimonio::class,
        ];
    }

    #[Scope]
    public function disponivelPorObra(Builder $query, int $obraId): Builder
    {
        return $query->where('obra_id', $obraId)
            ->where('situacao', ExemplarSituacao::NoAcervo);
    }
}

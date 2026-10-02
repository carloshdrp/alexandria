<?php

namespace Inventario\Domain\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\ExemplarFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Inventario\Domain\Enums\ExemplarEstadoConservacao;
use Inventario\Domain\Enums\ExemplarMotivoBaixa;
use Inventario\Domain\Enums\ExemplarSituacao;
use Inventario\Domain\Observers\ExemplarObserver;
use Inventario\Domain\ValueObjects\CodigoPatrimonio;

/**
 * @property int $id
 * @property int $obra_id
 * @property int $user_id
 * @property CodigoPatrimonio $codigo_patrimonio
 * @property ExemplarSituacao $situacao
 * @property ExemplarEstadoConservacao $estado_conservacao
 * @property ExemplarMotivoBaixa|null $motivo_baixa
 * @property CarbonImmutable|null $baixado_em
 * @property-read Obra $obra
 * @property-read User $user
 */
#[ObservedBy([ExemplarObserver::class])]
#[Fillable(['obra_id', 'codigo_patrimonio', 'estado_conservacao', 'user_id'])]
#[Table('exemplares')]
class Exemplar extends Model
{
    /** @use HasFactory<ExemplarFactory> */
    use HasFactory;

    protected $attributes = [
        'situacao' => ExemplarSituacao::NoAcervo->value,
    ];

    /**
     * @return BelongsTo<Obra, $this>
     */
    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obra::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
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

    public function emprestarParaReserva(): void
    {
        if ($this->situacao !== ExemplarSituacao::Reservado) {
            throw new DomainException('Exemplar não está reservado.');
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

    public function reservar(): void
    {
        if ($this->situacao !== ExemplarSituacao::Emprestado) {
            throw new DomainException('Exemplar não está emprestado.');
        }

        $this->situacao = ExemplarSituacao::Reservado;
    }

    public function liberarReserva(): void
    {
        if ($this->situacao !== ExemplarSituacao::Reservado) {
            throw new DomainException('Exemplar não está reservado.');
        }

        $this->situacao = ExemplarSituacao::NoAcervo;
    }

    public function alterarEstadoConservacao(ExemplarEstadoConservacao $estado): void
    {
        if ($this->situacao === ExemplarSituacao::Baixado) {
            throw new DomainException('Exemplar baixado não pode ser editado.');
        }

        $this->estado_conservacao = $estado;
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
            'codigo_patrimonio' => CodigoPatrimonio::class,
        ];
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function disponivelPorObra(Builder $query, int $obraId): Builder
    {
        return $query->where('obra_id', $obraId)
            ->where('situacao', ExemplarSituacao::NoAcervo);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function naoBaixadosPorObra(Builder $query, int $obraId): Builder
    {
        return $query->where('obra_id', $obraId)
            ->where('situacao', '!=', ExemplarSituacao::Baixado);
    }
}

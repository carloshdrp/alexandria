<?php

namespace Emprestimos\Domain\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\ReservaFactory;
use DateTimeInterface;
use DomainException;
use Emprestimos\Domain\Enums\ReservaSituacao;
use Emprestimos\Domain\Observers\ReservaObserver;
use Emprestimos\Domain\ValueObjects\JanelaReserva;
use Emprestimos\Infrastructure\Casts\JanelaReservaCast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @property int $id
 * @property int $user_id
 * @property int $obra_id
 * @property int|null $exemplar_id
 * @property ReservaSituacao $situacao
 * @property CarbonImmutable $enfileirada_em
 * @property JanelaReserva|null $janela
 * @property-read User $user
 */
#[ObservedBy([ReservaObserver::class])]
#[Fillable(['user_id', 'obra_id', 'enfileirada_em'])]
class Reserva extends Model
{
    /** @use HasFactory<ReservaFactory> */
    use HasFactory;

    protected $attributes = [
        'situacao' => ReservaSituacao::Aguardando->value,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function disponibilizar(int $exemplarId): void
    {
        if ($this->situacao !== ReservaSituacao::Aguardando) {
            throw new DomainException('Reserva não está aguardando disponibilização');
        }

        $this->exemplar_id = $exemplarId;
        $this->janela = JanelaReserva::abrir(CarbonImmutable::now());
        $this->situacao = ReservaSituacao::Disponivel;
    }

    public function reenfileirar(): void
    {
        if ($this->situacao !== ReservaSituacao::Disponivel) {
            throw new DomainException('Reserva não está disponível');
        }

        $this->exemplar_id = null;
        $this->janela = null;
        $this->enfileirada_em = CarbonImmutable::now();
        $this->situacao = ReservaSituacao::Aguardando;
    }

    public function atender(): void
    {
        if ($this->situacao !== ReservaSituacao::Disponivel) {
            throw new DomainException('Reserva não está disponível');
        }

        $this->situacao = ReservaSituacao::Atendida;
    }

    public function expirar(): void
    {
        if ($this->situacao !== ReservaSituacao::Disponivel) {
            throw new DomainException('Reserva não está disponível');
        }

        $this->situacao = ReservaSituacao::Expirada;
    }

    public function cancelar(): void
    {
        if (! in_array($this->situacao, [ReservaSituacao::Aguardando, ReservaSituacao::Disponivel], true)) {
            throw new DomainException('Reserva não está aguardando ou disponível.');
        }

        $this->situacao = ReservaSituacao::Cancelada;
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'situacao' => ReservaSituacao::class,
            'enfileirada_em' => 'immutable_datetime',
            'janela' => JanelaReservaCast::class,
        ];
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function doUsuario(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function reservaPorObra(Builder $query, int $obraId, ?int $excetoUserId = null): Builder
    {
        $query = $query->where('obra_id', $obraId)
            ->whereIn('situacao', [ReservaSituacao::Aguardando, ReservaSituacao::Disponivel]);

        if ($excetoUserId !== null) {
            $query->where('user_id', '!=', $excetoUserId);
        }

        return $query;
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function disponiveisVencidas(Builder $query, ?DateTimeInterface $referencia = null): Builder
    {
        return $query->where('situacao', ReservaSituacao::Disponivel)
            ->where('expira_em', '<=', $referencia ?? CarbonImmutable::now());
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function proximaReserva(Builder $query, int $obraId): Builder
    {
        return $query->where('obra_id', $obraId)
            ->where('situacao', ReservaSituacao::Aguardando)
            ->oldest('enfileirada_em');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function disponiveisPorExemplar(Builder $query, int $exemplarId): Builder
    {
        return $query->where('exemplar_id', $exemplarId)
            ->where('situacao', ReservaSituacao::Disponivel);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function disponiveisPorObra(Builder $query, int $obraId): Builder
    {
        return $query->where('obra_id', $obraId)
            ->where('situacao', ReservaSituacao::Disponivel);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function obrasComFilaParada(Builder $query): Builder
    {
        return $query->select('obra_id')
            ->distinct()
            ->where('situacao', ReservaSituacao::Aguardando)
            ->whereNotExists(function (QueryBuilder $disponiveis) {
                $disponiveis->selectRaw('1')
                    ->from('reservas', 'disponiveis')
                    ->whereColumn('disponiveis.obra_id', 'reservas.obra_id')
                    ->where('disponiveis.situacao', ReservaSituacao::Disponivel->value);
            });
    }
}

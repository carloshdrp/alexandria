<?php

namespace Emprestimos\Domain\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;

#[ObservedBy([ReservaObserver::class])]
#[Fillable(['user_id', 'obra_id'])]
class Reserva extends Model
{
    protected $attributes = [
        'situacao' => ReservaSituacao::Aguardando->value,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'situacao' => ReservaSituacao::class,
            'janela' => JanelaReservaCast::class,
        ];
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

    #[Scope]
    protected function doUsuario(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

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

    #[Scope]
    protected function disponiveisVencidas(Builder $query, ?DateTimeInterface $referencia = null): Builder
    {
        return $query->where('situacao', ReservaSituacao::Disponivel)
            ->where('expira_em', '<=', $referencia ?? CarbonImmutable::now());
    }

    #[Scope]
    protected function disponiveisPorObra(Builder $query, int $obraId): Builder
    {
        return $query->where('obra_id', $obraId)
            ->where('situacao', ReservaSituacao::Disponivel);
    }

    #[Scope]
    protected function proximaReserva(Builder $query, int $obraId): Builder
    {
        return $query->where('obra_id', $obraId)
            ->where('situacao', ReservaSituacao::Aguardando)
            ->oldest();
    }

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

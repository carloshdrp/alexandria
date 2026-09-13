<?php

namespace Emprestimos\Domain\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
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
use Inventario\Domain\Models\Exemplar;
use Inventario\Domain\Models\Obra;

#[ObservedBy([ReservaObserver::class])]
#[Fillable(['user_id', 'obra_id'])]
class Reserva extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obra::class);
    }

    public function exemplar(): BelongsTo
    {
        return $this->belongsTo(Exemplar::class);
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
            throw new DomainException('Reserva não está aguardando aguardando ou disponível.');
        }

        $this->situacao = ReservaSituacao::Cancelada;
    }

    #[Scope]
    public function reservaPorObra(Builder $query, int $obraId, ?int $excetoUserId = null): Builder
    {
        $query = $query->where('obra_id', $obraId)
            ->whereIn('situacao', [ReservaSituacao::Aguardando, ReservaSituacao::Disponivel]);

        if ($excetoUserId !== null) {
            $query->where('user_id', '!=', $excetoUserId);
        }

        return $query;
    }

    #[Scope]
    public function proximaReserva(Builder $query, int $obraId): Builder
    {
        return $query->where('obra_id', $obraId)
            ->where('situacao', ReservaSituacao::Aguardando)
            ->oldest();
    }
}

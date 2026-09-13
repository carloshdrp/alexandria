<?php

namespace Emprestimos\Domain\Models;

use App\Models\User;
use Emprestimos\Domain\Enums\ReservaSituacao;
use Emprestimos\Infrastructure\Casts\JanelaReservaCast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Inventario\Domain\Models\Exemplar;
use Inventario\Domain\Models\Obra;

#[Fillable(['user_id', 'obra_id', 'exemplar_id', 'situacao', 'disponibilizada_em', 'expira_em'])]
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
            'disponibilizada_em' => 'datetime',
            'expira_em' => 'datetime',
            'janela' => JanelaReservaCast::class,
        ];
    }
}

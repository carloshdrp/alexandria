<?php

namespace App\Models;

use App\Enums\ReservaSituacao;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        ];
    }
}

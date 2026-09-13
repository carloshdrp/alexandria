<?php

namespace Emprestimos\Domain\Models;

use App\Enums\MultaSituacao;
use App\Models\User;
use Emprestimos\Domain\Enums\MultaSituacao;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['emprestimo_id', 'user_id', 'valor', 'dias_atraso', 'situacao', 'paga_em'])]
class Multa extends Model
{
    public function emprestimo(): BelongsTo
    {
        return $this->belongsTo(Emprestimo::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'situacao' => MultaSituacao::class,
            'paga_em' => 'datetime',
        ];
    }
}

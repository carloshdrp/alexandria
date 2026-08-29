<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['emprestimo_id', 'sequencia', 'prazo_anterior', 'prazo_novo', 'renovado_em'])]
#[Table('emprestimo_renovacoes')]
class EmprestimoRenovacao extends Model
{
    public function emprestimo(): BelongsTo
    {
        return $this->belongsTo(Emprestimo::class);
    }

    protected function casts(): array
    {
        return [
            'prazo_anterior' => 'date',
            'prazo_novo' => 'date',
            'renovado_em' => 'datetime',
        ];
    }
}

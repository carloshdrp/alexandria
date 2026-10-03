<?php

namespace Emprestimos\Domain\Models;

use Acesso\Domain\Models\User;
use Database\Factories\EmprestimoRenovacaoFactory;
use Emprestimos\Domain\Observers\EmprestimoRenovacaoObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([EmprestimoRenovacaoObserver::class])]
#[Fillable(['emprestimo_id', 'sequencia', 'prazo_anterior', 'prazo_novo', 'user_id', 'renovado_em'])]
#[Table('emprestimo_renovacoes')]
class EmprestimoRenovacao extends Model
{
    /** @use HasFactory<EmprestimoRenovacaoFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Emprestimo, $this>
     */
    public function emprestimo(): BelongsTo
    {
        return $this->belongsTo(Emprestimo::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

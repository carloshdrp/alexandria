<?php

namespace Emprestimos\Domain\Models;

use App\Models\User;
use Emprestimos\Domain\Enums\EmprestimoSituacao;
use Emprestimos\Infrastructure\Casts\PrazoEmprestimoCast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Inventario\Domain\Models\Exemplar;

#[Fillable(['user_id', 'exemplar_id', 'retirado_em', 'prazo_devolucao', 'devolvido_em', 'situacao', 'qtd_renovacoes'])]
class Emprestimo extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exemplar(): BelongsTo
    {
        return $this->belongsTo(Exemplar::class);
    }

    public function renovacoes(): HasMany
    {
        return $this->hasMany(EmprestimoRenovacao::class);
    }

    public function multa(): HasOne
    {
        return $this->hasOne(Multa::class);
    }

    protected function casts(): array
    {
        return [
            'situacao' => EmprestimoSituacao::class,
            'retirado_em' => 'datetime',
            'prazo_devolucao' => 'date',
            'devolvido_em' => 'datetime',
            'prazo' => PrazoEmprestimoCast::class,
        ];
    }
}

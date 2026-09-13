<?php

namespace Inventario\Domain\Models;

use App\Enums\ExemplarEstadoConservacao;
use App\Enums\ExemplarMotivoBaixa;
use App\Enums\ExemplarSituacao;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Inventario\Domain\Enums\ExemplarEstadoConservacao;
use Inventario\Domain\Enums\ExemplarMotivoBaixa;
use Inventario\Domain\Enums\ExemplarSituacao;

#[Fillable(['obra_id', 'codigo_patrimonio', 'estado_conservacao', 'situacao', 'motivo_baixa', 'user_id', 'baixado_em'])]
#[Table('exemplares')]
class Exemplar extends Model
{
    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obra::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'estado_conservacao' => ExemplarEstadoConservacao::class,
            'situacao' => ExemplarSituacao::class,
            'motivo_baixa' => ExemplarMotivoBaixa::class,
            'baixado_em' => 'datetime',
        ];
    }
}

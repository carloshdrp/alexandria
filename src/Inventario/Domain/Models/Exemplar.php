<?php

namespace Inventario\Domain\Models;

use App\Casts\ValueObjectCast;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Inventario\Domain\Enums\ExemplarEstadoConservacao;
use Inventario\Domain\Enums\ExemplarMotivoBaixa;
use Inventario\Domain\Enums\ExemplarSituacao;
use Inventario\Domain\ValueObjects\CodigoPatrimonio;

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
            'codigo_patrimonio' => ValueObjectCast::class.':'.CodigoPatrimonio::class,
        ];
    }
}

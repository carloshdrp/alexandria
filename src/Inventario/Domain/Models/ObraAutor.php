<?php

namespace Inventario\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['obra_id', 'autor_id', 'user_id', 'ordem'])]
#[Table('obra_autores')]
class ObraAutor extends Model
{
    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obra::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(Autor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

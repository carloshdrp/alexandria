<?php

namespace Inventario\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['nome', 'user_id'])]
#[Table('autores')]
class Autor extends Model
{
    public function obras(): BelongsToMany
    {
        return $this->belongsToMany(Obra::class, 'obra_autores')
            ->using(ObraAutor::class)
            ->withPivot('ordem')
            ->withTimestamps();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

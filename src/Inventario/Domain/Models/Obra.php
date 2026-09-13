<?php

namespace Inventario\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['titulo', 'isbn', 'editora_id', 'categoria_id', 'user_id', 'ano_publicacao'])]
class Obra extends Model
{
    use SoftDeletes;

    public function editora(): BelongsTo
    {
        return $this->belongsTo(Editora::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function autores(): BelongsToMany
    {
        return $this->belongsToMany(Autor::class, 'obra_autores')
            ->using(ObraAutor::class)
            ->withPivot('ordem')
            ->withTimestamps()
            ->orderByPivot('ordem');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

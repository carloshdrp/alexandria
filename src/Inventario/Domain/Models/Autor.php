<?php

namespace Inventario\Domain\Models;

use Acesso\Domain\Models\User;
use Database\Factories\AutorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Inventario\Domain\Observers\AutorObserver;

/**
 * @property int $id
 * @property string $nome
 * @property int $user_id
 */
#[ObservedBy([AutorObserver::class])]
#[Fillable(['nome', 'user_id'])]
#[Table('autores')]
class Autor extends Model
{
    /** @use HasFactory<AutorFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<Obra, $this, ObraAutor>
     */
    public function obras(): BelongsToMany
    {
        return $this->belongsToMany(Obra::class, 'obra_autores')
            ->using(ObraAutor::class)
            ->withPivot('ordem')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

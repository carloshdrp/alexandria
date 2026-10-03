<?php

namespace Inventario\Domain\Models;

use Acesso\Domain\Models\User;
use Database\Factories\CategoriaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Inventario\Domain\Observers\CategoriaObserver;

/**
 * @property int $id
 * @property string $nome
 * @property int $user_id
 */
#[ObservedBy([CategoriaObserver::class])]
#[Fillable(['nome', 'user_id'])]
class Categoria extends Model
{
    /** @use HasFactory<CategoriaFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

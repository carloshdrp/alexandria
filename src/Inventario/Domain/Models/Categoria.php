<?php

namespace Inventario\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Inventario\Domain\Observers\CategoriaObserver;

#[ObservedBy([CategoriaObserver::class])]
#[Fillable(['nome', 'user_id'])]
class Categoria extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

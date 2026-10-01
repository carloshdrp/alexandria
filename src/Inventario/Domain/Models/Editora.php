<?php

namespace Inventario\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Inventario\Domain\Observers\EditoraObserver;

#[ObservedBy([EditoraObserver::class])]
#[Fillable(['nome', 'user_id'])]
class Editora extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

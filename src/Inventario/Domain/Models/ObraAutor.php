<?php

namespace Inventario\Domain\Models;

use Acesso\Domain\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['obra_id', 'autor_id', 'user_id', 'ordem'])]
#[Table('obra_autores')]
class ObraAutor extends Pivot
{
    use SoftDeletes;

    public $incrementing = true;

    /**
     * @return BelongsTo<Obra, $this>
     */
    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obra::class);
    }

    /**
     * @return BelongsTo<Autor, $this>
     */
    public function autor(): BelongsTo
    {
        return $this->belongsTo(Autor::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function doAutor(Builder $query, int $autorId): Builder
    {
        $query->withTrashed();

        return $query->where('autor_id', $autorId);
    }
}

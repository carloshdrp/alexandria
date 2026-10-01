<?php

namespace Inventario\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Inventario\Domain\Observers\ObraObserver;
use Inventario\Domain\Services\ArmazenadorCapaObra;
use Inventario\Domain\ValueObjects\Isbn;

#[ObservedBy([ObraObserver::class])]
#[Fillable(['titulo', 'isbn', 'editora_id', 'categoria_id', 'user_id', 'ano_publicacao'])]
class Obra extends Model
{
    use SoftDeletes;

    protected $appends = ['capa_url'];

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
            ->wherePivotNull('deleted_at')
            ->orderByPivot('ordem');
    }

    public function definirAutores(array $autores, int $userId): void
    {
        ObraAutor::onlyTrashed()
            ->where('obra_id', $this->id)
            ->whereIn('autor_id', $autores)
            ->restore();

        $this->autores()->sync(
            collect($autores)->mapWithKeys(
                fn (int $autorId, int $posicao) => [$autorId => ['ordem' => $posicao + 1, 'user_id' => $userId]]
            )->all()
        );

        $this->unsetRelation('autores');
    }

    public function exemplares(): HasMany
    {
        return $this->hasMany(Exemplar::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function capaUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->capa_path === null) {
                return null;
            }

            return (new ArmazenadorCapaObra)->url($this->capa_path);
        });
    }

    protected function casts(): array
    {
        return [
            'isbn' => Isbn::class,
        ];
    }

    #[Scope]
    protected function daEditora(Builder $query, int $editoraId): Builder
    {
        $query->withTrashed();

        return $query->where('editora_id', $editoraId);
    }

    #[Scope]
    protected function daCategoria(Builder $query, int $categoriaId): Builder
    {
        $query->withTrashed();

        return $query->where('categoria_id', $categoriaId);
    }
}

<?php

namespace Inventario\Domain\Models;

use Acesso\Domain\Models\User;
use Database\Factories\ObraFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Inventario\Domain\Observers\ObraObserver;
use Inventario\Domain\Services\ArmazenadorCapaObra;
use Inventario\Domain\ValueObjects\Isbn;

/**
 * @property int $id
 * @property string $titulo
 * @property Isbn|null $isbn
 * @property int $editora_id
 * @property int $categoria_id
 * @property int $user_id
 * @property int|null $ano_publicacao
 * @property string|null $capa_path
 * @property string|null $capa_miniatura_path
 * @property-read string|null $capa_url
 * @property-read string|null $capa_miniatura_url
 * @property-read Editora $editora
 * @property-read Categoria $categoria
 * @property-read User $user
 * @property-read Collection<int, Autor> $autores
 * @property-read Collection<int, Exemplar> $exemplares
 * @property-read int|null $exemplares_total
 * @property-read int|null $exemplares_disponiveis
 */
#[ObservedBy([ObraObserver::class])]
#[Fillable(['titulo', 'isbn', 'editora_id', 'categoria_id', 'user_id', 'ano_publicacao'])]
class Obra extends Model
{
    /** @use HasFactory<ObraFactory> */
    use HasFactory, SoftDeletes;

    protected $appends = ['capa_url', 'capa_miniatura_url'];

    /**
     * @return BelongsTo<Editora, $this>
     */
    public function editora(): BelongsTo
    {
        return $this->belongsTo(Editora::class);
    }

    /**
     * @return BelongsTo<Categoria, $this>
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * @param  list<int>  $autores
     */
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

    /**
     * @return BelongsToMany<Autor, $this, ObraAutor>
     */
    public function autores(): BelongsToMany
    {
        return $this->belongsToMany(Autor::class, 'obra_autores')
            ->using(ObraAutor::class)
            ->withPivot('ordem')
            ->withTimestamps()
            ->wherePivotNull('deleted_at')
            ->orderByPivot('ordem');
    }

    /**
     * @return HasMany<Exemplar, $this>
     */
    public function exemplares(): HasMany
    {
        return $this->hasMany(Exemplar::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function capaUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->capa_path === null) {
                return null;
            }

            return (new ArmazenadorCapaObra)->url($this->capa_path);
        });
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function capaMiniaturaUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->capa_miniatura_path === null) {
                return null;
            }

            return (new ArmazenadorCapaObra)->url($this->capa_miniatura_path);
        });
    }

    protected function casts(): array
    {
        return [
            'isbn' => Isbn::class,
        ];
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function daEditora(Builder $query, int $editoraId): Builder
    {
        $query->withTrashed();

        return $query->where('editora_id', $editoraId);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function daCategoria(Builder $query, int $categoriaId): Builder
    {
        $query->withTrashed();

        return $query->where('categoria_id', $categoriaId);
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UsuarioPapel;
use App\Enums\UsuarioSituacao;
use Database\Factories\UserFactory;
use Emprestimos\Domain\Models\Emprestimo;
use Emprestimos\Domain\Models\Multa;
use Emprestimos\Domain\Models\Reserva;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Inventario\Domain\Models\Autor;
use Inventario\Domain\Models\Categoria;
use Inventario\Domain\Models\Editora;
use Inventario\Domain\Models\Exemplar;
use Inventario\Domain\Models\Obra;
use Inventario\Domain\Models\ObraAutor;

#[Fillable(['name', 'email', 'password', 'documento', 'telefone', 'situacao', 'papel', 'bloqueado_em'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public function emprestimos(): HasMany
    {
        return $this->hasMany(Emprestimo::class);
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    public function multas(): HasMany
    {
        return $this->hasMany(Multa::class);
    }

    public function autores(): HasMany
    {
        return $this->hasMany(Autor::class);
    }

    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class);
    }

    public function editoras(): HasMany
    {
        return $this->hasMany(Editora::class);
    }

    public function exemplares(): HasMany
    {
        return $this->hasMany(Exemplar::class);
    }

    public function obras(): HasMany
    {
        return $this->hasMany(Obra::class);
    }

    public function obraAutores(): HasMany
    {
        return $this->hasMany(ObraAutor::class);
    }

    public function ehBibliotecario(): bool
    {
        return $this->papel === UsuarioPapel::Bibliotecario;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'situacao' => UsuarioSituacao::class,
            'papel' => UsuarioPapel::class,
            'bloqueado_em' => 'datetime',
        ];
    }
}

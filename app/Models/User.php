<?php

namespace App\Models;

use App\Casts\ValueObjectCast;
use App\Enums\UsuarioPapel;
use App\Enums\UsuarioSituacao;
use App\ValueObjects\Documento;
use App\ValueObjects\Telefone;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'documento', 'telefone', 'situacao', 'papel', 'bloqueado_em'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

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
            'documento' => ValueObjectCast::class.':'.Documento::class,
            'telefone' => ValueObjectCast::class.':'.Telefone::class,
        ];
    }
}

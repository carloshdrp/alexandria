<?php

namespace App\Models;

use App\Acesso\Domain\Enums\UsuarioPapel;
use App\Acesso\Domain\Enums\UsuarioSituacao;
use App\Acesso\Domain\ValueObjects\Documento;
use App\Acesso\Domain\ValueObjects\Telefone;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use DomainException;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'documento', 'telefone', 'situacao', 'papel', 'bloqueado_em'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public function ehBibliotecario(): bool
    {
        return $this->papel === UsuarioPapel::Bibliotecario;
    }

    public function bloquear(): void
    {
        if ($this->estaBloqueado()) {
            throw new DomainException('Usuário já está bloqueado.');
        }

        $this->situacao = UsuarioSituacao::Bloqueado;
        $this->bloqueado_em = CarbonImmutable::now();
    }

    public function estaBloqueado(): bool
    {
        return $this->situacao === UsuarioSituacao::Bloqueado;
    }

    public function desbloquear(): void
    {
        if (! $this->estaBloqueado()) {
            throw new DomainException('Usuário não está bloqueado.');
        }

        $this->situacao = UsuarioSituacao::Ativo;
        $this->bloqueado_em = null;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'situacao' => UsuarioSituacao::class,
            'papel' => UsuarioPapel::class,
            'bloqueado_em' => 'immutable_datetime',
            'documento' => Documento::class,
            'telefone' => Telefone::class,
        ];
    }
}

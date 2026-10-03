<?php

namespace Acesso\Application\Actions;

use Acesso\Domain\Enums\UsuarioPapel;
use Acesso\Domain\Enums\UsuarioSituacao;
use Acesso\Domain\Models\User;
use Acesso\Domain\ValueObjects\Documento;
use Acesso\Domain\ValueObjects\Telefone;
use Acesso\Infrastructure\Rules\DocumentoValido;
use Acesso\Infrastructure\Rules\TelefoneValido;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use NormalizaDocumentos, PasswordValidationRules;

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        $dados = Validator::make($this->normalizar($input), $this->regras(), [], $this->atributos())->validate();

        return User::create([
            'name' => $dados['name'],
            'email' => $dados['email'],
            'password' => Hash::make($dados['password']),
            'documento' => Documento::fromNative($dados['documento']),
            'telefone' => isset($dados['telefone']) ? Telefone::fromNative($dados['telefone']) : null,
            'papel' => UsuarioPapel::Cliente,
            'situacao' => UsuarioSituacao::Ativo,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function regras(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'documento' => ['required', 'string', new DocumentoValido, Rule::unique(User::class)],
            'telefone' => ['nullable', 'string', new TelefoneValido],
            'password' => $this->passwordRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function atributos(): array
    {
        return [
            'name' => 'nome',
            'email' => 'e-mail',
            'documento' => 'CPF',
            'telefone' => 'telefone',
            'password' => 'senha',
        ];
    }
}

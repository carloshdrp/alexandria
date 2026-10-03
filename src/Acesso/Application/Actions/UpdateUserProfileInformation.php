<?php

namespace Acesso\Application\Actions;

use Acesso\Domain\Models\User;
use Acesso\Domain\ValueObjects\Documento;
use Acesso\Domain\ValueObjects\Telefone;
use Acesso\Infrastructure\Rules\DocumentoValido;
use Acesso\Infrastructure\Rules\TelefoneValido;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    use NormalizaDocumentos;

    /**
     * @param  User  $user
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update($user, array $input): void
    {
        $dados = Validator::make(
            $this->normalizar($input),
            $this->regras($user),
            [],
            $this->atributos()
        )->validate();

        $user->name = $dados['name'];
        $user->documento = Documento::fromNative($dados['documento']);
        $user->telefone = isset($dados['telefone']) ? Telefone::fromNative($dados['telefone']) : null;

        if ($dados['email'] === $user->email) {
            $user->save();

            return;
        }

        $user->email = $dados['email'];
        $user->email_verified_at = null;
        $user->save();

        $user->sendEmailVerificationNotification();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function regras(User $user): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user->getKey())],
            'documento' => ['required', 'string', new DocumentoValido, Rule::unique(User::class)->ignore($user->getKey())],
            'telefone' => ['nullable', 'string', new TelefoneValido],
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
        ];
    }
}

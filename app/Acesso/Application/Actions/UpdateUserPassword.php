<?php

namespace App\Acesso\Application\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    /**
     * @param  User  $user
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update($user, array $input): void
    {
        $dados = Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $this->passwordRules(),
        ], [
            'current_password.current_password' => 'A senha atual está incorreta.',
        ], [
            'current_password' => 'senha atual',
            'password' => 'nova senha',
        ])->validate();

        $user->forceFill([
            'password' => Hash::make($dados['password']),
        ])->save();
    }
}

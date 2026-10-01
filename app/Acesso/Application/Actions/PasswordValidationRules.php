<?php

namespace App\Acesso\Application\Actions;

trait PasswordValidationRules
{
    /**
     * @return array<int, string>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', 'min:8', 'confirmed'];
    }
}

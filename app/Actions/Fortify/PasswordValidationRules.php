<?php

namespace App\Actions\Fortify;

use Illuminate\Contracts\Validation\ValidationRule;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', 'min:8', 'confirmed'];
    }
}

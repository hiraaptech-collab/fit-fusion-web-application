<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PasswordLength implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Bcrypt accepts at most 72 bytes, including for passwords with Unicode.
        if (is_string($value) && strlen($value) > 72) {
            $fail('This password is too long. Please use fewer characters.');
        }
    }
}

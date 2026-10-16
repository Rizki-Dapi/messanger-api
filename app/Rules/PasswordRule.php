<?php

namespace App\Rules;

use Illuminate\Validation\Rules\Password;

class PasswordRule
{
    public static function required(): array
    {
        return [
            'required',
            'string',
            'confirmed',
            self::policy(),
        ];
    }

    public static function optional(): array
    {
        return [
            'sometimes',
            'string',
            'confirmed',
            self::policy(),
        ];
    }

    public static function policy(): Password
    {
        return Password::min(8)->mixedCase()->numbers()->symbols();
    }
}

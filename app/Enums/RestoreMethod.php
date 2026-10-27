<?php

namespace App\Enums;

/**
 * How a deleted account was brought back, shown in the "account restored" email.
 */
enum RestoreMethod: string
{
    case Password = 'password';
    case Google = 'google';
    case PasswordReset = 'password_reset';

    public function label(): string
    {
        return match ($this) {
            self::Password => __('signing in with your password'),
            self::Google => __('signing in with Google'),
            self::PasswordReset => __('resetting your password'),
        };
    }
}

<?php

namespace App\Enums;

enum ServerAuthType: string
{
    case Key = 'key';
    case Password = 'password';

    public function label(): string
    {
        return match ($this) {
            self::Key => 'SSH key',
            self::Password => 'Password',
        };
    }
}

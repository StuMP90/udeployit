<?php

namespace App\Enums;

enum ScriptFailureAction: string
{
    case Abort = 'abort';
    case Continue = 'continue';

    public function label(): string
    {
        return match ($this) {
            self::Abort => 'Abort the deployment',
            self::Continue => 'Continue anyway',
        };
    }
}

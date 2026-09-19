<?php

namespace App\Enums;

enum DeploymentScriptType: string
{
    case Before = 'before';
    case After = 'after';

    public function label(): string
    {
        return match ($this) {
            self::Before => 'Before deployment',
            self::After => 'After deployment',
        };
    }
}

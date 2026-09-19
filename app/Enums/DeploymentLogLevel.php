<?php

namespace App\Enums;

enum DeploymentLogLevel: string
{
    case Info = 'info';
    case Error = 'error';
}

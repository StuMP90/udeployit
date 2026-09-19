<?php

namespace App\Enums;

enum DeploymentType: string
{
    case Full = 'full';
    case Incremental = 'incremental';
}

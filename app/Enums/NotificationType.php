<?php

namespace App\Enums;

enum NotificationType: string
{
    case DeploymentSuccess = 'deployment_success';
    case DeploymentFailure = 'deployment_failure';
    case BranchUpdated = 'branch_updated';
}

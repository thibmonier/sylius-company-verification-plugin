<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Status;

enum RegisterStatus: string
{
    case Active = 'active';
    case Closed = 'closed';
    case NotFound = 'not_found';
    case NotApplicable = 'not_applicable';
    case NotVerified = 'not_verified';
}

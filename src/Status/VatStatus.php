<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Status;

enum VatStatus: string
{
    case Valid = 'valid';
    case Invalid = 'invalid';
    case NotApplicable = 'not_applicable';
    case NotVerified = 'not_verified';
}

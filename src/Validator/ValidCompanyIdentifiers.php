<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class ValidCompanyIdentifiers extends Constraint
{
    public string $vatMessage = 'tmonier_company_verification.validation.vat_number';

    public string $registrationMessage = 'tmonier_company_verification.validation.registration_number';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

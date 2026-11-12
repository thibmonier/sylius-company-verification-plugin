<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Tmonier\SyliusCompanyAccountsPlugin\Entity\CompanyInterface;
use Tmonier\SyliusCompanyVerificationPlugin\Identifier\CompanyIdentifiers;

/** Format only (Luhn key, VAT format and French key): no call to an external service. */
final class ValidCompanyIdentifiersValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidCompanyIdentifiers) {
            throw new UnexpectedTypeException($constraint, ValidCompanyIdentifiers::class);
        }

        if (!$value instanceof CompanyInterface) {
            return;
        }

        $identifiers = CompanyIdentifiers::of($value->getVatNumber(), $value->getRegistrationNumber());

        if (null !== $identifiers->vatNumber() && !$identifiers->isVatFormatValid()) {
            $this->context->buildViolation($constraint->vatMessage)->atPath('vatNumber')->addViolation();
        }
    }
}

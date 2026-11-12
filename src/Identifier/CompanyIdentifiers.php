<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Identifier;

/**
 * The SIREN/SIRET and the VAT number of a company, normalized (no space, dot or hyphen, upper
 * case). A registration number is French when there is no VAT number or when it starts with FR.
 */
final class CompanyIdentifiers
{
    private function __construct(
        private readonly ?string $vatNumber,
        private readonly ?string $registrationNumber,
    ) {
    }

    public static function of(?string $vatNumber, ?string $registrationNumber): self
    {
        return new self(self::normalize($vatNumber), self::normalize($registrationNumber));
    }

    public function vatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function registrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function vatCountryCode(): ?string
    {
        return null === $this->vatNumber ? null : substr($this->vatNumber, 0, 2);
    }

    public function vatNumberWithoutCountry(): ?string
    {
        return null === $this->vatNumber ? null : substr($this->vatNumber, 2);
    }

    public function isVatFormatValid(): bool
    {
        return null !== $this->vatNumber && FrenchIdentifiers::isValidVatNumber($this->vatNumber);
    }

    /** False for a foreign company: the number is then not a SIREN/SIRET and is not checked. */
    public function hasFrenchRegistrationNumber(): bool
    {
        return null !== $this->registrationNumber && (null === $this->vatNumber || 'FR' === $this->vatCountryCode());
    }

    public function isRegistrationFormatValid(): bool
    {
        return null !== $this->registrationNumber &&
            (FrenchIdentifiers::isValidSiren($this->registrationNumber) || FrenchIdentifiers::isValidSiret($this->registrationNumber));
    }

    private static function normalize(?string $value): ?string
    {
        $normalized = strtoupper((string) preg_replace('/[\s.\-]/', '', (string) $value));

        return '' === $normalized ? null : $normalized;
    }
}

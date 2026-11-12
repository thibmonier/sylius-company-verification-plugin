<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Entity;

use Tmonier\SyliusCompanyAccountsPlugin\Entity\CompanyInterface;
use Tmonier\SyliusCompanyVerificationPlugin\Register\RegisterResult;
use Tmonier\SyliusCompanyVerificationPlugin\Status\RegisterStatus;
use Tmonier\SyliusCompanyVerificationPlugin\Status\VatStatus;
use Tmonier\SyliusCompanyVerificationPlugin\Vies\VatResult;

/**
 * One run of the verification of a company: append only, the latest one is the current state.
 * The identifiers are kept as they were checked.
 */
class CompanyVerification
{
    public const REGISTER_SOURCE = 'recherche-entreprises.api.gouv.fr';

    public const VAT_SOURCE = 'VIES';

    protected ?int $id = null;

    private RegisterStatus $registerStatus;

    private ?string $registerName;

    private ?string $registerAddress;

    private ?\DateTimeImmutable $registerClosedAt;

    private VatStatus $vatStatus;

    private ?string $vatName;

    private ?string $vatAddress;

    public function __construct(
        private CompanyInterface $company,
        private ?string $registrationNumber,
        private ?string $vatNumber,
        RegisterResult $register,
        VatResult $vat,
        private \DateTimeImmutable $verifiedAt,
    ) {
        $this->registerStatus = $register->status;
        $this->registerName = $register->name;
        $this->registerAddress = $register->address;
        $this->registerClosedAt = $register->closedAt;
        $this->vatStatus = $vat->status;
        $this->vatName = $vat->name;
        $this->vatAddress = $vat->address;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompany(): CompanyInterface
    {
        return $this->company;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function getRegisterStatus(): RegisterStatus
    {
        return $this->registerStatus;
    }

    public function getRegisterName(): ?string
    {
        return $this->registerName;
    }

    public function getRegisterAddress(): ?string
    {
        return $this->registerAddress;
    }

    public function getRegisterClosedAt(): ?\DateTimeImmutable
    {
        return $this->registerClosedAt;
    }

    public function getVatStatus(): VatStatus
    {
        return $this->vatStatus;
    }

    public function getVatName(): ?string
    {
        return $this->vatName;
    }

    public function getVatAddress(): ?string
    {
        return $this->vatAddress;
    }

    public function getVerifiedAt(): \DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    public function getRegisterSource(): string
    {
        return self::REGISTER_SOURCE;
    }

    public function getVatSource(): string
    {
        return self::VAT_SOURCE;
    }
}

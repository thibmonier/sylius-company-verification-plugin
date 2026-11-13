<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Verification;

use Psr\Clock\ClockInterface;
use Tmonier\SyliusCompanyAccountsPlugin\Entity\CompanyInterface;
use Tmonier\SyliusCompanyVerificationPlugin\Entity\CompanyVerification;
use Tmonier\SyliusCompanyVerificationPlugin\Identifier\CompanyIdentifiers;
use Tmonier\SyliusCompanyVerificationPlugin\Register\RegisterClientInterface;
use Tmonier\SyliusCompanyVerificationPlugin\Register\RegisterResult;
use Tmonier\SyliusCompanyVerificationPlugin\Status\RegisterStatus;
use Tmonier\SyliusCompanyVerificationPlugin\Status\VatStatus;
use Tmonier\SyliusCompanyVerificationPlugin\Vies\VatResult;
use Tmonier\SyliusCompanyVerificationPlugin\Vies\ViesClientInterface;

/**
 * Checks the identifiers of a company and records the outcome. Nothing is approved or refused
 * here: an unreachable service gives "not verified". A malformed identifier is never sent out.
 */
final class CompanyVerifier
{
    public function __construct(
        private readonly RegisterClientInterface $registerClient,
        private readonly ViesClientInterface $viesClient,
        private readonly VerificationRepositoryInterface $repository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function verify(CompanyInterface $company): CompanyVerification
    {
        $identifiers = CompanyIdentifiers::of($company->getVatNumber(), $company->getRegistrationNumber());

        $verification = new CompanyVerification(
            $company,
            $identifiers->registrationNumber(),
            $identifiers->vatNumber(),
            $this->checkRegister($identifiers),
            $this->checkVat($identifiers),
            \DateTimeImmutable::createFromInterface($this->clock->now()),
        );
        $this->repository->add($verification);

        return $verification;
    }

    private function checkRegister(CompanyIdentifiers $identifiers): RegisterResult
    {
        if (!$identifiers->hasFrenchRegistrationNumber()) {
            return new RegisterResult(RegisterStatus::NotApplicable);
        }

        if (!$identifiers->isRegistrationFormatValid()) {
            return RegisterResult::unavailable();
        }

        return $this->registerClient->lookup((string) $identifiers->registrationNumber());
    }

    private function checkVat(CompanyIdentifiers $identifiers): VatResult
    {
        if (null === $identifiers->vatNumber()) {
            return new VatResult(VatStatus::NotApplicable);
        }

        if (!$identifiers->isVatFormatValid()) {
            return new VatResult(VatStatus::Invalid);
        }

        return $this->viesClient->check((string) $identifiers->vatCountryCode(), (string) $identifiers->vatNumberWithoutCountry());
    }
}

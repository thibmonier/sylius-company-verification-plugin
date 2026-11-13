<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Verification;

use Tmonier\SyliusCompanyAccountsPlugin\Entity\CompanyInterface;
use Tmonier\SyliusCompanyVerificationPlugin\Entity\CompanyVerification;

interface VerificationRepositoryInterface
{
    public function add(CompanyVerification $verification): void;

    public function findLatest(CompanyInterface $company): ?CompanyVerification;

    /**
     * Companies never verified or whose latest verification could not reach a service.
     *
     * @return list<int>
     */
    public function findCompanyIdsToVerify(): array;
}

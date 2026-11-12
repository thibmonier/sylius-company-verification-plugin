<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Message;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Tmonier\SyliusCompanyAccountsPlugin\Entity\CompanyInterface;
use Tmonier\SyliusCompanyAccountsPlugin\Repository\CompanyRepositoryInterface;
use Tmonier\SyliusCompanyVerificationPlugin\Verification\CompanyVerifier;

#[AsMessageHandler]
final class VerifyCompanyHandler
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly CompanyVerifier $verifier,
    ) {
    }

    public function __invoke(VerifyCompany $message): void
    {
        $company = $this->companyRepository->find($message->companyId);

        // Deleted between the message and its handling: nothing to verify.
        if (!$company instanceof CompanyInterface) {
            return;
        }

        $this->verifier->verify($company);
    }
}

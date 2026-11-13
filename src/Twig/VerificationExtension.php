<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Twig;

use Tmonier\SyliusCompanyAccountsPlugin\Entity\CompanyInterface;
use Tmonier\SyliusCompanyVerificationPlugin\Entity\CompanyVerification;
use Tmonier\SyliusCompanyVerificationPlugin\Verification\VerificationRepositoryInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class VerificationExtension extends AbstractExtension
{
    public function __construct(private readonly VerificationRepositoryInterface $repository)
    {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('tmonier_company_verification', $this->latest(...))];
    }

    public function latest(mixed $company): ?CompanyVerification
    {
        return $company instanceof CompanyInterface ? $this->repository->findLatest($company) : null;
    }
}

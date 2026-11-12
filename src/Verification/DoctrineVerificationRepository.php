<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Verification;

use Doctrine\ORM\EntityManagerInterface;
use Tmonier\SyliusCompanyAccountsPlugin\Entity\CompanyInterface;
use Tmonier\SyliusCompanyVerificationPlugin\Entity\CompanyVerification;
use Tmonier\SyliusCompanyVerificationPlugin\Status\RegisterStatus;
use Tmonier\SyliusCompanyVerificationPlugin\Status\VatStatus;

final class DoctrineVerificationRepository implements VerificationRepositoryInterface
{
    /** @param class-string<CompanyInterface> $companyClass */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $companyClass,
    ) {
    }

    public function add(CompanyVerification $verification): void
    {
        $this->entityManager->persist($verification);
        $this->entityManager->flush();
    }

    public function findLatest(CompanyInterface $company): ?CompanyVerification
    {
        $verification = $this->entityManager->createQueryBuilder()
            ->select('v')
            ->from(CompanyVerification::class, 'v')
            ->where('v.company = :company')
            ->setParameter('company', $company)
            ->orderBy('v.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $verification instanceof CompanyVerification ? $verification : null;
    }

    public function findCompanyIdsToVerify(): array
    {
        $ids = $this->entityManager->createQuery(sprintf(
            'SELECT c.id FROM %s c LEFT JOIN %s v WITH v.id = (SELECT MAX(l.id) FROM %s l WHERE l.company = c) '
            . 'WHERE v.id IS NULL OR v.registerStatus = :registerStatus OR v.vatStatus = :vatStatus ORDER BY c.id',
            $this->companyClass,
            CompanyVerification::class,
            CompanyVerification::class,
        ))
            ->setParameter('registerStatus', RegisterStatus::NotVerified)
            ->setParameter('vatStatus', VatStatus::NotVerified)
            ->getSingleColumnResult()
        ;

        $companyIds = [];
        foreach ($ids as $id) {
            if (is_numeric($id)) {
                $companyIds[] = (int) $id;
            }
        }

        return $companyIds;
    }
}

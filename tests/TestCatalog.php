<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCompanyVerificationPlugin;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Tmonier\SyliusCompanyAccountsPlugin\Entity\CompanyInterface;

/** Builds the companies of the tests; saving one asks for its verification, as in production. */
final class TestCatalog
{
    private int $sequence = 0;

    private readonly string $suffix;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->suffix = bin2hex(random_bytes(3));
    }

    public function company(?string $vatNumber = 'FR40303265045', ?string $registrationNumber = '30326504500011'): CompanyInterface
    {
        /** @var FactoryInterface<CompanyInterface> $factory */
        $factory = $this->container->get('tmonier_company_accounts.factory.company');
        $company = $factory->createNew();
        $company->setCode(strtoupper(sprintf('ACME_%s_%d', $this->suffix, ++$this->sequence)));
        $company->setName('Acme ' . $this->suffix);
        $company->setVatNumber($vatNumber);
        $company->setRegistrationNumber($registrationNumber);
        $company->setEnabled(true);

        $entityManager = $this->entityManager();
        $entityManager->persist($company);
        $entityManager->flush();

        return $company;
    }

    private function entityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        return $entityManager;
    }
}

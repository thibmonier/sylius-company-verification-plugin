<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCompanyVerificationPlugin;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Tmonier\SyliusCompanyAccountsPlugin\Entity\CompanyInterface;
use Tmonier\SyliusCompanyVerificationPlugin\Entity\CompanyVerification;
use Tmonier\SyliusCompanyVerificationPlugin\Verification\VerificationRepositoryInterface;

/** Every test runs in a transaction rolled back at the end, and no request leaves the process. */
trait VerificationTestTrait
{
    private ?TestCatalog $testCatalog = null;

    private function beginTestTransaction(): void
    {
        $this->connection()->beginTransaction();
    }

    private function rollBackTestTransaction(): void
    {
        if ($this->connection()->isTransactionActive()) {
            $this->connection()->rollBack();
        }
        $this->testCatalog = null;
    }

    private function catalog(): TestCatalog
    {
        return $this->testCatalog ??= new TestCatalog(self::getContainer());
    }

    private function http(): FakeHttpClient
    {
        $http = self::getContainer()->get('tmonier_company_verification.test.http_client');
        self::assertInstanceOf(FakeHttpClient::class, $http);

        return $http;
    }

    private function latest(CompanyInterface $company): ?CompanyVerification
    {
        $repository = self::getContainer()->get('tmonier_company_verification.test.repository');
        self::assertInstanceOf(VerificationRepositoryInterface::class, $repository);

        return $repository->findLatest($company);
    }

    /** @return array{results: list<array<string, mixed>>} */
    private function activeRegister(string $siret = '30326504500011'): array
    {
        return [
            'results' => [[
                'siren' => substr($siret, 0, 9),
                'nom_complet' => 'SODIMAS',
                'etat_administratif' => 'A',
                'date_fermeture' => null,
                'siege' => ['siret' => $siret, 'adresse' => '11 RUE AMPERE 26600 PONT DE L ISERE', 'etat_administratif' => 'A'],
                'matching_etablissements' => [['siret' => $siret, 'adresse' => '11 RUE AMPERE 26600 PONT DE L ISERE', 'etat_administratif' => 'A', 'date_fermeture' => null]],
            ]],
        ];
    }

    /** @return array{results: list<array<string, mixed>>} */
    private function closedRegister(string $closedAt, string $siret = '30326504500011'): array
    {
        $payload = $this->activeRegister($siret);
        $payload['results'][0]['matching_etablissements'][0]['etat_administratif'] = 'F';
        $payload['results'][0]['matching_etablissements'][0]['date_fermeture'] = $closedAt;

        return $payload;
    }

    /** @return array<string, mixed> */
    private function validVat(): array
    {
        return ['valid' => true, 'name' => 'SA SODIMAS', 'address' => "11 RUE AMPERE\n26600 PONT DE L ISERE"];
    }

    private function entityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        return $entityManager;
    }

    private function connection(): Connection
    {
        return $this->entityManager()->getConnection();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCompanyVerificationPlugin\Integration;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Tmonier\SyliusCompanyVerificationPlugin\Migrations\Version20261112100000;

/**
 * Not in a transaction: DDL statements are not rolled back on MySQL. The table is dropped and
 * created again, and is back when the test ends.
 */
final class MigrationTest extends KernelTestCase
{
    public function testTheTableGoesDownAndUpAgain(): void
    {
        $kernel = self::bootKernel();
        self::assertTrue($this->hasTable(), 'The test database is migrated.');

        try {
            $this->execute($kernel, 'down');
            self::assertFalse($this->hasTable(), 'Down drops the table.');

            $this->execute($kernel, 'up');
            self::assertTrue($this->hasTable(), 'Up creates the table.');
        } finally {
            if (!$this->hasTable()) {
                $this->execute($kernel, 'up');
            }
        }
    }

    private function hasTable(): bool
    {
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        self::assertInstanceOf(Connection::class, $connection);

        return $connection->createSchemaManager()->tablesExist(['tmonier_company_verification']);
    }

    private function execute(KernelInterface $kernel, string $direction): void
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $input = new ArrayInput([
            'command' => 'doctrine:migrations:execute',
            'versions' => [Version20261112100000::class],
            '--' . $direction => true,
            '--no-interaction' => true,
        ]);
        $input->setInteractive(false);

        self::assertSame(0, $application->run($input, new NullOutput()));
    }
}

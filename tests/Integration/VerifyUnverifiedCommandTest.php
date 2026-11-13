<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCompanyVerificationPlugin\Integration;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Tmonier\SyliusCompanyVerificationPlugin\FakeHttpClient;
use Tmonier\SyliusCompanyVerificationPlugin\Status\RegisterStatus;
use Tmonier\SyliusCompanyVerificationPlugin\Status\VatStatus;

final class VerifyUnverifiedCommandTest extends KernelTestCase
{
    use IntegrationTestTrait;

    public function testACompanyThatCouldNotBeVerifiedIsVerifiedAgainOnceTheServicesAreBack(): void
    {
        $company = $this->catalog()->company();
        $first = $this->latest($company);
        self::assertNotNull($first);
        self::assertSame(RegisterStatus::NotVerified, $first->getRegisterStatus());
        $this->http()->answer(FakeHttpClient::REGISTER_HOST, $this->activeRegister());
        $this->http()->answer(FakeHttpClient::VIES_HOST, $this->validVat());

        $tester = $this->executeCommand();

        self::assertStringContainsString('verification(s) requested', $tester->getDisplay());
        $second = $this->latest($company);
        self::assertNotNull($second);
        self::assertSame(RegisterStatus::Active, $second->getRegisterStatus());
        self::assertSame(VatStatus::Valid, $second->getVatStatus());
    }

    public function testACompanyAlreadyVerifiedIsLeftAlone(): void
    {
        $this->http()->answer(FakeHttpClient::REGISTER_HOST, $this->activeRegister());
        $this->http()->answer(FakeHttpClient::VIES_HOST, $this->validVat());
        $company = $this->catalog()->company();
        $verification = $this->latest($company);
        $requests = \count($this->http()->requests());

        $this->executeCommand();

        self::assertSame($verification, $this->latest($company));
        self::assertCount($requests, $this->http()->requests());
    }

    private function executeCommand(): CommandTester
    {
        $kernel = self::$kernel;
        self::assertNotNull($kernel);
        $application = new Application($kernel);
        $tester = new CommandTester($application->find('tmonier:company-verification:verify-unverified'));
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        return $tester;
    }
}

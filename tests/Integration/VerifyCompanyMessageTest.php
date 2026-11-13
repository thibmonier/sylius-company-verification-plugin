<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCompanyVerificationPlugin\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Tests\Tmonier\SyliusCompanyVerificationPlugin\FakeHttpClient;
use Tmonier\SyliusCompanyVerificationPlugin\Message\VerifyCompany;
use Tmonier\SyliusCompanyVerificationPlugin\Status\RegisterStatus;
use Tmonier\SyliusCompanyVerificationPlugin\Status\VatStatus;

/** The test application has no asynchronous transport: the bus handles the message at once. */
final class VerifyCompanyMessageTest extends KernelTestCase
{
    use IntegrationTestTrait;

    public function testTheMessageVerifiesTheCompany(): void
    {
        $company = $this->catalog()->company(null, null);
        $this->http()->answer(FakeHttpClient::REGISTER_HOST, $this->activeRegister());
        $this->http()->answer(FakeHttpClient::VIES_HOST, $this->validVat());
        $company->setVatNumber('FR40303265045');
        $company->setRegistrationNumber('30326504500011');
        $this->entityManager()->flush();

        $verification = $this->latest($company);

        self::assertNotNull($verification);
        self::assertSame(RegisterStatus::Active, $verification->getRegisterStatus());
        self::assertSame(VatStatus::Valid, $verification->getVatStatus());
    }

    public function testCreatingACompanyAsksForItsVerification(): void
    {
        $this->http()->answer(FakeHttpClient::REGISTER_HOST, $this->activeRegister());
        $this->http()->answer(FakeHttpClient::VIES_HOST, $this->validVat());

        $company = $this->catalog()->company();

        self::assertSame(RegisterStatus::Active, $this->latest($company)?->getRegisterStatus());
    }

    public function testChangingAnIdentifierAsksForANewVerification(): void
    {
        $company = $this->catalog()->company();
        $first = $this->latest($company);
        self::assertNotNull($first);

        $company->setVatNumber('FR41303265045');
        $this->entityManager()->flush();

        $second = $this->latest($company);
        self::assertNotNull($second);
        self::assertNotSame($first->getId(), $second->getId());
        self::assertSame('FR41303265045', $second->getVatNumber());
    }

    public function testChangingTheNameDoesNotAskForAVerification(): void
    {
        $company = $this->catalog()->company();
        $first = $this->latest($company);

        $company->setName('Acme renamed');
        $this->entityManager()->flush();

        self::assertSame($first, $this->latest($company));
    }

    public function testAMessageForADeletedCompanyIsIgnored(): void
    {
        $envelope = $this->bus()->dispatch(new VerifyCompany(999_999_999));

        self::assertSame([], $envelope->all(\Symfony\Component\Messenger\Stamp\ErrorDetailsStamp::class));
    }

    private function bus(): MessageBusInterface
    {
        $bus = self::getContainer()->get('sylius.command_bus');
        self::assertInstanceOf(MessageBusInterface::class, $bus);

        return $bus;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCompanyVerificationPlugin\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Tmonier\SyliusCompanyVerificationPlugin\FakeHttpClient;
use Tmonier\SyliusCompanyVerificationPlugin\Status\RegisterStatus;
use Tmonier\SyliusCompanyVerificationPlugin\Status\VatStatus;
use Tmonier\SyliusCompanyVerificationPlugin\Verification\CompanyVerifier;

final class CompanyVerifierTest extends KernelTestCase
{
    use IntegrationTestTrait;

    public function testAnActiveSiretAndAValidVatAreRecordedWithTheirSourceAndDate(): void
    {
        $this->http()->answer(FakeHttpClient::REGISTER_HOST, $this->activeRegister());
        $this->http()->answer(FakeHttpClient::VIES_HOST, $this->validVat());
        $company = $this->catalog()->company();

        $verification = $this->verifier()->verify($company);

        self::assertSame(RegisterStatus::Active, $verification->getRegisterStatus());
        self::assertSame('SODIMAS', $verification->getRegisterName());
        self::assertSame('11 RUE AMPERE 26600 PONT DE L ISERE', $verification->getRegisterAddress());
        self::assertSame(VatStatus::Valid, $verification->getVatStatus());
        self::assertSame('SA SODIMAS', $verification->getVatName());
        self::assertSame('recherche-entreprises.api.gouv.fr', $verification->getRegisterSource());
        self::assertSame('VIES', $verification->getVatSource());
        self::assertEqualsWithDelta(time(), $verification->getVerifiedAt()->getTimestamp(), 60);
        self::assertSame($verification, $this->latest($company));
    }

    public function testAClosedEstablishmentKeepsItsClosingDateAndNothingIsDecided(): void
    {
        $this->http()->answer(FakeHttpClient::REGISTER_HOST, $this->closedRegister('2024-03-31'));
        $this->http()->answer(FakeHttpClient::VIES_HOST, $this->validVat());
        $company = $this->catalog()->company();

        $verification = $this->verifier()->verify($company);

        self::assertSame(RegisterStatus::Closed, $verification->getRegisterStatus());
        self::assertSame('2024-03-31', $verification->getRegisterClosedAt()?->format('Y-m-d'));
        self::assertTrue($company->isEnabled(), 'The company is left as it is: the decision is the administrator\'s.');
    }

    public function testACompanyOutsideFranceOnlyGoesThroughVies(): void
    {
        $this->http()->answer(FakeHttpClient::VIES_HOST, ['valid' => true, 'name' => 'ACME NV', 'address' => 'RUE 1, BRUXELLES']);
        $company = $this->catalog()->company('BE0123456749', null);
        $before = \count($this->http()->requests());

        $verification = $this->verifier()->verify($company);

        self::assertSame(RegisterStatus::NotApplicable, $verification->getRegisterStatus());
        self::assertSame(VatStatus::Valid, $verification->getVatStatus());
        $hosts = array_map(static fn (array $request): string => (string) parse_url($request['url'], \PHP_URL_HOST), \array_slice($this->http()->requests(), $before));
        self::assertSame([FakeHttpClient::VIES_HOST], $hosts);
    }

    public function testAForeignRegistrationNumberIsNotLookedUpInTheFrenchRegister(): void
    {
        $this->http()->answer(FakeHttpClient::VIES_HOST, $this->validVat());
        $company = $this->catalog()->company('BE0123456749', '0123.456.749');

        self::assertSame(RegisterStatus::NotApplicable, $this->verifier()->verify($company)->getRegisterStatus());
        foreach ($this->http()->requests() as $request) {
            self::assertStringNotContainsString(FakeHttpClient::REGISTER_HOST, $request['url']);
        }
    }

    public function testAMalformedIdentifierIsNeverSentToAnExternalService(): void
    {
        $company = $this->catalog()->company('FR41303265045', '30326504500012');
        $before = \count($this->http()->requests());

        $verification = $this->verifier()->verify($company);

        self::assertSame(\count($this->http()->requests()), $before, 'No request for a malformed identifier.');
        self::assertSame(RegisterStatus::NotVerified, $verification->getRegisterStatus());
        self::assertSame(VatStatus::Invalid, $verification->getVatStatus());
    }

    public function testAnUnknownSirenIsNotFound(): void
    {
        $this->http()->answer(FakeHttpClient::REGISTER_HOST, ['results' => []]);
        $this->http()->answer(FakeHttpClient::VIES_HOST, ['valid' => false]);
        $company = $this->catalog()->company();

        $verification = $this->verifier()->verify($company);

        self::assertSame(RegisterStatus::NotFound, $verification->getRegisterStatus());
        self::assertSame(VatStatus::Invalid, $verification->getVatStatus());
    }

    public function testServicesThatDoNotAnswerGiveNotVerifiedAndTheRunIsStillRecorded(): void
    {
        $this->http()->answer(FakeHttpClient::REGISTER_HOST, new \RuntimeException('Timeout'));
        $this->http()->answer(FakeHttpClient::VIES_HOST, new \RuntimeException('Timeout'));
        $company = $this->catalog()->company();

        $verification = $this->verifier()->verify($company);

        self::assertSame(RegisterStatus::NotVerified, $verification->getRegisterStatus());
        self::assertSame(VatStatus::NotVerified, $verification->getVatStatus());
        self::assertNotNull($verification->getId());
    }

    public function testACompanyWithoutIdentifiersHasNothingToCheck(): void
    {
        $company = $this->catalog()->company(null, null);

        $verification = $this->verifier()->verify($company);

        self::assertSame(RegisterStatus::NotApplicable, $verification->getRegisterStatus());
        self::assertSame(VatStatus::NotApplicable, $verification->getVatStatus());
    }

    public function testEachRunIsKeptAndTheLatestOneIsTheCurrentState(): void
    {
        $this->http()->answer(FakeHttpClient::REGISTER_HOST, new \RuntimeException('Timeout'));
        $this->http()->answer(FakeHttpClient::VIES_HOST, new \RuntimeException('Timeout'));
        $company = $this->catalog()->company();
        $first = $this->verifier()->verify($company);

        $this->http()->answer(FakeHttpClient::REGISTER_HOST, $this->activeRegister());
        $this->http()->answer(FakeHttpClient::VIES_HOST, $this->validVat());
        $second = $this->verifier()->verify($company);

        self::assertNotSame($first->getId(), $second->getId());
        self::assertSame($second, $this->latest($company));
        self::assertSame(RegisterStatus::NotVerified, $first->getRegisterStatus(), 'The earlier run is not rewritten.');
    }

    private function verifier(): CompanyVerifier
    {
        $verifier = self::getContainer()->get('tmonier_company_verification.test.verifier');
        self::assertInstanceOf(CompanyVerifier::class, $verifier);

        return $verifier;
    }
}

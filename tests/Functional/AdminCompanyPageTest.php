<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCompanyVerificationPlugin\Functional;

use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Tmonier\SyliusCompanyVerificationPlugin\FakeHttpClient;
use Tests\Tmonier\SyliusCompanyVerificationPlugin\VerificationTestTrait;
use Tmonier\SyliusCompanyAccountsPlugin\Entity\CompanyInterface;
use Tmonier\SyliusCompanyVerificationPlugin\Status\RegisterStatus;
use Tmonier\SyliusCompanyVerificationPlugin\Status\VatStatus;

final class AdminCompanyPageTest extends WebTestCase
{
    use VerificationTestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->beginTestTransaction();
    }

    protected function tearDown(): void
    {
        $this->rollBackTestTransaction();
        parent::tearDown();
    }

    public function testTheAdministratorSeesTheStatusesInWordsWithTheNameAndAddressReturned(): void
    {
        $this->http()->answer(FakeHttpClient::REGISTER_HOST, $this->activeRegister());
        $this->http()->answer(FakeHttpClient::VIES_HOST, $this->validVat());
        $company = $this->catalog()->company();

        $crawler = $this->openCompany($company);

        self::assertResponseIsSuccessful();
        self::assertSame('SIREN/SIRET active', $this->text($crawler, '[data-test-tmonier-register-status="active"]'));
        self::assertSame('VAT valid (VIES)', $this->text($crawler, '[data-test-tmonier-vat-status="valid"]'));
        self::assertStringContainsString('SODIMAS, 11 RUE AMPERE 26600 PONT DE L ISERE', $this->text($crawler, '[data-test-tmonier-register-identity]'));
        self::assertStringContainsString('SA SODIMAS', $this->text($crawler, '[data-test-tmonier-vat-identity]'));
        self::assertStringContainsString('recherche-entreprises.api.gouv.fr', $this->text($crawler, '[data-test-tmonier-verification-source]'));
    }

    public function testAClosedEstablishmentIsShownWithItsClosingDate(): void
    {
        $this->http()->answer(FakeHttpClient::REGISTER_HOST, $this->closedRegister('2024-03-31'));
        $this->http()->answer(FakeHttpClient::VIES_HOST, $this->validVat());
        $company = $this->catalog()->company();

        $crawler = $this->openCompany($company);

        self::assertSame('Closed (2024-03-31)', $this->text($crawler, '[data-test-tmonier-register-status="closed"]'));
    }

    public function testAnUnreachableServiceIsSaidInWords(): void
    {
        $company = $this->catalog()->company();

        $crawler = $this->openCompany($company);

        self::assertSame('Not verified (service unavailable)', $this->text($crawler, '[data-test-tmonier-register-status="not_verified"]'));
        self::assertSame('VAT not verified (service unavailable)', $this->text($crawler, '[data-test-tmonier-vat-status="not_verified"]'));
    }

    public function testTheButtonVerifiesAgainAndShowsTheNewResult(): void
    {
        $company = $this->catalog()->company();
        $this->http()->answer(FakeHttpClient::REGISTER_HOST, $this->activeRegister());
        $this->http()->answer(FakeHttpClient::VIES_HOST, $this->validVat());
        $crawler = $this->openCompany($company);

        $this->client->submit($crawler->filter('[data-test-tmonier-verify-again]')->form());
        $this->client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-test-tmonier-register-status="active"]');
        $verification = $this->latest($company);
        self::assertNotNull($verification);
        self::assertSame(RegisterStatus::Active, $verification->getRegisterStatus());
        self::assertSame(VatStatus::Valid, $verification->getVatStatus());
    }

    public function testAPostWithoutAValidCsrfTokenIsRefused(): void
    {
        $company = $this->catalog()->company();
        $before = $this->latest($company);
        $this->loginAdmin();

        $this->client->request('POST', $this->url($company), ['_csrf_token' => 'forged']);

        self::assertResponseStatusCodeSame(403);
        self::assertSame($before, $this->latest($company));
    }

    public function testTheButtonIsNotAvailableToAnonymousVisitors(): void
    {
        $company = $this->catalog()->company();
        $this->client->request('POST', $this->url($company), ['_csrf_token' => 'whatever']);

        self::assertResponseRedirects();
        self::assertStringContainsString('login', (string) $this->client->getResponse()->headers->get('Location'));
    }

    private function text(Crawler $crawler, string $selector): string
    {
        self::assertCount(1, $crawler->filter($selector), $selector);

        return trim($crawler->filter($selector)->text());
    }

    private function openCompany(CompanyInterface $company): Crawler
    {
        $this->loginAdmin();

        return $this->client->request('GET', sprintf('/admin/companies/%d/edit', $company->getId()));
    }

    private function url(CompanyInterface $company): string
    {
        return sprintf('/admin/companies/%d/verification', $company->getId());
    }

    private function loginAdmin(): void
    {
        $admin = $this->entityManager()->getRepository(AdminUserInterface::class)->findOneBy(['username' => 'sylius']);
        self::assertInstanceOf(AdminUserInterface::class, $admin);
        $this->client->loginUser($admin, 'admin');
    }
}

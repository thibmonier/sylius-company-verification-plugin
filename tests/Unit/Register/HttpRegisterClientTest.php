<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCompanyVerificationPlugin\Unit\Register;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tmonier\SyliusCompanyVerificationPlugin\Register\HttpRegisterClient;
use Tmonier\SyliusCompanyVerificationPlugin\Register\RegisterResult;
use Tmonier\SyliusCompanyVerificationPlugin\Status\RegisterStatus;

final class HttpRegisterClientTest extends TestCase
{
    private const URL = 'https://register.test/search';

    /** @var list<array{string, string, array<mixed>}> */
    private array $requests = [];

    public function testAnActiveCompanyIsReturnedWithItsNameAndHeadOfficeAddress(): void
    {
        $result = $this->lookup($this->json(['results' => [$this->company('A', null)]]), '303265045');

        self::assertSame(RegisterStatus::Active, $result->status);
        self::assertSame('SODIMAS', $result->name);
        self::assertSame('11 RUE AMPERE 26600 PONT DE L ISERE', $result->address);
        self::assertNull($result->closedAt);
    }

    public function testAClosedCompanyCarriesItsClosingDate(): void
    {
        $result = $this->lookup($this->json(['results' => [$this->company('C', '2024-03-31')]]), '303265045');

        self::assertSame(RegisterStatus::Closed, $result->status);
        self::assertSame('2024-03-31', $result->closedAt?->format('Y-m-d'));
    }

    public function testASiretGivesTheStatusOfItsEstablishmentNotOfTheCompany(): void
    {
        $company = $this->company('A', null);
        $company['matching_etablissements'] = [['siret' => '30326504500029', 'adresse' => '3 RUE DU PORT 26000 VALENCE', 'etat_administratif' => 'F', 'date_fermeture' => '2023-12-31']];

        $result = $this->lookup($this->json(['results' => [$company]]), '30326504500029');

        self::assertSame(RegisterStatus::Closed, $result->status);
        self::assertSame('2023-12-31', $result->closedAt?->format('Y-m-d'));
        self::assertSame('3 RUE DU PORT 26000 VALENCE', $result->address);
    }

    public function testASiretOfTheHeadOfficeIsFoundWithoutAMatchingEstablishment(): void
    {
        $result = $this->lookup($this->json(['results' => [$this->company('A', null)]]), '30326504500011');

        self::assertSame(RegisterStatus::Active, $result->status);
    }

    public function testAnUnknownSiretOfAKnownCompanyIsNotFound(): void
    {
        $result = $this->lookup($this->json(['results' => [$this->company('A', null)]]), '30326504500037');

        self::assertSame(RegisterStatus::NotFound, $result->status);
    }

    public function testNoResultMeansNotFound(): void
    {
        self::assertSame(RegisterStatus::NotFound, $this->lookup($this->json(['results' => []]), '303265045')->status);
    }

    public function testAResultForAnotherSirenIsNotTakenForTheCompany(): void
    {
        $other = $this->company('A', null);
        $other['siren'] = '552100554';

        self::assertSame(RegisterStatus::NotFound, $this->lookup($this->json(['results' => [$other]]), '303265045')->status);
    }

    public function testAServerErrorIsNotVerified(): void
    {
        self::assertSame(RegisterStatus::NotVerified, $this->lookup(new MockResponse('oops', ['http_code' => 503]), '303265045')->status);
    }

    public function testATooManyRequestsAnswerIsNotVerified(): void
    {
        self::assertSame(RegisterStatus::NotVerified, $this->lookup(new MockResponse('{}', ['http_code' => 429]), '303265045')->status);
    }

    public function testATimeoutIsNotVerified(): void
    {
        $result = $this->lookup(new MockResponse(info: ['error' => 'Idle timeout reached']), '303265045');

        self::assertSame(RegisterStatus::NotVerified, $result->status);
        self::assertNull($result->name);
    }

    public function testAnAnswerThatIsNotJsonIsNotVerified(): void
    {
        self::assertSame(RegisterStatus::NotVerified, $this->lookup(new MockResponse('<html>maintenance</html>'), '303265045')->status);
    }

    public function testAnAnswerWithoutResultsKeyIsNotVerified(): void
    {
        self::assertSame(RegisterStatus::NotVerified, $this->lookup($this->json(['message' => 'error']), '303265045')->status);
    }

    public function testOnlyTheIdentifierIsSentToTheConfiguredHostWithATimeoutAndNoRedirect(): void
    {
        $this->lookup($this->json(['results' => []]), '303265045');

        self::assertCount(1, $this->requests);
        [$method, $url, $options] = $this->requests[0];
        self::assertSame('GET', $method);
        self::assertStringStartsWith(self::URL . '?q=303265045', $url);
        self::assertSame(5.0, $options['timeout']);
        self::assertSame(0, $options['max_redirects']);
    }

    private function lookup(MockResponse $response, string $identifier): RegisterResult
    {
        $http = new MockHttpClient(function (string $method, string $url, array $options) use ($response): MockResponse {
            $this->requests[] = [$method, $url, $options];

            return $response;
        });

        return (new HttpRegisterClient($http, self::URL, 5.0))->lookup($identifier);
    }

    /** @param array<string, mixed> $payload */
    private function json(array $payload): MockResponse
    {
        return new MockResponse(json_encode($payload, \JSON_THROW_ON_ERROR), ['response_headers' => ['content-type' => 'application/json']]);
    }

    /** @return array<string, mixed> */
    private function company(string $state, ?string $closedAt): array
    {
        return [
            'siren' => '303265045',
            'nom_complet' => 'SODIMAS',
            'etat_administratif' => $state,
            'date_fermeture' => $closedAt,
            'siege' => ['siret' => '30326504500011', 'adresse' => '11 RUE AMPERE 26600 PONT DE L ISERE', 'etat_administratif' => $state, 'date_fermeture' => $closedAt],
            'matching_etablissements' => [],
        ];
    }
}

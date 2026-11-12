<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCompanyVerificationPlugin\Unit\Vies;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tmonier\SyliusCompanyVerificationPlugin\Status\VatStatus;
use Tmonier\SyliusCompanyVerificationPlugin\Vies\HttpViesClient;
use Tmonier\SyliusCompanyVerificationPlugin\Vies\VatResult;

final class HttpViesClientTest extends TestCase
{
    private const URL = 'https://vies.test/check-vat-number';

    /** @var list<array{string, string, array<mixed>}> */
    private array $requests = [];

    public function testAValidNumberIsReturnedWithTheNameAndAddressOfTheTrader(): void
    {
        $result = $this->check($this->json(['valid' => true, 'name' => 'SA SODIMAS', 'address' => "11 RUE AMPERE\n26600 PONT DE L ISERE"]));

        self::assertSame(VatStatus::Valid, $result->status);
        self::assertSame('SA SODIMAS', $result->name);
        self::assertSame('11 RUE AMPERE, 26600 PONT DE L ISERE', $result->address);
    }

    public function testAnUnknownNumberIsInvalid(): void
    {
        self::assertSame(VatStatus::Invalid, $this->check($this->json(['valid' => false, 'name' => '---', 'address' => '---']))->status);
    }

    public function testPlaceholdersOfTheMemberStateAreNotShown(): void
    {
        $result = $this->check($this->json(['valid' => true, 'name' => '---', 'address' => '---']));

        self::assertSame(VatStatus::Valid, $result->status);
        self::assertNull($result->name);
        self::assertNull($result->address);
    }

    public function testAMemberStateThatIsDownIsNotTakenForAnInvalidNumber(): void
    {
        $result = $this->check($this->json(['actionSucceed' => false, 'errorWrappers' => [['error' => 'MS_UNAVAILABLE']]]));

        self::assertSame(VatStatus::NotVerified, $result->status);
    }

    public function testAServerErrorIsNotVerified(): void
    {
        self::assertSame(VatStatus::NotVerified, $this->check(new MockResponse('oops', ['http_code' => 500]))->status);
    }

    public function testATimeoutIsNotVerified(): void
    {
        self::assertSame(VatStatus::NotVerified, $this->check(new MockResponse(info: ['error' => 'Idle timeout reached']))->status);
    }

    public function testAnAnswerThatIsNotJsonIsNotVerified(): void
    {
        self::assertSame(VatStatus::NotVerified, $this->check(new MockResponse('<html></html>'))->status);
    }

    public function testOnlyTheCountryAndTheNumberAreSentToTheConfiguredHost(): void
    {
        $this->check($this->json(['valid' => true]));

        self::assertCount(1, $this->requests);
        [$method, $url, $options] = $this->requests[0];
        self::assertSame('POST', $method);
        self::assertSame(self::URL, $url);
        self::assertSame('{"countryCode":"FR","vatNumber":"40303265045"}', $options['body']);
        self::assertSame(5.0, $options['timeout']);
        self::assertSame(0, $options['max_redirects']);
    }

    private function check(MockResponse $response): VatResult
    {
        $http = new MockHttpClient(function (string $method, string $url, array $options) use ($response): MockResponse {
            $this->requests[] = [$method, $url, $options];

            return $response;
        });

        return (new HttpViesClient($http, self::URL, 5.0))->check('FR', '40303265045');
    }

    /** @param array<string, mixed> $payload */
    private function json(array $payload): MockResponse
    {
        return new MockResponse(json_encode($payload, \JSON_THROW_ON_ERROR), ['response_headers' => ['content-type' => 'application/json']]);
    }
}

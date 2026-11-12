<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Vies;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * REST API of the European Commission (VIES). The host comes from the configuration, the only
 * values sent are a country code and a VAT number already format-checked, and the raw response
 * is never kept.
 */
final class HttpViesClient implements ViesClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $checkUrl,
        private readonly float $timeout,
    ) {
    }

    public function check(string $countryCode, string $number): VatResult
    {
        try {
            $this->httpClient->request('POST', $this->checkUrl, [
                'json' => ['countryCode' => $countryCode, 'vatNumber' => $number],
                'timeout' => $this->timeout,
                'max_duration' => $this->timeout,
                'max_redirects' => 0,
            ])->getStatusCode();
        } catch (ExceptionInterface) {
            // The answer is not read yet.
        }

        return VatResult::unavailable();
    }
}

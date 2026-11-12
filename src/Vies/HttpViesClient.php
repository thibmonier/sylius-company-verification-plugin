<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Vies;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tmonier\SyliusCompanyVerificationPlugin\Status\VatStatus;

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
            $response = $this->httpClient->request('POST', $this->checkUrl, [
                'json' => ['countryCode' => $countryCode, 'vatNumber' => $number],
                'timeout' => $this->timeout,
                'max_duration' => $this->timeout,
                'max_redirects' => 0,
                'headers' => ['Accept' => 'application/json'],
            ]);

            return 200 === $response->getStatusCode() ? $this->interpret($response->toArray(false)) : VatResult::unavailable();
        } catch (ExceptionInterface|\JsonException) {
            return VatResult::unavailable();
        }
    }

    /** @param array<array-key, mixed> $payload */
    private function interpret(array $payload): VatResult
    {
        // An error of the service (MS_UNAVAILABLE, TIMEOUT...) answers with an actionSucceed flag or
        // an errorWrappers list and no "valid": that is not an invalid number.
        if (!isset($payload['valid']) || !\is_bool($payload['valid'])) {
            return VatResult::unavailable();
        }

        if (!$payload['valid']) {
            return new VatResult(VatStatus::Invalid);
        }

        return new VatResult(VatStatus::Valid, $this->text($payload['name'] ?? null), $this->text($payload['address'] ?? null));
    }

    private function text(mixed $value): ?string
    {
        // VIES answers "---" when the member state does not give the data.
        return \is_string($value) && '' !== trim($value, " -\n") ? mb_substr(trim(str_replace("\n", ', ', $value)), 0, 255) : null;
    }
}

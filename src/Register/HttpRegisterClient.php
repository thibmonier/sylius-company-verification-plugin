<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Register;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * API "Recherche d'entreprises" (data.gouv.fr). The host comes from the configuration, the only
 * value sent is a SIREN/SIRET already format-checked, and the raw response is never kept.
 */
final class HttpRegisterClient implements RegisterClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $searchUrl,
        private readonly float $timeout,
    ) {
    }

    public function lookup(string $identifier): RegisterResult
    {
        try {
            $this->httpClient->request('GET', $this->searchUrl, [
                'query' => ['q' => $identifier, 'per_page' => 1],
                'timeout' => $this->timeout,
                'max_duration' => $this->timeout,
                'max_redirects' => 0,
            ])->getStatusCode();
        } catch (ExceptionInterface) {
            // The answer is not read yet.
        }

        return RegisterResult::unavailable();
    }
}

<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Register;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tmonier\SyliusCompanyVerificationPlugin\Status\RegisterStatus;

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
            $response = $this->httpClient->request('GET', $this->searchUrl, [
                'query' => ['q' => $identifier, 'per_page' => 1],
                'timeout' => $this->timeout,
                'max_duration' => $this->timeout,
                'max_redirects' => 0,
                'headers' => ['Accept' => 'application/json'],
            ]);

            return 200 === $response->getStatusCode()
                ? $this->interpret($identifier, $response->toArray(false))
                : RegisterResult::unavailable();
        } catch (ExceptionInterface|\JsonException) {
            return RegisterResult::unavailable();
        }
    }

    /** @param array<array-key, mixed> $payload */
    private function interpret(string $identifier, array $payload): RegisterResult
    {
        $company = $this->findCompany($payload, substr($identifier, 0, 9));
        if (null === $company) {
            return \is_array($payload['results'] ?? null) ? new RegisterResult(RegisterStatus::NotFound) : RegisterResult::unavailable();
        }

        $unit = 14 === \strlen($identifier) ? $this->findEstablishment($company, $identifier) : $company;
        if (null === $unit) {
            return new RegisterResult(RegisterStatus::NotFound, $this->text($company['nom_complet'] ?? null));
        }

        $address = $this->text($this->section($unit, 'siege')['adresse'] ?? $unit['adresse'] ?? null);

        return match ($unit['etat_administratif'] ?? null) {
            'A' => new RegisterResult(RegisterStatus::Active, $this->text($company['nom_complet'] ?? null), $address),
            'C', 'F' => new RegisterResult(RegisterStatus::Closed, $this->text($company['nom_complet'] ?? null), $address, $this->date($unit['date_fermeture'] ?? null)),
            default => RegisterResult::unavailable(),
        };
    }

    /**
     * @param array<array-key, mixed> $payload
     *
     * @return array<array-key, mixed>|null
     */
    private function findCompany(array $payload, string $siren): ?array
    {
        $results = $payload['results'] ?? null;
        if (!\is_array($results)) {
            return null;
        }

        foreach ($results as $result) {
            if (\is_array($result) && ($result['siren'] ?? null) === $siren) {
                return $result;
            }
        }

        return null;
    }

    /**
     * @param array<array-key, mixed> $company
     *
     * @return array<array-key, mixed>|null
     */
    private function findEstablishment(array $company, string $siret): ?array
    {
        $candidates = $this->list($company['matching_etablissements'] ?? null);
        $candidates[] = $this->section($company, 'siege');

        foreach ($candidates as $candidate) {
            if (($candidate['siret'] ?? null) === $siret) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<array-key, mixed>
     */
    private function section(array $data, string $key): array
    {
        return \is_array($data[$key] ?? null) ? $data[$key] : [];
    }

    /** @return list<array<array-key, mixed>> */
    private function list(mixed $value): array
    {
        return \is_array($value) ? array_values(array_filter($value, \is_array(...))) : [];
    }

    private function text(mixed $value): ?string
    {
        return \is_string($value) && '' !== $value ? mb_substr($value, 0, 255) : null;
    }

    private function date(mixed $value): ?\DateTimeImmutable
    {
        if (!\is_string($value)) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return false === $date ? null : $date;
    }
}

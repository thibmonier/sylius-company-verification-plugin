<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCompanyVerificationPlugin;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/** Answers by host what the test queued, a timeout otherwise; records every request. */
final class FakeHttpClient implements HttpClientInterface
{
    public const REGISTER_HOST = 'recherche-entreprises.api.gouv.fr';

    public const VIES_HOST = 'ec.europa.eu';

    /** @var array<string, array<string, mixed>|\Throwable> */
    private array $answers = [];

    /** @var list<array{method: string, url: string, options: array<string, mixed>}> */
    private array $requests = [];

    /** @param array<string, mixed>|\Throwable $answer a JSON body, or an exception for an unreachable service */
    public function answer(string $host, array|\Throwable $answer): void
    {
        $this->answers[$host] = $answer;
    }

    /** @return list<array{method: string, url: string, options: array<string, mixed>}> */
    public function requests(): array
    {
        return $this->requests;
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $this->requests[] = ['method' => $method, 'url' => $url, 'options' => $options];

        return (new MockHttpClient($this->respond(...)))->request($method, $url, $options);
    }

    private function respond(string $method, string $url): MockResponse
    {
        $answer = $this->answers[(string) parse_url($url, \PHP_URL_HOST)] ?? new \RuntimeException('Timeout');

        if ($answer instanceof \Throwable) {
            return new MockResponse(info: ['error' => $answer->getMessage()]);
        }

        return new MockResponse(json_encode($answer, \JSON_THROW_ON_ERROR), ['response_headers' => ['content-type' => 'application/json']]);
    }

    public function stream(ResponseInterface|iterable $responses, ?float $timeout = null): ResponseStreamInterface
    {
        throw new \LogicException('Not used by the plugin.');
    }

    public function withOptions(array $options): static
    {
        return $this;
    }
}

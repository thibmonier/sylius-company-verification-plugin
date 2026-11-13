<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Tests\Tmonier\SyliusCompanyVerificationPlugin\FakeHttpClient;

/*
 * Test application only: no call leaves the test process. The HTTP client of the plugin is
 * replaced by a fake that answers what the test queued and records every request.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->set('tmonier_company_verification.test.http_client', FakeHttpClient::class)
        ->decorate('tmonier_company_verification.http_client')
        ->public()
    ;
    $services->alias('tmonier_company_verification.test.verifier', 'tmonier_company_verification.verifier')->public();
    $services->alias('tmonier_company_verification.test.repository', 'tmonier_company_verification.repository')->public();
    $services->alias('tmonier_company_verification.test.client.register', 'tmonier_company_verification.client.register')->public();
    $services->alias('tmonier_company_verification.test.client.vies', 'tmonier_company_verification.client.vies')->public();
};

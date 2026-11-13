<?php

declare(strict_types=1);

use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Tmonier\SyliusCompanyVerificationPlugin\Command\VerifyUnverifiedCommand;
use Tmonier\SyliusCompanyVerificationPlugin\Controller\VerifyAgainController;
use Tmonier\SyliusCompanyVerificationPlugin\Doctrine\CompanyIdentifiersChangeListener;
use Tmonier\SyliusCompanyVerificationPlugin\Message\VerifyCompanyHandler;
use Tmonier\SyliusCompanyVerificationPlugin\Register\HttpRegisterClient;
use Tmonier\SyliusCompanyVerificationPlugin\Twig\VerificationExtension;
use Tmonier\SyliusCompanyVerificationPlugin\Verification\CompanyVerifier;
use Tmonier\SyliusCompanyVerificationPlugin\Verification\DoctrineVerificationRepository;
use Tmonier\SyliusCompanyVerificationPlugin\Vies\HttpViesClient;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->alias('tmonier_company_verification.http_client', 'http_client');

    $services->set('tmonier_company_verification.client.register', HttpRegisterClient::class)
        ->args([service('tmonier_company_verification.http_client'), param('tmonier_company_verification.register_url'), param('tmonier_company_verification.timeout')])
    ;

    $services->set('tmonier_company_verification.client.vies', HttpViesClient::class)
        ->args([service('tmonier_company_verification.http_client'), param('tmonier_company_verification.vies_url'), param('tmonier_company_verification.timeout')])
    ;

    $services->set('tmonier_company_verification.repository', DoctrineVerificationRepository::class)
        ->args([service('doctrine.orm.entity_manager'), param('tmonier_company_accounts.model.company.class')])
    ;

    $services->set('tmonier_company_verification.verifier', CompanyVerifier::class)
        ->args([
            service('tmonier_company_verification.client.register'),
            service('tmonier_company_verification.client.vies'),
            service('tmonier_company_verification.repository'),
            service('clock'),
        ])
    ;

    $services->set('tmonier_company_verification.message_handler.verify_company', VerifyCompanyHandler::class)
        ->args([service('tmonier_company_accounts.repository.company'), service('tmonier_company_verification.verifier')])
        ->tag('messenger.message_handler')
    ;

    $services->set('tmonier_company_verification.listener.identifiers_change', CompanyIdentifiersChangeListener::class)
        ->args([service('sylius.command_bus')])
        ->tag('doctrine.event_listener', ['event' => Events::onFlush])
        ->tag('doctrine.event_listener', ['event' => Events::postFlush])
    ;

    $services->set('tmonier_company_verification.command.verify_unverified', VerifyUnverifiedCommand::class)
        ->args([service('tmonier_company_verification.repository'), service('sylius.command_bus')])
        ->tag('console.command')
    ;

    $services->set('tmonier_company_verification.controller.verify_again', VerifyAgainController::class)
        ->args([
            service('security.authorization_checker'),
            service('security.csrf.token_manager'),
            service('tmonier_company_accounts.repository.company'),
            service('tmonier_company_verification.verifier'),
            service('router'),
            service('translator'),
        ])
        ->public()
        ->tag('controller.service_arguments')
    ;

    $services->set('tmonier_company_verification.twig.verification', VerificationExtension::class)
        ->args([service('tmonier_company_verification.repository')])
        ->tag('twig.extension')
    ;
};

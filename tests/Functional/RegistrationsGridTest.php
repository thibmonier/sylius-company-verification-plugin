<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCompanyVerificationPlugin\Functional;

use Sylius\Component\Grid\Provider\GridProviderInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tmonier\SyliusRegistrationApprovalPlugin\TmonierSyliusRegistrationApprovalPlugin;

/**
 * Optional integration: runs only when tmonier/sylius-registration-approval-plugin is installed,
 * which needs Sylius 2.3 (the plugin itself supports Sylius 2.2).
 */
final class RegistrationsGridTest extends WebTestCase
{
    public function testTheBadgeColumnIsAddedToTheRegistrationsGridWhenThePluginIsInstalled(): void
    {
        if (!class_exists(TmonierSyliusRegistrationApprovalPlugin::class)) {
            self::markTestSkipped('tmonier/sylius-registration-approval-plugin is not installed.');
        }

        self::bootKernel();
        $provider = self::getContainer()->get(GridProviderInterface::class);
        self::assertInstanceOf(GridProviderInterface::class, $provider);
        $definition = $provider->get('tmonier_registration_approval_admin');

        self::assertArrayHasKey('companyVerification', $definition->getFields());
    }
}

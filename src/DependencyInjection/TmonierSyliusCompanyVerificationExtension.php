<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\DependencyInjection;

use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class TmonierSyliusCompanyVerificationExtension extends Extension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{register_url: string, vies_url: string, timeout: float} $config */
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('tmonier_company_verification.register_url', $config['register_url']);
        $container->setParameter('tmonier_company_verification.vies_url', $config['vies_url']);
        $container->setParameter('tmonier_company_verification.timeout', $config['timeout']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'TmonierSyliusCompanyVerificationPlugin' => [
                        'type' => 'xml',
                        'dir' => 'config/doctrine',
                        'prefix' => 'Tmonier\SyliusCompanyVerificationPlugin\Entity',
                        'is_bundle' => true,
                    ],
                ],
            ],
        ]);

        // Optional: the badge also appears in Customers > Registrations when the plugin is installed.
        if ($container->hasExtension('tmonier_sylius_registration_approval')) {
            $container->prependExtensionConfig('sylius_grid', [
                'grids' => [
                    'tmonier_registration_approval_admin' => [
                        'fields' => [
                            'companyVerification' => [
                                'type' => 'twig',
                                'label' => 'tmonier_company_verification.ui.identity',
                                'path' => '.',
                                'position' => 200,
                                'options' => ['template' => '@TmonierSyliusCompanyVerificationPlugin/admin/verification/grid/registration.html.twig'],
                            ],
                        ],
                    ],
                ],
            ]);
        }

        $this->prependDoctrineMigrations($container);
    }

    public function getAlias(): string
    {
        return 'tmonier_sylius_company_verification';
    }

    protected function getMigrationsNamespace(): string
    {
        return 'Tmonier\SyliusCompanyVerificationPlugin\Migrations';
    }

    protected function getMigrationsDirectory(): string
    {
        return '@TmonierSyliusCompanyVerificationPlugin/src/Migrations';
    }

    protected function getNamespacesOfMigrationsExecutedBefore(): array
    {
        return [
            'Sylius\Bundle\CoreBundle\Migrations',
            'Tmonier\SyliusCompanyAccountsPlugin\Migrations',
        ];
    }
}

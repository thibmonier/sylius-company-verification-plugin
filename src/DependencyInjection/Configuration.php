<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('tmonier_sylius_company_verification');

        $treeBuilder->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('register_url')
                    ->info('Search endpoint of the French business register (fixed host, never taken from a user input).')
                    ->defaultValue('https://recherche-entreprises.api.gouv.fr/search')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('vies_url')
                    ->info('VAT number check endpoint of the VIES REST API of the European Commission.')
                    ->defaultValue('https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number')
                    ->cannotBeEmpty()
                ->end()
                ->floatNode('timeout')
                    ->info('Seconds before a service is given up (request and total duration). VIES often takes 7-8 s for France; the check runs in the background.')
                    ->defaultValue(15.0)
                    ->min(0.5)
                    ->max(30.0)
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}

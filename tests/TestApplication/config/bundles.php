<?php

$bundles = [
    Tmonier\SyliusOrderHoldPlugin\TmonierSyliusOrderHoldPlugin::class => ['all' => true],
    Tmonier\SyliusCompanyAccountsPlugin\TmonierSyliusCompanyAccountsPlugin::class => ['all' => true],
    Tmonier\SyliusCompanyVerificationPlugin\TmonierSyliusCompanyVerificationPlugin::class => ['all' => true],
];

// Optional companion (Sylius 2.3 only, so not required by the plugin): the badge of the registrations grid.
if (class_exists(Tmonier\SyliusRegistrationApprovalPlugin\TmonierSyliusRegistrationApprovalPlugin::class)) {
    $bundles[Tmonier\SyliusRegistrationApprovalPlugin\TmonierSyliusRegistrationApprovalPlugin::class] = ['all' => true];
}

return $bundles;

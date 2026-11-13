<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Vies;

interface ViesClientInterface
{
    /**
     * @param string $countryCode two letters, already format-checked
     * @param string $number      the number without the country prefix, already format-checked
     */
    public function check(string $countryCode, string $number): VatResult;
}

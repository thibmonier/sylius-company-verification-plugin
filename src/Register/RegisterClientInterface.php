<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Register;

interface RegisterClientInterface
{
    /**
     * @param string $identifier a valid SIREN (9 digits) or SIRET (14 digits); never a free text
     */
    public function lookup(string $identifier): RegisterResult;
}

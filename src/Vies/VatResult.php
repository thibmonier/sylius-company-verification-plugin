<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Vies;

use Tmonier\SyliusCompanyVerificationPlugin\Status\VatStatus;

final class VatResult
{
    public function __construct(
        public readonly VatStatus $status,
        public readonly ?string $name = null,
        public readonly ?string $address = null,
    ) {
    }

    public static function unavailable(): self
    {
        return new self(VatStatus::NotVerified);
    }
}

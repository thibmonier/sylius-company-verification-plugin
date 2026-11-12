<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Register;

use Tmonier\SyliusCompanyVerificationPlugin\Status\RegisterStatus;

final class RegisterResult
{
    public function __construct(
        public readonly RegisterStatus $status,
        public readonly ?string $name = null,
        public readonly ?string $address = null,
        public readonly ?\DateTimeImmutable $closedAt = null,
    ) {
    }

    public static function unavailable(): self
    {
        return new self(RegisterStatus::NotVerified);
    }
}

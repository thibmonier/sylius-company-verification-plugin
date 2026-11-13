<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCompanyVerificationPlugin\Integration;

use Tests\Tmonier\SyliusCompanyVerificationPlugin\VerificationTestTrait;

trait IntegrationTestTrait
{
    use VerificationTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->beginTestTransaction();
    }

    protected function tearDown(): void
    {
        $this->rollBackTestTransaction();
        parent::tearDown();
    }
}

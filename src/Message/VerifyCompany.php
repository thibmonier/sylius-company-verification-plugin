<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Message;

/** Route it to an asynchronous transport: the verification calls two external services. */
final class VerifyCompany
{
    public function __construct(public readonly int $companyId)
    {
    }
}

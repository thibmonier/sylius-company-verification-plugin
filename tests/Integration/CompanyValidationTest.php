<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCompanyVerificationPlugin\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** The format is checked by the validator of the company, with no call to an external service. */
final class CompanyValidationTest extends KernelTestCase
{
    use IntegrationTestTrait;

    public function testASiretWithAWrongKeyIsRefused(): void
    {
        $paths = $this->violationPaths('FR40303265045', '30326504500012');

        self::assertSame(['registrationNumber'], $paths);
    }

    public function testAWrongFrenchVatKeyIsRefused(): void
    {
        self::assertSame(['vatNumber'], $this->violationPaths('FR41303265045', '30326504500011'));
    }

    public function testValidIdentifiersAreAccepted(): void
    {
        self::assertSame([], $this->violationPaths('FR 40 303 265 045', '303 265 045 00011'));
    }

    public function testABelgianCompanyIsAcceptedWithItsOwnRegistrationNumber(): void
    {
        self::assertSame([], $this->violationPaths('BE0123456749', '0123.456.749'));
    }

    public function testIdentifiersAreOptional(): void
    {
        self::assertSame([], $this->violationPaths(null, null));
    }

    public function testNoRequestLeavesTheProcessWhileValidating(): void
    {
        $before = \count($this->http()->requests());

        $this->violationPaths('FR41303265045', '30326504500012');

        self::assertCount($before, $this->http()->requests());
    }

    /** @return list<string> */
    private function violationPaths(?string $vatNumber, ?string $registrationNumber): array
    {
        $company = $this->catalog()->company(null, null);
        $company->setVatNumber($vatNumber);
        $company->setRegistrationNumber($registrationNumber);

        $validator = self::getContainer()->get('validator');
        self::assertInstanceOf(ValidatorInterface::class, $validator);

        $paths = [];
        foreach ($validator->validate($company, null, ['sylius']) as $violation) {
            $paths[] = $violation->getPropertyPath();
        }

        return $paths;
    }
}

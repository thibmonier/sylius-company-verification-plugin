<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCompanyVerificationPlugin\Unit\Identifier;

use PHPUnit\Framework\TestCase;
use Tmonier\SyliusCompanyVerificationPlugin\Identifier\CompanyIdentifiers;

final class CompanyIdentifiersTest extends TestCase
{
    public function testSpacesDotsAndHyphensAreIgnoredAndCaseIsFolded(): void
    {
        $identifiers = CompanyIdentifiers::of('fr 40.303-265 045', '303 265 045 00011');

        self::assertSame('FR40303265045', $identifiers->vatNumber());
        self::assertSame('30326504500011', $identifiers->registrationNumber());
        self::assertSame('FR', $identifiers->vatCountryCode());
        self::assertSame('40303265045', $identifiers->vatNumberWithoutCountry());
    }

    public function testBlankValuesBecomeNull(): void
    {
        $identifiers = CompanyIdentifiers::of('  ', '');

        self::assertNull($identifiers->vatNumber());
        self::assertNull($identifiers->registrationNumber());
        self::assertNull($identifiers->vatCountryCode());
        self::assertFalse($identifiers->hasFrenchRegistrationNumber());
    }

    public function testARegistrationNumberWithoutVatNumberIsFrench(): void
    {
        self::assertTrue(CompanyIdentifiers::of(null, '303265045')->hasFrenchRegistrationNumber());
    }

    public function testARegistrationNumberNextToAFrenchVatNumberIsFrench(): void
    {
        self::assertTrue(CompanyIdentifiers::of('FR40303265045', '303265045')->hasFrenchRegistrationNumber());
    }

    public function testARegistrationNumberNextToAForeignVatNumberIsNotFrench(): void
    {
        self::assertFalse(CompanyIdentifiers::of('BE0123456749', '0123.456.749')->hasFrenchRegistrationNumber());
    }

    public function testRegistrationFormatAcceptsASirenAndASiretOnly(): void
    {
        self::assertTrue(CompanyIdentifiers::of(null, '303265045')->isRegistrationFormatValid());
        self::assertTrue(CompanyIdentifiers::of(null, '30326504500011')->isRegistrationFormatValid());
        self::assertFalse(CompanyIdentifiers::of(null, '30326504500012')->isRegistrationFormatValid());
        self::assertFalse(CompanyIdentifiers::of(null, '3032650450')->isRegistrationFormatValid());
    }
}

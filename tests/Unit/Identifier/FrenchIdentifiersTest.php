<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCompanyVerificationPlugin\Unit\Identifier;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tmonier\SyliusCompanyVerificationPlugin\Identifier\FrenchIdentifiers;

final class FrenchIdentifiersTest extends TestCase
{
    /** @return iterable<string, array{string, bool}> */
    public static function sirens(): iterable
    {
        yield 'valid' => ['303265045', true];
        yield 'wrong key' => ['303265046', false];
        yield 'too short' => ['30326504', false];
        yield 'letters' => ['30326504A', false];
    }

    #[DataProvider('sirens')]
    public function testSiren(string $siren, bool $expected): void
    {
        self::assertSame($expected, FrenchIdentifiers::isValidSiren($siren));
    }

    /** @return iterable<string, array{string, bool}> */
    public static function sirets(): iterable
    {
        yield 'valid' => ['30326504500011', true];
        yield 'wrong key' => ['30326504500012', false];
        yield 'wrong length' => ['3032650450001', false];
        yield 'La Poste head office' => ['35600000000048', true];
        yield 'La Poste digit sum rule' => ['35600000000055', true];
        yield 'La Poste neither rule' => ['35600000000057', false];
    }

    #[DataProvider('sirets')]
    public function testSiret(string $siret, bool $expected): void
    {
        self::assertSame($expected, FrenchIdentifiers::isValidSiret($siret));
    }

    /** @return iterable<string, array{string, bool}> */
    public static function vatNumbers(): iterable
    {
        yield 'French, numeric key' => ['FR40303265045', true];
        yield 'French, wrong key' => ['FR41303265045', false];
        yield 'French, alphanumeric key' => ['FRA1303265045', true];
        yield 'Belgian' => ['BE0123456749', true];
        yield 'no country' => ['0123456749', false];
        yield 'too short' => ['BE1', false];
    }

    #[DataProvider('vatNumbers')]
    public function testVatNumber(string $vatNumber, bool $expected): void
    {
        self::assertSame($expected, FrenchIdentifiers::isValidVatNumber($vatNumber));
    }
}

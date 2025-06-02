<?php

declare(strict_types=1);

namespace BankAccountPayment\Tests\Unit\Domain\ValueObject;

use BankAccountPayment\Domain\ValueObject\Currency;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CurrencyTest extends TestCase
{
    public function testItBasicFunctionalityForValidCurrencyCode(): void
    {
        // GIVEN
        $code = 'pln';

        // WHEN
        $actual = new Currency($code);

        // THEN
        self::assertSame('PLN', $actual->toString());
    }

    public function testItValueObjectIsStringable(): void
    {
        // GIVEN
        $code = 'USD';

        // WHEN
        $actual = new Currency($code);

        // THEN
        self::assertSame($code, (string) $actual);
    }

    public static function invalidIdDataProvider(): iterable
    {
        yield 'code is empty string' => [
            'code' => '',
            'expectedExceptionMessage' => 'Currency code must be exactly 3 characters long.',
        ];

        yield 'code is not 2 chars string' => [
            'code' => 'US',
            'expectedExceptionMessage' => 'Currency code must be exactly 3 characters long.',
        ];

        yield 'code is not 4 chars string' => [
            'code' => 'USDX',
            'expectedExceptionMessage' => 'Currency code must be exactly 3 characters long.',
        ];

        yield 'code is not valid ISO currency code' => [
            'code' => 'XYZ',
            'expectedExceptionMessage' => 'Currency "XYZ" is not valid ISO 4217 currency code.',
        ];
    }

    #[DataProvider(methodName: 'invalidIdDataProvider')]
    public function testItThrowsExceptionWhenCurrencyCodeIsNotValid(
        string $code,
        string $expectedExceptionMessage
    ): void {
        // EXPECT
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage($expectedExceptionMessage);

        // WHEN
        new Currency($code);
    }

    public function testItReturnsTrueForTwoEqualCurrencyCodes(): void
    {
        // GIVEN
        $code = 'EUR';
        $other = 'eur';

        $voUnderTest = new Currency($code);
        $otherVo = new Currency($other);

        // WHEN
        $actual = $voUnderTest->isEqual($otherVo);

        // THEN
        self::assertTrue($actual);
    }

    public function testItReturnsFalseForTwoDifferentCurrencyCodes(): void
    {
        // GIVEN
        $code = 'EUR';
        $other = 'PLN';

        $voUnderTest = new Currency($code);
        $otherVo = new Currency($other);

        // WHEN
        $actual = $voUnderTest->isEqual($otherVo);

        // THEN
        self::assertFalse($actual);
    }
}

<?php

declare(strict_types=1);

namespace BankAccountPayment\Tests\Unit\Domain\ValueObject;

use BankAccountPayment\Domain\ValueObject\Currency;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AmountTest extends TestCase
{
    public function testItBasicFunctionalityForAmount(): void
    {
        // GIVEN
        $amount = 100.50;
        $currency = new Currency('PLN');
        
        // WHEN
        $actual = new Amount($amount, $currency);
        
        // THEN
        self::assertSame(100.50, $actual->toFloat());
        self::assertSame('PLN', $actual->currency()->toString());
        self::assertSame('100.50 PLN', $actual->toString());
    }

    public function testItValueObjectIsStringable(): void
    {
        // GIVEN
        $amount = 88.55;
        $currency = new Currency('USD');

        // WHEN
        $actual = new Amount($amount, $currency);

        // THEN
        self::assertSame('88.55 USD', (string) $actual);
    }

    public function testItThrowsExceptionWhenAmountIsNegative(): void
    {
        // EXPECT
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Amount cannot be negative.');

        // GIVEN
        $amount = -8.23;
        $currency = new Currency('PLN');

        // WHEN
        new Amount($amount, $currency);
    }

    public static function isEqualDataProvider(): iterable
    {
        yield 'amounts are equal and currencies are the same' => [
            'baseAmount' => 250.55,
            'baseCurrency' => 'PLN',
            'otherAmount' => 250.55,
            'otherCurrency' => 'PLN',
            'expected' => true,
        ];

        yield 'amounts are not equal and currencies are the same' => [
            'baseAmount' => 100.00,
            'baseCurrency' => 'PLN',
            'otherAmount' => 100.01,
            'otherCurrency' => 'PLN',
            'expected' => false,
        ];

        yield 'amounts are equal but currencies are not the same' => [
            'baseAmount' => 250.55,
            'baseCurrency' => 'PLN',
            'otherAmount' => 250.55,
            'otherCurrency' => 'USD',
            'expected' => false,
        ];

        yield 'amounts are equal and currencies are not the same' => [
            'baseAmount' => 500.00,
            'baseCurrency' => 'PLN',
            'otherAmount' => 800.00,
            'otherCurrency' => 'USD',
            'expected' => false,
        ];
    }

    #[DataProvider(methodName: 'isEqualDataProvider')]
    public function testItReturnsTrueForTwoEqualAmounts(
        float $baseAmount,
        string $baseCurrency,
        float $otherAmount,
        string $otherCurrency,
        bool $expected,
    ): void {
        // GIVEN
        $baseCurrency = new Currency($baseCurrency);
        $otherCurrency = new Currency($otherCurrency);

        $baseAmount = new Amount($baseAmount, $baseCurrency);
        $otherAmount = new Amount($otherAmount, $otherCurrency);

        // WHEN
        $actual = $baseAmount->isEqual($otherAmount);

        // THEN
        self::assertSame($expected, $actual);
    }

    public static function isGreaterThanOrEqualDataProvider(): iterable
    {
        yield 'amounts are equal' => [
            'baseAmount' => 250.55,
            'otherAmount' => 250.55,
            'expected' => true,
        ];

        yield 'the source amount is greater than other amount' => [
            'baseAmount' => 250.56,
            'otherAmount' => 250.55,
            'expected' => true,
        ];
    }

    #[DataProvider(methodName: 'isGreaterThanOrEqualDataProvider')]
    public function testItReturnsTrueForWhenBaseAmountIsGreaterThanOrEqualOtherAmount(
        float $baseAmount,
        float $otherAmount,
        bool $expected,
    ): void {
        // GIVEN
        $currency = new Currency('PLN');

        $baseAmount = new Amount($baseAmount, $currency);
        $otherAmount = new Amount($otherAmount, $currency);

        // WHEN
        $actual = $baseAmount->isGreaterThanOrEqual($otherAmount);

        // THEN
        self::assertSame($expected, $actual);
    }

    public function testItAddsAmount(): void
    {
        // GIVEN
        $amountUnderTest = new Amount(100.50, new Currency('PLN'));
        $amountToAdd = new Amount(50.00, new Currency('PLN'));

        // WHEN
        $actual = $amountUnderTest->add($amountToAdd);

        // THEN
        self::assertSame(150.5, $actual->toFloat());
    }

    public function testItThrowsExceptionWhenAmountToAddHasDifferentCurrency(): void
    {
        // EXPECT
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Amounts are not in the same currency.');;

        // GIVEN
        $amountUnderTest = new Amount(100.50, new Currency('PLN'));
        $amountToAdd = new Amount(50.00, new Currency('EUR'));

        // WHEN
        $actual = $amountUnderTest->add($amountToAdd);

        // THEN
        self::assertSame(100.50, $actual->toFloat());
    }

    public function testItSubtractsAmount(): void
    {
        // GIVEN
        $amountUnderTest = new Amount(100.50, new Currency('PLN'));
        $amountToAdd = new Amount(50.00, new Currency('PLN'));

        // WHEN
        $actual = $amountUnderTest->subtract($amountToAdd);

        // THEN
        self::assertSame(50.5, $actual->toFloat());
    }

    public function testItThrowsExceptionWhenAmountToSubtractHasDifferentCurrency(): void
    {
        // EXPECT
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Amounts are not in the same currency.');;

        // GIVEN
        $amountUnderTest = new Amount(100.50, new Currency('PLN'));
        $amountToAdd = new Amount(50.00, new Currency('EUR'));

        // WHEN
        $actual = $amountUnderTest->subtract($amountToAdd);

        // THEN
        self::assertSame(100.50, $actual->toFloat());
    }

    public function testItMultiplyAmount(): void
    {
        // GIVEN
        $amountUnderTest = new Amount(50.00, new Currency('PLN'));
        $multiplier = 0.5;

        // WHEN
        $actual = $amountUnderTest->multiply($multiplier);

        // THEN
        self::assertSame(25.0, $actual->toFloat());
    }

    public function testItThrowsExceptionWhenMultiplierIsNegative(): void
    {
        // EXPECT
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Amounts are not in the same currency.');;

        // GIVEN
        $amountUnderTest = new Amount(100.50, new Currency('PLN'));
        $amountToAdd = new Amount(50.00, new Currency('EUR'));

        // WHEN
        $actual = $amountUnderTest->subtract($amountToAdd);

        // THEN
        self::assertSame(100.50, $actual->toFloat());
    }
}

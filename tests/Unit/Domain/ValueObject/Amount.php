<?php

declare(strict_types=1);

namespace BankAccountPayment\Tests\Unit\Domain\ValueObject;

use BankAccountPayment\Domain\ValueObject\Currency;
use InvalidArgumentException;
use Money;
use Stringable;

final class Amount implements Stringable
{
    private float $amount;
    private Currency $currency;
    private Money\Money $money;

    public function __construct(float $amount, Currency $currency)
    {
        $money = $this->toMoney($amount, $currency);
        $amount = $this->normalize($money);

        $this->validate($money);

        $this->amount = $amount;
        $this->currency = $currency;
        $this->money = $money;
    }

    //
    // Modifiers
    //

    public function add(Amount $amountToAdd): self
    {
        $this->validateSameCurrency($amountToAdd);

        $moneyToAdd = $this->toMoney($amountToAdd->amount, $amountToAdd->currency);
        $updatedMoney = $this->money->add($moneyToAdd);

        return new self($this->normalize($updatedMoney), $this->currency);
    }

    public function subtract(Amount $amountToSubtract): self
    {
        $this->validateSameCurrency($amountToSubtract);

        $moneyToSubtract = $this->toMoney($amountToSubtract->amount, $amountToSubtract->currency);
        $updatedMoney = $this->money->subtract($moneyToSubtract);

        return new self($this->normalize($updatedMoney), $this->currency);
    }

    public function multiply(float $multiplier): self
    {
        $newAmount = (float) $this->getMoneyTeller($this->currency)->multiply($this->amount, $multiplier);

        return new self($newAmount, $this->currency);
    }

    //
    // Checkers
    //

    public function isEqual(Amount $other): bool
    {
        $otherMoney = $this->toMoney($other->amount, $other->currency);

        return $this->money->equals($otherMoney);
    }

    public function isGreaterThanOrEqual(Amount $other): bool
    {
        $otherMoney = $this->toMoney($other->amount, $other->currency);

        return $this->money->greaterThanOrEqual($otherMoney);
    }

    //
    // Dumpers
    //

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function toFloat(): float
    {
        return $this->amount;
    }

    public function toString(): string
    {
        return sprintf('%s %s', $this->getMoneyFormatter()->format($this->money), $this->currency->toString());
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    //
    // [Internal] Money library
    //

    private function getMoneyCurrencies(): Money\Currencies\ISOCurrencies
    {
        return new Money\Currencies\ISOCurrencies();
    }

    private function getMoneyParser(): Money\Parser\DecimalMoneyParser
    {
        return new Money\Parser\DecimalMoneyParser($this->getMoneyCurrencies());
    }

    private function getMoneyFormatter(): Money\Formatter\DecimalMoneyFormatter
    {
        return new Money\Formatter\DecimalMoneyFormatter($this->getMoneyCurrencies());
    }

    private function getMoneyTeller(Currency $currency): Money\Teller
    {
        $currency = new Money\Currency($currency->toString());

        return new Money\Teller(
            currency: $currency,
            parser: $this->getMoneyParser(),
            formatter: $this->getMoneyFormatter(),

        );
    }

    private function toMoney(float $amount, Currency $currency): Money\Money
    {
        return $this->getMoneyTeller($currency)->convertToMoney($amount);
    }

    //
    // [Internal] Normalizers
    //

    private function normalize(Money\Money $money): float
    {
        return (float) $this->getMoneyFormatter()->format($money);
    }

    //
    // [Internal] Validators
    //
    private function validate(Money\Money $money): void
    {
        if ($money->isNegative()) {
            throw new InvalidArgumentException('Amount cannot be negative.');
        }
    }

    private function validateSameCurrency(Amount $other): void
    {
        if (!$this->currency->isEqual($other->currency)) {
            throw new InvalidArgumentException('Amounts are not in the same currency.');
        }
    }
}

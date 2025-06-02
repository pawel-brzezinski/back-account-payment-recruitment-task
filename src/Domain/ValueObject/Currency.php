<?php

declare(strict_types=1);

namespace BankAccountPayment\Domain\ValueObject;

use InvalidArgumentException;
use Money\Currencies\ISOCurrencies;
use Stringable;

final class Currency implements Stringable
{
    private string $code;

    public function __construct(string $code)
    {
        $code = $this->normalize($code);
        $this->validate($code);

        $this->code = $code;
    }

    //
    // Checkers
    //

    public function isEqual(Currency $other): bool
    {
        return $this->code === $other->code;
    }

    //
    // Dumpers
    //

    public function toString(): string
    {
        return $this->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }

    //
    // [Internal]
    //

    private function normalize(string $code): string
    {
        return strtoupper($code);
    }

    private function validate(string $code): void
    {
        if (empty($code) || strlen($code) !== 3) {
            throw new InvalidArgumentException('Currency code must be exactly 3 characters long.');
        }

        // Check if the currency code is real
        $currencies = new ISOCurrencies();

        if (!$currencies->contains(new \Money\Currency($code))) {
            throw new InvalidArgumentException(sprintf('Currency "%s" is not valid ISO 4217 currency code.', $code));
        }
    }
}

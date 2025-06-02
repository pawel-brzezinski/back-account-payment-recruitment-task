<?php

declare(strict_types=1);

namespace BankAccountPayment\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

final class AccountId implements Stringable
{
    private string $id;

    public function __construct(string $id)
    {
        $this->validate($id);
        $this->id = $id;
    }

    //
    // Factories
    //

    public static function generate(): self
    {
        return new self(uniqid('acc-'));
    }

    //
    // Checkers
    //

    public function isEqual(AccountId $other): bool
    {
        return $this->id === $other->id;
    }

    //
    // Dumpers
    //

    public function toString(): string
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->id;
    }

    //
    // [Internal]
    //

    private function validate(string $id): void
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Account ID cannot be empty.');
        }

        // Check if ID matches a pattern: acc-xxxxxxxxxxxxx (13 hexadecimal characters after "acc-")
        if (!preg_match('/^acc-[a-f0-9]{13}$/', $id)) {
            throw new InvalidArgumentException('Account ID format is not valid.');
        }
    }
}

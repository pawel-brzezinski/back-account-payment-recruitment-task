<?php

declare(strict_types=1);

namespace BankAccountPayment\Domain\ValueObject;

use DateTimeImmutable;

final readonly class Payment
{
    public function __construct(
        private Amount $amount,
        private DateTimeImmutable $performedOn,
    ) {
    }

    //
    // Getters
    //

    public function amount(): Amount
    {
        return $this->amount;
    }

    public function performedOn(): DateTimeImmutable
    {
        return $this->performedOn;
    }
}

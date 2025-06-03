<?php

declare(strict_types=1);

namespace BankAccountPayment\Domain\Event;

use BankAccountPayment\Domain\ValueObject\AccountId;
use BankAccountPayment\Domain\ValueObject\Amount;
use DateTimeImmutable;

final readonly class BankAccountWasDebited implements DomainEventInterface
{
    public function __construct(
        public AccountId $accountId,
        public Amount $debitedAmount,
        public Amount $accountBalance,
        private DateTimeImmutable $occurredAt,
    ) {
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}

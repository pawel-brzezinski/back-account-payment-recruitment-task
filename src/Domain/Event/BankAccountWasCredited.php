<?php

declare(strict_types=1);

namespace BankAccountPayment\Domain\Event;

use BankAccountPayment\Domain\ValueObject\AccountId;
use BankAccountPayment\Tests\Unit\Domain\ValueObject\Amount;
use DateTimeImmutable;

final readonly class BankAccountWasCredited implements DomainEventInterface
{
    public function __construct(
        public AccountId $accountId,
        public Amount $creditedAmount,
        public Amount $accountBalance,
        private DateTimeImmutable $occurredAt,
    ) {
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}

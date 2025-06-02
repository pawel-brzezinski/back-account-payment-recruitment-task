<?php

declare(strict_types=1);

namespace BankAccountPayment\Domain\Event;

use BankAccountPayment\Domain\ValueObject\AccountId;
use BankAccountPayment\Domain\ValueObject\Currency;
use DateTimeImmutable;

final readonly class BankAccountWasOpened implements DomainEventInterface
{
    public function __construct(
        public AccountId $accountId,
        public Currency $currency,
        private DateTimeImmutable $occurredAt,
    ) {
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}

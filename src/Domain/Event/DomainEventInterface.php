<?php

declare(strict_types=1);

namespace BankAccountPayment\Domain\Event;

use DateTimeImmutable;

interface DomainEventInterface
{
    public function occurredAt(): DateTimeImmutable;
}
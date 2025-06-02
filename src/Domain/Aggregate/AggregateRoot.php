<?php

declare(strict_types=1);

namespace BankAccountPayment\Domain\Aggregate;

use BankAccountPayment\Domain\Event\DomainEventInterface;

abstract class AggregateRoot
{
    /**
     * @var DomainEventInterface[]
     */
    private array $recordedEvents = [];

    /**
     * @return DomainEventInterface[]
     */
    public function pullDomainEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    //
    // [Internal]
    //

    protected function recordThat(DomainEventInterface $event): void
    {
        $this->recordedEvents[] = $event;
    }
}

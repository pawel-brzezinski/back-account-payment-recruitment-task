<?php

declare(strict_types=1);

namespace BankAccountPayment\Domain\Repository;

use BankAccountPayment\Domain\Aggregate\BankAccount;
use BankAccountPayment\Domain\ValueObject\AccountId;
use BankAccountPayment\Primitives\Exception\ResourceNotFoundException;

interface BankAccountRepositoryInterface
{
    public function save(BankAccount $bankAccount): void;

    /**
     * @throws ResourceNotFoundException
     */
    public function getById(AccountId $id): BankAccount;

    /**
     * @return array<BankAccount>
     */
    public function getAll(): array;
}
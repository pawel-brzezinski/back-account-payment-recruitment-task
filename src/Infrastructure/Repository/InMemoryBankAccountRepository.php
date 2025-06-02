<?php

declare(strict_types=1);

namespace BankAccountPayment\Infrastructure\Repository;

use BankAccountPayment\Domain\Aggregate\BankAccount;
use BankAccountPayment\Domain\Repository\BankAccountRepositoryInterface;
use BankAccountPayment\Domain\ValueObject\AccountId;
use BankAccountPayment\Primitives\Exception\ResourceNotFoundException;

final class InMemoryBankAccountRepository implements BankAccountRepositoryInterface
{
    /**
     * @var array<BankAccount>
     */
    private array $bankAccounts = [];

    public function save(BankAccount $bankAccount): void
    {
        $this->bankAccounts[$bankAccount->getId()->toString()] = $bankAccount;
    }

    public function getById(AccountId $id): BankAccount
    {
        $bankAccount = $this->bankAccounts[$id->toString()] ?? null;

        if (null === $bankAccount) {
            throw new ResourceNotFoundException();
        }

        return $bankAccount;
    }

    public function getAll(): array
    {
        return $this->bankAccounts;
    }
}

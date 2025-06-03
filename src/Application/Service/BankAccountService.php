<?php

declare(strict_types=1);

namespace BankAccountPayment\Application\Service;

use BankAccountPayment\Domain\Aggregate\BankAccount;
use BankAccountPayment\Domain\Exception\DailyDebitLimitExceededException;
use BankAccountPayment\Domain\Exception\LackOfFoundsException;
use BankAccountPayment\Domain\ValueObject\AccountId;
use BankAccountPayment\Domain\ValueObject\Amount;
use BankAccountPayment\Domain\ValueObject\Currency;
use BankAccountPayment\Infrastructure\Repository\InMemoryBankAccountRepository;
use BankAccountPayment\Primitives\Exception\ResourceNotFoundException;

final readonly class BankAccountService
{
    public function __construct(
        private InMemoryBankAccountRepository $bankAccountRepository
    ) {
    }

    public function openAccount(Currency $currency): AccountId
    {
        $bankAccountId = AccountId::generate();
        $bankAccount = BankAccount::open(id: $bankAccountId, currency: $currency);

        $this->bankAccountRepository->save($bankAccount);

        return $bankAccountId;
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function creditAccount(AccountId $accountId, Amount $amount): void
    {
        $bankAccount = $this->bankAccountRepository->getById($accountId);

        $bankAccount->credit($amount);

        $this->bankAccountRepository->save($bankAccount);
    }

    /**
     * @throws DailyDebitLimitExceededException
     * @throws LackOfFoundsException
     * @throws ResourceNotFoundException
     */
    public function debitAccount(AccountId $accountId, Amount $amount, \DateTimeImmutable $operationDate): void
    {
        $bankAccount = $this->bankAccountRepository->getById($accountId);

        $bankAccount->debit($amount, $operationDate);

        $this->bankAccountRepository->save($bankAccount);
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function getAccountBalance(AccountId $accountId): Amount
    {
        $bankAccount = $this->bankAccountRepository->getById($accountId);

        return $bankAccount->getBalance();
    }
}

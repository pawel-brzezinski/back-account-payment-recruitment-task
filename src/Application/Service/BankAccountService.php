<?php

declare(strict_types=1);

namespace BankAccountPayment\Application\Service;

use BankAccountPayment\Domain\Aggregate\BankAccount;
use BankAccountPayment\Domain\ValueObject\AccountId;
use BankAccountPayment\Domain\ValueObject\Currency;
use BankAccountPayment\Infrastructure\Repository\InMemoryBankAccountRepository;

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
}

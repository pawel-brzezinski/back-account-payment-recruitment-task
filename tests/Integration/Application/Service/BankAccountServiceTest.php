<?php

declare(strict_types=1);

namespace BankAccountPayment\Tests\Integration\Application\Service;

use BankAccountPayment\Application\Service\BankAccountService;
use BankAccountPayment\Domain\Exception\DailyDebitLimitExceededException;
use BankAccountPayment\Domain\Exception\LackOfFoundsException;
use BankAccountPayment\Domain\Repository\BankAccountRepositoryInterface;
use BankAccountPayment\Domain\ValueObject\Currency;
use BankAccountPayment\Infrastructure\Repository\InMemoryBankAccountRepository;
use BankAccountPayment\Primitives\Exception\ResourceNotFoundException;
use BankAccountPayment\Tests\Unit\Domain\ValueObject\Amount;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class BankAccountServiceTest extends TestCase
{
    private BankAccountRepositoryInterface $bankAccountRepository;

    protected function setUp(): void
    {
        $this->bankAccountRepository = new InMemoryBankAccountRepository();
    }

    /**
     * @throws DailyDebitLimitExceededException
     * @throws LackOfFoundsException
     * @throws ResourceNotFoundException
     */
    public function testItBankAccountWholeFlow(): void
    {
        $serviceUnderTest = $this->serviceUnderTest();

        //
        // Step 1. Open account

        // GIVEN
        $currency = new Currency('EUR');

        // WHEN
        $actualBankAccountId = $serviceUnderTest->openAccount($currency);

        // THEN
        $actualBankAccounts = $this->bankAccountRepository->getAll();

        self::assertCount(1, $this->bankAccountRepository->getAll());
        self::assertArrayHasKey($actualBankAccountId->toString(), $actualBankAccounts);

        $actualBankAccount = $actualBankAccounts[$actualBankAccountId->toString()];

        //
        // Step 2. Credit account

        // GIVEN
        $creditAmount1 = new Amount(200, $currency);
        $creditAmount2 = new Amount(35.55, $currency);

        // WHEN
        $actualBankAccount->credit($creditAmount1);
        $actualBankAccount->credit($creditAmount2);

        // THEN
        $actualBankAccount = $this->bankAccountRepository->getById($actualBankAccountId);

        self::assertSame(235.55, $actualBankAccount->getBalance()->toFloat());

        //
        // Step 3. Debit account

        // GIVEN
        $now = new DateTimeImmutable();
        $debitAmount1 = new Amount(10, $currency);
        $debitAmount2 = new Amount(20, $currency);

        // WHEN
        $actualBankAccount->debit(amount: $debitAmount1, operationDate: $now);
        $actualBankAccount->debit(amount: $debitAmount2, operationDate: $now);

        // THEN
        $actualBankAccount = $this->bankAccountRepository->getById($actualBankAccountId);

        self::assertSame(205.40, $actualBankAccount->getBalance()->toFloat());
    }

    //
    // [Internal]
    //

    private function serviceUnderTest(): BankAccountService
    {
        return new BankAccountService(
            bankAccountRepository: $this->bankAccountRepository,
        );
    }
}

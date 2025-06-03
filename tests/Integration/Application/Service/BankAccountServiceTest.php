<?php

declare(strict_types=1);

namespace BankAccountPayment\Tests\Integration\Application\Service;

use BankAccountPayment\Application\Service\BankAccountService;
use BankAccountPayment\Domain\Exception\DailyDebitLimitExceededException;
use BankAccountPayment\Domain\Exception\LackOfFoundsException;
use BankAccountPayment\Domain\Repository\BankAccountRepositoryInterface;
use BankAccountPayment\Domain\ValueObject\Amount;
use BankAccountPayment\Domain\ValueObject\Currency;
use BankAccountPayment\Infrastructure\Repository\InMemoryBankAccountRepository;
use BankAccountPayment\Primitives\Exception\ResourceNotFoundException;
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
        $actualBankAccountBalance = $serviceUnderTest->getAccountBalance(accountId: $actualBankAccountId)->toFloat();

        self::assertSame(0.0, $actualBankAccountBalance);

        //
        // Step 2. Credit account

        // GIVEN
        $creditAmount1 = new Amount(200, $currency);
        $creditAmount2 = new Amount(35.55, $currency);

        // WHEN
        $serviceUnderTest->creditAccount(accountId: $actualBankAccountId, amount: $creditAmount1);
        $serviceUnderTest->creditAccount(accountId: $actualBankAccountId, amount: $creditAmount2);

        // THEN
        $actualBankAccountBalance = $serviceUnderTest->getAccountBalance(accountId: $actualBankAccountId)->toFloat();

        self::assertSame(235.55, $actualBankAccountBalance);

        //
        // Step 3. Debit account

        // GIVEN
        $now = new DateTimeImmutable();
        $debitAmount1 = new Amount(10, $currency);
        $debitAmount2 = new Amount(20, $currency);

        // WHEN
        $serviceUnderTest->debitAccount(accountId: $actualBankAccountId, amount: $debitAmount1, operationDate: $now);
        $serviceUnderTest->debitAccount(accountId: $actualBankAccountId, amount: $debitAmount2, operationDate: $now);

        // THEN
        $actualBankAccountBalance = $serviceUnderTest->getAccountBalance(accountId: $actualBankAccountId)->toFloat();

        self::assertSame(205.40, $actualBankAccountBalance);
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

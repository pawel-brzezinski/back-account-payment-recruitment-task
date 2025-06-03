<?php

declare(strict_types=1);

namespace BankAccountPayment\Tests\Unit\Domain\Aggregate;

use BankAccountPayment\Domain\Aggregate\BankAccount;
use BankAccountPayment\Domain\Event\BankAccountWasCredited;
use BankAccountPayment\Domain\Event\BankAccountWasDebited;
use BankAccountPayment\Domain\Event\BankAccountWasOpened;
use BankAccountPayment\Domain\Exception\DailyDebitLimitExceededException;
use BankAccountPayment\Domain\Exception\LackOfFoundsException;
use BankAccountPayment\Domain\ValueObject\AccountId;
use BankAccountPayment\Domain\ValueObject\Amount;
use BankAccountPayment\Domain\ValueObject\Currency;
use BankAccountPayment\Domain\ValueObject\Payment;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class BankAccountTest extends TestCase
{
    //
    // Open account
    //

    public function testItOpensBankAccount(): void
    {
        // GIVEN
        $accountId = new AccountId('acc-'.uniqid());
        $currency = new Currency('PLN');

        // WHEN
        $actual = BankAccount::open($accountId, $currency);

        // THEN
        self::assertSame($accountId, $actual->getId());
        self::assertSame($currency, $actual->getCurrency());
        self::assertSame('0.00 PLN', $actual->getBalance()->toString());
        self::assertCount(0, $actual->getDebitPayments());

        // Be sure that domain events have been recorded
        $actualDomainEvents = $actual->pullDomainEvents();
        self::assertCount(1, $actualDomainEvents);

        $actualFirstDomainEvent = $actualDomainEvents[0];
        self::assertInstanceOf(BankAccountWasOpened::class, $actualFirstDomainEvent);
        self::assertSame($accountId, $actualFirstDomainEvent->accountId);
        self::assertSame($currency, $actualFirstDomainEvent->currency);
    }

    //
    // Credit payments
    //

    public function testItAddsAmountToAccountBalance(): void
    {
        // GIVEN
        $currency = new Currency('PLN');
        $bankAccountUnderTest = $this->bankAccountUnderTest(currency: $currency);

        // Pre-assertion - be sure that balance is 0
        self::assertSame(0.0, $bankAccountUnderTest->getBalance()->toFloat());

        //
        // 1st credit action

        // GIVEN
        $creditAmount = new Amount(100.50, $currency);

        // WHEN
        $bankAccountUnderTest->credit(amount: $creditAmount);

        // THEN
        self::assertSame(100.50, $bankAccountUnderTest->getBalance()->toFloat());

        // Be sure that domain events have been recorded
        $actualDomainEvents = $bankAccountUnderTest->pullDomainEvents();
        self::assertCount(1, $actualDomainEvents);

        $actualFirstDomainEvent = $actualDomainEvents[0];
        self::assertInstanceOf(BankAccountWasCredited::class, $actualFirstDomainEvent);
        self::assertSame($bankAccountUnderTest->getId(), $actualFirstDomainEvent->accountId);
        self::assertSame($creditAmount, $actualFirstDomainEvent->creditedAmount);
        self::assertSame(100.5, $actualFirstDomainEvent->accountBalance->toFloat());

        //
        // 2nd credit action

        // GIVEN
        $creditAmount = new Amount(55.43, $currency);

        // WHEN
        $bankAccountUnderTest->credit(amount: $creditAmount);

        // THEN
        self::assertSame(155.93, $bankAccountUnderTest->getBalance()->toFloat());

        // Be sure that domain events have been recorded
        $actualDomainEvents = $bankAccountUnderTest->pullDomainEvents();
        self::assertCount(1, $actualDomainEvents);

        $actualFirstDomainEvent = $actualDomainEvents[0];
        self::assertInstanceOf(BankAccountWasCredited::class, $actualFirstDomainEvent);
        self::assertSame($bankAccountUnderTest->getId(), $actualFirstDomainEvent->accountId);
        self::assertSame($creditAmount, $actualFirstDomainEvent->creditedAmount);
        self::assertSame(155.93, $actualFirstDomainEvent->accountBalance->toFloat());
    }

    //
    // Debit payments
    //

    public function testItSubtractsAmountToAccountBalance(): void
    {
        // GIVEN
        $currency = new Currency('PLN');
        $bankAccountUnderTest = $this->bankAccountUnderTest(currency: $currency, balance: 1000.0);

        // Pre-assertion - be sure that balance is 0
        self::assertSame(1000.0, $bankAccountUnderTest->getBalance()->toFloat());

        // GIVEN
        $debitAmount = new Amount(10.00, $currency);
        $operationDate = new DateTimeImmutable('2025-06-02T18:00:00+00:00');;

        // EXPECT
        // 1000 - 10 - (10 * 0.5%) = 989.95
        $expectedAmount = 989.95;

        // WHEN
        $bankAccountUnderTest->debit(amount: $debitAmount, operationDate: $operationDate);

        // THEN
        self::assertSame($expectedAmount, $bankAccountUnderTest->getBalance()->toFloat());

        // Be sure that payment was registered
        $actualDebitPayments = $bankAccountUnderTest->getDebitPayments();
        self::assertArrayHasKey($operationDate->format('Y-m-d'), $actualDebitPayments);

        $actualDebitPayments = $actualDebitPayments[$operationDate->format('Y-m-d')];
        self::assertCount(1, $actualDebitPayments);

        $actualFirstDebitPayment = $actualDebitPayments[0];
        self::assertInstanceOf(Payment::class, $actualFirstDebitPayment);
        self::assertSame($debitAmount, $actualFirstDebitPayment->amount());

        // Be sure that domain events have been recorded
        $actualDomainEvents = $bankAccountUnderTest->pullDomainEvents();
        self::assertCount(1, $actualDomainEvents);

        $actualFirstDomainEvent = $actualDomainEvents[0];
        self::assertInstanceOf(BankAccountWasDebited::class, $actualFirstDomainEvent);
        self::assertSame($bankAccountUnderTest->getId(), $actualFirstDomainEvent->accountId);
        self::assertSame(10.05, $actualFirstDomainEvent->debitedAmount->toFloat());
        self::assertSame($expectedAmount, $actualFirstDomainEvent->accountBalance->toFloat());
    }

    public function testItChecksIfMultipleDebitOperationsForDifferentDatesArePossible(): void
    {
        // GIVEN
        $currency = new Currency('PLN');
        $bankAccountUnderTest = $this->bankAccountUnderTest(currency: $currency, balance: 100.0);

        // WHEN (at the same time make 3 debit operations at the same date and one in different date)
        $bankAccountUnderTest->debit(new Amount(10.00, $currency), new DateTimeImmutable('2025-06-01T15:00:00+00:00'));
        $bankAccountUnderTest->debit(new Amount(10.00, $currency), new DateTimeImmutable('2025-06-02T18:01:00+00:00'));
        $bankAccountUnderTest->debit(new Amount(10.00, $currency), new DateTimeImmutable('2025-06-02T18:02:00+00:00'));
        $bankAccountUnderTest->debit(new Amount(10.00, $currency), new DateTimeImmutable('2025-06-02T19:03:00+00:00'));

        // THEN
        $actualDebitPayments = $bankAccountUnderTest->getDebitPayments();

        self::assertArrayHasKey('2025-06-01', $actualDebitPayments);
        self::assertCount(1, $actualDebitPayments['2025-06-01']);

        self::assertArrayHasKey('2025-06-02', $actualDebitPayments);
        self::assertCount(3, $actualDebitPayments['2025-06-02']);
    }

    public function testItThrowsExceptionWhenDailyOperationLimitHasBeenReached(): void
    {
        // EXPECT
        self::expectException(DailyDebitLimitExceededException::class);

        // GIVEN
        $operationDate = new DateTimeImmutable('2025-06-02T18:00:00+00:00');
        $currency = new Currency('PLN');
        $bankAccountUnderTest = $this->bankAccountUnderTest(currency: $currency, balance: 100.0);

        // Make 3 debit operations
        $bankAccountUnderTest->debit(new Amount(10.00, $currency), $operationDate);
        $bankAccountUnderTest->debit(new Amount(10.00, $currency), $operationDate);
        $bankAccountUnderTest->debit(new Amount(10.00, $currency), $operationDate);

        // WHEN (making 4th operation at the same day should throw an exception
        $bankAccountUnderTest->debit(new Amount(10.00, $currency), $operationDate);

    }

    public function testItThrowsExceptionWhenThereAreNotEnoughFoundsInAccountToMakeACreditOperation(): void
    {
        // EXPECT
        self::expectException(LackOfFoundsException::class);

        // GIVEN
        $currency = new Currency('PLN');
        $bankAccountUnderTest = $this->bankAccountUnderTest(currency: $currency, balance: 100.0);

        // WHEN (we have 100.00 PLN in an account, but then the fee amount can't be received)
        $bankAccountUnderTest->debit(new Amount(100.00, $currency), new DateTimeImmutable());
    }

    //
    // [Internal]
    //

    private function bankAccountUnderTest(
        Currency $currency = new Currency('EUR'),
        float $balance = 0.00,
    ): BankAccount {
        $accountId = new AccountId('acc-'.uniqid());
        $bankAccount = BankAccount::open(id: $accountId, currency: $currency);

        if ($balance > 0) {
            $bankAccount->credit(amount: new Amount($balance, $currency));
        }

        // Clear all domain events to have a clean aggregate state
        $bankAccount->pullDomainEvents();

        return $bankAccount;
    }
}

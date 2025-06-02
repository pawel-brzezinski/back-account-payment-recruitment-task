<?php

declare(strict_types=1);

namespace BankAccountPayment\Domain\Aggregate;

use BankAccountPayment\Domain\Event\BankAccountWasCredited;
use BankAccountPayment\Domain\Event\BankAccountWasDebited;
use BankAccountPayment\Domain\Event\BankAccountWasOpened;
use BankAccountPayment\Domain\Exception\DailyDebitLimitExceededException;
use BankAccountPayment\Domain\Exception\LackOfFoundsException;
use BankAccountPayment\Domain\ValueObject\AccountId;
use BankAccountPayment\Domain\ValueObject\Currency;
use BankAccountPayment\Tests\Unit\Domain\ValueObject\Amount;
use BankAccountPayment\Tests\Unit\Domain\ValueObject\Payment;
use DateTimeImmutable;

class BankAccount extends AggregateRoot
{
    private const float DEBIT_FEE_RATE = 0.005; // 0.5%
    private const int DEBIT_DAILY_OPERATIONS_LIMIT = 3;

    private readonly AccountId $id;

    private readonly Currency $currency;

    private Amount $balance;

    private array $debitPayments;


    final private function __construct(AccountId $id, Currency $currency)
    {
        $this->id = $id;
        $this->currency = $currency;
        $this->balance = new Amount(0.0, $currency);
        $this->debitPayments = [];
    }

    #
    # Factories
    #

    public static function open(AccountId $id, Currency $currency): self
    {
        $bankAccount = new self(id: $id, currency: $currency);

        $bankAccount->recordThat(new BankAccountWasOpened(
            accountId: $id,
            currency: $currency,
            occurredAt: new DateTimeImmutable(),
        ));

        return $bankAccount;
    }

    #
    # Modifiers
    #

    public function credit(Amount $amount): void
    {
        $this->balance = $this->balance->add($amount);

        $this->recordThat(new BankAccountWasCredited(
            accountId: $this->id,
            creditedAmount: $amount,
            accountBalance: $this->balance,
            occurredAt: new DateTimeImmutable(),
        ));
    }

    /**
     * @throws DailyDebitLimitExceededException
     * @throws LackOfFoundsException
     */
    public function debit(Amount $amount, DateTimeImmutable $operationDate): void
    {
        $this->checkDebitOperationsDailyLimitNotExceeded($operationDate);

        $debitFee = $amount->multiply(self::DEBIT_FEE_RATE);
        $totalAmountToDebit = $amount->add($debitFee);

        $this->checkSufficientFounds($totalAmountToDebit);

        $this->balance = $this->balance->subtract($totalAmountToDebit);

        $payment = new Payment($amount, $operationDate);
        $this->debitPayments[$operationDate->format('Y-m-d')][] = $payment;

        $this->recordThat(new BankAccountWasDebited(
            accountId: $this->id,
            debitedAmount: $totalAmountToDebit,
            accountBalance: $this->balance,
            occurredAt: new DateTimeImmutable(),
        ));
    }

    #
    # Getters
    #

    public function getId(): AccountId
    {
        return $this->id;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getBalance(): Amount
    {
        return $this->balance;
    }

    public function getDebitPayments(): array
    {
        return $this->debitPayments;
    }

    //
    // [Internal]
    //

    /**
     * @throws DailyDebitLimitExceededException
     */
    private function checkDebitOperationsDailyLimitNotExceeded(DateTimeImmutable $operationDate): void
    {
        $date = $operationDate->format('Y-m-d');
        $operationsInDate = $this->debitPayments[$date] ?? [];

        if (count($operationsInDate) >= self::DEBIT_DAILY_OPERATIONS_LIMIT) {
            throw new DailyDebitLimitExceededException();
        }
    }

    /**
     * @throws LackOfFoundsException
     */
    private function checkSufficientFounds(Amount $amount): void
    {
        if (!$this->balance->isGreaterThanOrEqual($amount)) {
            throw new LackOfFoundsException();
        }
    }
}

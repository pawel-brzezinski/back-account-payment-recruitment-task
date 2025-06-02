<?php

declare(strict_types=1);

namespace BankAccountPayment\Domain\Exception;

use Exception;

final class DailyDebitLimitExceededException extends Exception
{
}

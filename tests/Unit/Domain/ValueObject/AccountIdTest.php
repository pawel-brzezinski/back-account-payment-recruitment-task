<?php

declare(strict_types=1);

namespace BankAccountPayment\Tests\Unit\Domain\ValueObject;

use BankAccountPayment\Domain\ValueObject\AccountId;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AccountIdTest extends TestCase
{
    public function testItBasicFunctionalityForValidId(): void
    {
        // GIVEN
        $id = 'acc-'.uniqid();

        // WHEN
        $actual = new AccountId($id);

        // THEN
        self::assertSame($id, $actual->toString());
    }

    public function testItValueObjectIsStringable(): void
    {
        // GIVEN
        $id = uniqid('acc-');

        // WHEN
        $actual = new AccountId($id);

        // THEN
        self::assertSame($id, (string) $actual);
    }

    public static function invalidIdDataProvider(): iterable
    {
        yield 'id is empty string' => [
            'id' => '',
            'expectedExceptionMessage' => 'Account ID cannot be empty.',
        ];

        yield 'id has not valid prefix' => [
            'id' => uniqid('xxx-'),
            'expectedExceptionMessage' => 'Account ID format is not valid.',
        ];

        yield 'id has not valid 12 chars after prefix' => [
            'id' => 'acc-683daf410f3f',
            'expectedExceptionMessage' => 'Account ID format is not valid.',
        ];

        yield 'id has not valid 14 chars after prefix' => [
            'id' => 'acc-683daf410f3f9z',
            'expectedExceptionMessage' => 'Account ID format is not valid.',
        ];
    }

    #[DataProvider(methodName: 'invalidIdDataProvider')]
    public function testItThrowsExceptionWhenIdIsNotValid(string $id, string $expectedExceptionMessage): void
    {
        // EXPECT
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage($expectedExceptionMessage);

        // WHEN
        new AccountId($id);
    }

    public function testItReturnsTrueForTwoEqualIds(): void
    {
        // GIVEN
        $id = uniqid('acc-');
        $other = $id;

        $voUnderTest = new AccountId($id);
        $otherVo = new AccountId($other);

        // WHEN
        $actual = $voUnderTest->isEqual($otherVo);

        // THEN
        self::assertTrue($actual);
    }

    public function testItReturnsFalseForTwoDifferentIds(): void
    {
        // GIVEN
        $id = uniqid('acc-');
        $other = uniqid('acc-');

        $voUnderTest = new AccountId($id);
        $otherVo = new AccountId($other);

        // WHEN
        $actual = $voUnderTest->isEqual($otherVo);

        // THEN
        self::assertFalse($actual);
    }
}

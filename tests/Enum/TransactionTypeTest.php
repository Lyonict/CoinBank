<?php

namespace App\Tests\Enum;

use App\Enum\TransactionType;
use PHPUnit\Framework\TestCase;

class TransactionTypeTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame('buy', TransactionType::BUY->value);
        $this->assertSame('sell', TransactionType::SELL->value);
    }

    public function testEnumCases(): void
    {
        $cases = TransactionType::cases();
        $this->assertCount(2, $cases);
        $this->assertContainsOnlyInstancesOf(TransactionType::class, $cases);
        $this->assertSame(TransactionType::BUY, $cases[0]);
        $this->assertSame(TransactionType::SELL, $cases[1]);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('Buy', TransactionType::BUY->getLabel());
        $this->assertSame('Sell', TransactionType::SELL->getLabel());
    }

    public function testFromString(): void
    {
        $this->assertSame(TransactionType::BUY, TransactionType::from('buy'));
        $this->assertSame(TransactionType::SELL, TransactionType::from('sell'));
    }

    public function testInvalidFromString(): void
    {
        $this->expectException(\ValueError::class);
        TransactionType::from('invalid');
    }

    public function testTryFromString(): void
    {
        $this->assertSame(TransactionType::BUY, TransactionType::tryFrom('buy'));
        $this->assertSame(TransactionType::SELL, TransactionType::tryFrom('sell'));
        $this->assertNull(TransactionType::tryFrom('invalid'));
    }
}
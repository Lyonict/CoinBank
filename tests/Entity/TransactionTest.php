<?php

namespace App\Tests\Entity;

use App\Entity\Transaction;
use App\Entity\Cryptocurrency;
use App\Entity\User;
use App\Enum\TransactionType;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    private Transaction $transaction;

    protected function setUp(): void
    {
        $this->transaction = new Transaction();
    }

    public function testId(): void
    {
        $this->assertNull($this->transaction->getId());
        // Note: We can't set the ID as it's managed by Doctrine
    }

    public function testDate(): void
    {
        $date = new \DateTimeImmutable();
        $this->transaction->setDate($date);
        $this->assertEquals($date, $this->transaction->getDate());
    }

    public function testCryptocurrency(): void
    {
        $cryptocurrency = new Cryptocurrency();
        $this->transaction->setCryptocurrency($cryptocurrency);
        $this->assertSame($cryptocurrency, $this->transaction->getCryptocurrency());
    }

    public function testCryptoAmount(): void
    {
        $amount = 1.5;
        $this->transaction->setCryptoAmount($amount);
        $this->assertEquals($amount, $this->transaction->getCryptoAmount());
    }

    public function testUser(): void
    {
        $user = new User();
        $this->transaction->setUser($user);
        $this->assertSame($user, $this->transaction->getUser());
    }

    public function testTransactionType(): void
    {
        $type = TransactionType::BUY;
        $this->transaction->setTransactionType($type);
        $this->assertEquals($type, $this->transaction->getTransactionType());
    }

    public function testDollarAmount(): void
    {
        $amount = 1000.50;
        $this->transaction->setDollarAmount($amount);
        $this->assertEquals($amount, $this->transaction->getDollarAmount());
    }

    public function testDefaultValues(): void
    {
        $newTransaction = new Transaction();
        $this->assertNull($newTransaction->getId());
        $this->assertNull($newTransaction->getDate());
        $this->assertNull($newTransaction->getCryptocurrency());
        $this->assertNull($newTransaction->getCryptoAmount());
        $this->assertNull($newTransaction->getUser());
        $this->assertNull($newTransaction->getTransactionType());
        $this->assertNull($newTransaction->getDollarAmount());
    }

    public function testFluentInterfaces(): void
    {
        $date = new \DateTimeImmutable();
        $cryptocurrency = new Cryptocurrency();
        $user = new User();

        $result = $this->transaction
            ->setDate($date)
            ->setCryptocurrency($cryptocurrency)
            ->setCryptoAmount(1.5)
            ->setUser($user)
            ->setTransactionType(TransactionType::SELL)
            ->setDollarAmount(1000.50);

        $this->assertSame($this->transaction, $result);
        $this->assertEquals($date, $this->transaction->getDate());
        $this->assertSame($cryptocurrency, $this->transaction->getCryptocurrency());
        $this->assertEquals(1.5, $this->transaction->getCryptoAmount());
        $this->assertSame($user, $this->transaction->getUser());
        $this->assertEquals(TransactionType::SELL, $this->transaction->getTransactionType());
        $this->assertEquals(1000.50, $this->transaction->getDollarAmount());
    }
}
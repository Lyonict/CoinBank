<?php

namespace App\Tests\Entity;

use App\Entity\Cryptocurrency;
use App\Entity\Transaction;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class CryptocurrencyTest extends TestCase
{
    private Cryptocurrency $cryptocurrency;

    protected function setUp(): void
    {
        $this->cryptocurrency = new Cryptocurrency();
    }

    public function testId(): void
    {
        $this->assertNull($this->cryptocurrency->getId());
        // Note: We can't set the ID as it's managed by Doctrine
    }

    public function testSymbol(): void
    {
        $symbol = 'BTC';
        $this->cryptocurrency->setSymbol($symbol);
        $this->assertEquals($symbol, $this->cryptocurrency->getSymbol());
    }

    public function testName(): void
    {
        $name = 'Bitcoin';
        $this->cryptocurrency->setName($name);
        $this->assertEquals($name, $this->cryptocurrency->getName());
    }

    public function testCoingeckoId(): void
    {
        $coingeckoId = 'bitcoin';
        $this->cryptocurrency->setCoingeckoId($coingeckoId);
        $this->assertEquals($coingeckoId, $this->cryptocurrency->getCoingeckoId());
    }

    public function testTransactions(): void
    {
        $this->assertInstanceOf(ArrayCollection::class, $this->cryptocurrency->getTransactions());
        $this->assertCount(0, $this->cryptocurrency->getTransactions());

        $transaction1 = new Transaction();
        $transaction2 = new Transaction();

        $this->cryptocurrency->addTransaction($transaction1);
        $this->cryptocurrency->addTransaction($transaction2);

        $this->assertCount(2, $this->cryptocurrency->getTransactions());
        $this->assertTrue($this->cryptocurrency->getTransactions()->contains($transaction1));
        $this->assertTrue($this->cryptocurrency->getTransactions()->contains($transaction2));

        $this->cryptocurrency->removeTransaction($transaction1);

        $this->assertCount(1, $this->cryptocurrency->getTransactions());
        $this->assertFalse($this->cryptocurrency->getTransactions()->contains($transaction1));
        $this->assertTrue($this->cryptocurrency->getTransactions()->contains($transaction2));
    }

    public function testAddTransactionTwice(): void
    {
        $transaction = new Transaction();

        $this->cryptocurrency->addTransaction($transaction);
        $this->cryptocurrency->addTransaction($transaction);

        $this->assertCount(1, $this->cryptocurrency->getTransactions());
    }

    public function testRemoveNonExistentTransaction(): void
    {
        $transaction = new Transaction();

        $this->cryptocurrency->removeTransaction($transaction);

        $this->assertCount(0, $this->cryptocurrency->getTransactions());
    }

    public function testDefaultValues(): void
    {
        $newCryptocurrency = new Cryptocurrency();
        $this->assertNull($newCryptocurrency->getId());
        $this->assertNull($newCryptocurrency->getSymbol());
        $this->assertNull($newCryptocurrency->getName());
        $this->assertNull($newCryptocurrency->getCoingeckoId());
        $this->assertInstanceOf(ArrayCollection::class, $newCryptocurrency->getTransactions());
        $this->assertCount(0, $newCryptocurrency->getTransactions());
    }

    public function testFluentInterfaces(): void
    {
        $result = $this->cryptocurrency
            ->setSymbol('ETH')
            ->setName('Ethereum')
            ->setCoingeckoId('ethereum');

        $this->assertSame($this->cryptocurrency, $result);
        $this->assertEquals('ETH', $this->cryptocurrency->getSymbol());
        $this->assertEquals('Ethereum', $this->cryptocurrency->getName());
        $this->assertEquals('ethereum', $this->cryptocurrency->getCoingeckoId());
    }
}
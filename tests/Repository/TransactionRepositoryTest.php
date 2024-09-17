<?php

namespace App\Tests\Repository;

use App\Entity\Cryptocurrency;
use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\TransactionType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TransactionRepositoryTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;
    private $entityManager;
    private $transactionRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->transactionRepository = $this->entityManager->getRepository(Transaction::class);
    }

    public function testGetNetAmountByName(): void
    {
        $this->createTestData();

        $netAmount = $this->transactionRepository->getNetAmountByName('Bitcoin');

        $this->assertEquals(1.5, $netAmount);

        // Test for non-existent cryptocurrency
        $nullBalance = $this->transactionRepository->getNetAmountByName('notexist');
        $this->assertNull($nullBalance);

        // Test for cryptocurrency with zero net amount
        $zeroNetAmount = $this->transactionRepository->getNetAmountByName('ZeroCoin');
        $this->assertNull($zeroNetAmount);
    }

    public function testGetCryptosOfUserWithBalance(): void
    {
        $user = $this->createTestData();

        $cryptos = $this->transactionRepository->getCryptosOfUserWithBalance($user);

        $this->assertCount(2, $cryptos);
        $this->assertEquals('Bitcoin', $cryptos[0]['name']);
        $this->assertEquals('bitcoin', $cryptos[0]['coingecko_id']);
        $this->assertEquals(1.5, $cryptos[0]['cryptoBalance']);
        $this->assertEquals('Ethereum', $cryptos[1]['name']);
        $this->assertEquals('ethereum', $cryptos[1]['coingecko_id']);
        $this->assertEquals(2.0, $cryptos[1]['cryptoBalance']);
    }

    public function testGetCryptoBalanceForUserAndCrypto(): void
    {
        $user = $this->createTestData();

        $balance = $this->transactionRepository->getCryptoBalanceForUserAndCrypto($user, 'bitcoin');

        $this->assertNotNull($balance);
        $this->assertEquals('Bitcoin', $balance['name']);
        $this->assertEquals('bitcoin', $balance['coingecko_id']);
        $this->assertEquals(1.5, $balance['cryptoBalance']);
    }

        public function testGetAllTransactionsForUser(): void
    {
        $user = $this->createTestData();

        $queryBuilder = $this->transactionRepository->getAllTransactionsForUser($user);
        $transactions = $queryBuilder->getQuery()->getResult();

        $this->assertCount(5, $transactions);
        $this->assertInstanceOf(Transaction::class, $transactions[0]);
        $this->assertSame($user, $transactions[0]->getUser());
    }

    public function testGetTransactionsForUserAndCoinGeckoId(): void
    {
        $user = $this->createTestData();

        $queryBuilder = $this->transactionRepository->getTransactionsForUserAndCoinGeckoId($user, 'bitcoin');
        $transactions = $queryBuilder->getQuery()->getResult();

        $this->assertCount(2, $transactions);
        $this->assertInstanceOf(Transaction::class, $transactions[0]);
        $this->assertSame($user, $transactions[0]->getUser());
        $this->assertEquals('bitcoin', $transactions[0]->getCryptocurrency()->getCoingeckoId());

        // Test for non-existent CoinGecko ID
        $emptyQueryBuilder = $this->transactionRepository->getTransactionsForUserAndCoinGeckoId($user, 'nonexistent');
        $emptyTransactions = $emptyQueryBuilder->getQuery()->getResult();
        $this->assertEmpty($emptyTransactions);
    }

    private function createTestData(): User
    {
        $user = new User();
        $user->setEmail('testtransaction@example.com');
        $user->setUsername('testuser');
        $user->setPassword('password');
        $user->setPreferedLocale('en');
        $user->setBank(1000); // Initial balance
        $user->setSponsorCode('TESTTRANSATION123');
        $this->entityManager->persist($user);

        $bitcoin = new Cryptocurrency();
        $bitcoin->setName('Bitcoin');
        $bitcoin->setSymbol('BTC');
        $bitcoin->setCoingeckoId('bitcoin');
        $this->entityManager->persist($bitcoin);

        $ethereum = new Cryptocurrency();
        $ethereum->setName('Ethereum');
        $ethereum->setSymbol('ETH');
        $ethereum->setCoingeckoId('ethereum');
        $this->entityManager->persist($ethereum);

        $zeroCoin = new Cryptocurrency();
        $zeroCoin->setName('ZeroCoin');
        $zeroCoin->setSymbol('ZERO');
        $zeroCoin->setCoingeckoId('zerocoin');
        $this->entityManager->persist($zeroCoin);

        $transaction1 = new Transaction();
        $transaction1->setUser($user);
        $transaction1->setCryptocurrency($bitcoin);
        $transaction1->setTransactionType(TransactionType::BUY);
        $transaction1->setCryptoAmount(2.0);
        $transaction1->setDollarAmount(20000);
        $transaction1->setDate(new \DateTimeImmutable());
        $this->entityManager->persist($transaction1);

        $transaction2 = new Transaction();
        $transaction2->setUser($user);
        $transaction2->setCryptocurrency($bitcoin);
        $transaction2->setTransactionType(TransactionType::SELL);
        $transaction2->setCryptoAmount(0.5);
        $transaction2->setDollarAmount(5000);
        $transaction2->setDate(new \DateTimeImmutable());
        $this->entityManager->persist($transaction2);

        $transaction3 = new Transaction();
        $transaction3->setUser($user);
        $transaction3->setCryptocurrency($ethereum);
        $transaction3->setTransactionType(TransactionType::BUY);
        $transaction3->setCryptoAmount(2.0);
        $transaction3->setDollarAmount(4000);
        $transaction3->setDate(new \DateTimeImmutable());
        $this->entityManager->persist($transaction3);

        $transaction4 = new Transaction();
        $transaction4->setUser($user);
        $transaction4->setCryptocurrency($zeroCoin);
        $transaction4->setTransactionType(TransactionType::BUY);
        $transaction4->setCryptoAmount(1.0);
        $transaction4->setDollarAmount(1000);
        $transaction4->setDate(new \DateTimeImmutable());
        $this->entityManager->persist($transaction4);

        $transaction5 = new Transaction();
        $transaction5->setUser($user);
        $transaction5->setCryptocurrency($zeroCoin);
        $transaction5->setTransactionType(TransactionType::SELL);
        $transaction5->setCryptoAmount(1.0);
        $transaction5->setDollarAmount(1000);
        $transaction5->setDate(new \DateTimeImmutable());
        $this->entityManager->persist($transaction5);

        $this->entityManager->flush();

        return $user;
    }

    protected function tearDown(): void
    {

        parent::tearDown();

        // Database cleanup
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
<?php

namespace App\Tests\Repository;

use App\Entity\Cryptocurrency;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class CryptocurrencyRepositoryTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;
    private $entityManager;
    private $cryptocurrencyRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->cryptocurrencyRepository = $this->entityManager->getRepository(Cryptocurrency::class);

        $this->createTestData();
    }

    public function testFindCoingeckoIds(): void
    {
        $coingeckoIds = $this->cryptocurrencyRepository->findCoingeckoIds();

        $this->assertIsArray($coingeckoIds);
        $this->assertCount(3, $coingeckoIds);

        $expectedIds = ['bitcoin', 'ethereum', 'dogecoin'];
        $actualIds = array_column($coingeckoIds, 'coingecko_id');

        $this->assertEqualsCanonicalizing($expectedIds, $actualIds);
    }

    public function testFindOneByCoingeckoId(): void
    {
        $bitcoin = $this->cryptocurrencyRepository->findOneByCoingeckoId('bitcoin');
        $this->assertInstanceOf(Cryptocurrency::class, $bitcoin);
        $this->assertEquals('Bitcoin', $bitcoin->getName());

        $nonExistent = $this->cryptocurrencyRepository->findOneByCoingeckoId('nonexistent');
        $this->assertNull($nonExistent);
    }

    public function testFindOneByName(): void
    {
        $ethereum = $this->cryptocurrencyRepository->findOneByName('Ethereum');
        $this->assertInstanceOf(Cryptocurrency::class, $ethereum);
        $this->assertEquals('ethereum', $ethereum->getCoingeckoId());

        $nonExistent = $this->cryptocurrencyRepository->findOneByName('NonExistent');
        $this->assertNull($nonExistent);
    }

    private function createTestData(): void
    {
        $cryptocurrencies = [
            ['name' => 'Bitcoin', 'symbol' => 'BTC', 'coingecko_id' => 'bitcoin'],
            ['name' => 'Ethereum', 'symbol' => 'ETH', 'coingecko_id' => 'ethereum'],
            ['name' => 'Dogecoin', 'symbol' => 'DOGE', 'coingecko_id' => 'dogecoin'],
        ];

        foreach ($cryptocurrencies as $data) {
            $crypto = new Cryptocurrency();
            $crypto->setName($data['name']);
            $crypto->setSymbol($data['symbol']);
            $crypto->setCoingeckoId($data['coingecko_id']);
            $this->entityManager->persist($crypto);
        }

        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Database cleanup
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
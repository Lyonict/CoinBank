<?php

namespace App\Tests\Service;

use App\Service\CoinGeckoService;
use App\Repository\CryptocurrencyRepository;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class CoinGeckoServiceTest extends TestCase
{
    private $coinGeckoService;
    private $mockHandler;
    private CryptocurrencyRepository $cryptoRepository;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $client = new Client(['handler' => $handlerStack]);

        $this->cryptoRepository = $this->createMock(CryptocurrencyRepository::class);

        $this->coinGeckoService = new CoinGeckoService('fake_api_key', $this->cryptoRepository);
        $this->coinGeckoService->setClient($client);
    }

    public function testGetPing()
    {
        $this->mockHandler->append(new Response(200, [], json_encode(['gecko_says' => '(V3) To the Moon!'])));

        $result = $this->coinGeckoService->getPing();

        $this->assertTrue($result);
    }

    public function testGetPingFailure()
    {
        $this->mockHandler->append(new Response(500, []));

        $result = $this->coinGeckoService->getPing();

        $this->assertFalse($result);
    }

    public function testGetAllCryptoCurrentPrice()
    {
        $this->mockHandler->append(new Response(200, [], json_encode(['gecko_says' => '(V3) To the Moon!'])));
        $this->mockHandler->append(new Response(200, [], json_encode([
            ['id' => 'bitcoin', 'current_price' => 50000],
            ['id' => 'ethereum', 'current_price' => 3000],
        ])));

        $this->cryptoRepository->method('findCoingeckoIds')
            ->willReturn([['coingecko_id' => 'bitcoin'], ['coingecko_id' => 'ethereum']]);

        $result = $this->coinGeckoService->getAllCryptoCurrentPrice();

        $this->assertEquals(['bitcoin' => 50000, 'ethereum' => 3000], $result);
    }

    public function testGetAllCryptoCurrentPriceFailure()
    {
        $this->mockHandler->append(new Response(500, []));

        $result = $this->coinGeckoService->getAllCryptoCurrentPrice();

        $this->assertFalse($result);
    }

    public function testGetCryptoCurrentPrice()
    {
        $this->mockHandler->append(new Response(200, [], json_encode(['gecko_says' => '(V3) To the Moon!'])));
        $this->mockHandler->append(new Response(200, [], json_encode([
            'market_data' => ['current_price' => ['usd' => 50000]]
        ])));

        $result = $this->coinGeckoService->getCryptoCurrentPrice('bitcoin');

        $this->assertEquals(50000, $result);
    }

    public function testGetCryptoCurrentPriceFailure()
    {
        $this->mockHandler->append(new Response(500, []));

        $result = $this->coinGeckoService->getCryptoCurrentPrice('bitcoin');

        $this->assertFalse($result);
    }
}
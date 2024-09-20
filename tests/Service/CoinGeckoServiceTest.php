<?php

namespace App\Tests\Service;

use App\Service\CoinGeckoService;
use App\Repository\CryptocurrencyRepository;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
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

    public function testGetAllCryptoCurrentPriceWithApiKeyNotSet()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('CoinGecko API key is not set');

        $coinGeckoService = new CoinGeckoService('', $this->cryptoRepository);
        $coinGeckoService->setClient(new Client(['handler' => HandlerStack::create($this->mockHandler)]));

        $coinGeckoService->getAllCryptoCurrentPrice();
    }

    public function testGetAllCryptoCurrentPriceWithApiNotResponding()
    {
        $this->mockHandler->append(new Response(500, []));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('CoinGecko API is not responding');

        $this->coinGeckoService->getAllCryptoCurrentPrice();
    }

    public function testGetAllCryptoCurrentPriceWithUnexpectedResponseFormat()
    {
        $this->mockHandler->append(new Response(200, [], json_encode(['gecko_says' => '(V3) To the Moon!'])));
        $this->mockHandler->append(new Response(200, [], json_encode('not an array')));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to fetch crypto prices: Unexpected API response format');

        $this->coinGeckoService->getAllCryptoCurrentPrice();
    }

    public function testGetAllCryptoCurrentPriceWithNoSupportedCryptocurrencies()
    {
        $this->mockHandler->append(new Response(200, [], json_encode(['gecko_says' => '(V3) To the Moon!'])));
        $this->mockHandler->append(new Response(200, [], json_encode([
            ['id' => 'unsupported_crypto', 'current_price' => 1000],
        ])));

        $this->cryptoRepository->method('findCoingeckoIds')
            ->willReturn([['coingecko_id' => 'bitcoin'], ['coingecko_id' => 'ethereum']]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to fetch crypto prices: No supported cryptocurrencies found in the API response');

        $this->coinGeckoService->getAllCryptoCurrentPrice();
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

    public function testGetCryptoCurrentPriceWithApiKeyNotSet()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('CoinGecko API key is not set');

        $coinGeckoService = new CoinGeckoService('', $this->cryptoRepository);
        $coinGeckoService->setClient(new Client(['handler' => HandlerStack::create($this->mockHandler)]));

        $coinGeckoService->getCryptoCurrentPrice('bitcoin');
    }

    public function testGetCryptoCurrentPriceWithApiNotResponding()
    {
        $this->mockHandler->append(new Response(500, []));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('CoinGecko API is not responding');

        $this->coinGeckoService->getCryptoCurrentPrice('bitcoin');
    }

    public function testGetCryptoCurrentPriceWithMissingData()
    {
        $this->mockHandler->append(new Response(200, [], json_encode(['gecko_says' => '(V3) To the Moon!'])));
        $this->mockHandler->append(new Response(200, [], json_encode([
            'market_data' => ['current_price' => []]
        ])));

        $result = $this->coinGeckoService->getCryptoCurrentPrice('bitcoin');

        $this->assertNull($result);
    }

    public function testGetCryptoCurrentPriceFailedFetch()
    {
        $this->mockHandler->append(new Response(200, [], json_encode(['gecko_says' => '(V3) To the Moon!'])));
        $this->mockHandler->append(
            new RequestException('Error Communicating with Server', new Request('GET', 'test'))
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to fetch crypto price: Error Communicating with Server');

        $this->coinGeckoService->getCryptoCurrentPrice('bitcoin');
    }

    public function testGetPingSuccess()
    {
        $this->mockHandler->append(new Response(200, [], json_encode(['gecko_says' => '(V3) To the Moon!'])));

        $result = $this->coinGeckoService->getPing();

        $this->assertTrue($result);
    }

    public function testGetPingFailureNon200Status()
    {
        $this->mockHandler->append(new Response(500, []));

        $result = $this->coinGeckoService->getPing();

        $this->assertFalse($result);
    }

    public function testGetPingFailureInvalidResponse()
    {
        $this->mockHandler->append(new Response(200, [], json_encode(['gecko_says' => 'Invalid response'])));

        $result = $this->coinGeckoService->getPing();

        $this->assertFalse($result);
    }

    public function testGetPingFailureException()
    {
        $this->mockHandler->append(new RequestException('Error Communicating with Server', new Request('GET', 'test')));

        $result = $this->coinGeckoService->getPing();

        $this->assertFalse($result);
    }
}
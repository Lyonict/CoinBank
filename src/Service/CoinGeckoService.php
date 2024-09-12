<?php

namespace App\Service;

use App\Repository\CryptocurrencyRepository;
use Psr\Log\LoggerInterface;

class CoinGeckoService
{
    private $client;
    private $logger;
    private $apiKey;
    private $cryptoRepository;

    public function __construct(LoggerInterface $logger, string $apiKey, CryptocurrencyRepository $cryptoRepository) {
        $this->client = new \GuzzleHttp\Client();
        $this->logger = $logger;
        $this->apiKey = $apiKey;
        $this->cryptoRepository = $cryptoRepository;
    }

    public function getPing() {
        try {
            $response = $this->geckoApiCall("ping");

            if ($response->getStatusCode() !== 200) {
                throw new \Exception("API request failed with status code: " . $response->getStatusCode());
            }

            $decodedResult = json_decode($response->getBody(), true);
            if (!isset($decodedResult['gecko_says']) || $decodedResult['gecko_says'] !== "(V3) To the Moon!") {
                throw new \Exception("Unexpected API response: " . json_encode($decodedResult));
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error('CoinGecko API ping failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getAllCryptoCurrentPrice() {
        if (!$this->getPing()) {
            $this->logger->error('CoinGecko API is not available');
            return false;
        }

        try {
            $supportedCryptos = $this->cryptoRepository->findCoinGeckoIds();
            $response = $this->geckoApiCall('coins/markets?vs_currency=usd');
            $decodedResult = json_decode($response->getBody(), true);

            if (!is_array($decodedResult)) {
                throw new \Exception("Unexpected API response format");
            }

            $supportedCryptoCoinGeckoIds = array_column($supportedCryptos, 'coingecko_id');
            $filteredCryptos = [];
            foreach ($decodedResult as $crypto) {
                if (in_array($crypto['id'], $supportedCryptoCoinGeckoIds)) {
                    $filteredCryptos[$crypto['id']] = $crypto['current_price'];
                }
            }

            return $filteredCryptos;
        } catch (\Exception $e) {
            $this->logger->error('CoinGecko API request failed: ' . $e->getMessage());
            return false;
        }
    }

    private function geckoApiCall(string $endpoint) {
        $response = $this->client->request('GET',"https://api.coingecko.com/api/v3/$endpoint", [
            'headers' => [
                'accept' => 'application/json',
                'x-cg-demo-api-key' => $this->apiKey,
              ],
        ]);

        return $response;
    }
}
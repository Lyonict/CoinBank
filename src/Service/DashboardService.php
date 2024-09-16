<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\TransactionRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

class DashboardService
{
    public function __construct(
        private TransactionRepository $transactionRepository,
        private CoinGeckoService $coinGeckoService,
        private TranslatorInterface $translator
    ) {}

    public function getCryptoBalances(User $user): array
    {
        $cryptoPrices = $this->coinGeckoService->getAllCryptoCurrentPrice();
        $cryptosData = $this->transactionRepository->getCryptosOfUserWithBalance($user);

        return $this->enrichCryptoData($cryptosData, $cryptoPrices);
    }

    private function enrichCryptoData(array $cryptosData, array $cryptoPrices): array
    {
        $enrichedCryptoData = [];
        foreach ($cryptosData as $crypto) {
            if (isset($cryptoPrices[$crypto['coingecko_id']])) {
                $crypto['currentPrice'] = $cryptoPrices[$crypto['coingecko_id']];
                $currentValue = $crypto['cryptoBalance'] * $crypto['currentPrice'];
                $crypto['profitPercentage'] = $this->calculateProfitPercentage($currentValue, $crypto['dollarBalance']);
                $crypto['currentValue'] = $this->formatCurrentValue($currentValue);
                $enrichedCryptoData[] = $crypto;
            }
        }
        return $enrichedCryptoData;
    }

    private function calculateProfitPercentage(float $currentValue, float $dollarBalance): float
    {
        return round(($currentValue - $dollarBalance) / $dollarBalance * 100, 2);
    }

    private function formatCurrentValue(float $currentValue): string
    {
        if ($currentValue >= 1000000) {
            return number_format($currentValue / 1000000, 2) . 'M';
        } elseif ($currentValue >= 1000) {
            return number_format($currentValue / 1000, 2) . 'k';
        } else {
            return number_format($currentValue, 2);
        }
    }
}
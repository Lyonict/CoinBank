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
        $cryptoBalances = $this->transactionRepository->getCryptoBalancesForUser($user);

        return $this->calculateUpdatedCryptoBalances($cryptoBalances, $cryptoPrices);
    }

    private function calculateUpdatedCryptoBalances(array $cryptoBalances, array $cryptoPrices): array
    {
        $updatedCryptoBalances = [];
        foreach ($cryptoBalances as $balance) {
            if (isset($cryptoPrices[$balance['coingecko_id']])) {
                $balance['currentPrice'] = $cryptoPrices[$balance['coingecko_id']];
                $currentValue = $balance['cryptoBalance'] * $balance['currentPrice'];
                $balance['profitPercentage'] = $this->calculateProfitPercentage($currentValue, $balance['dollarBalance']);
                $balance['currentValue'] = $this->formatCurrentValue($currentValue);
                $updatedCryptoBalances[] = $balance;
            }
        }
        return $updatedCryptoBalances;
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
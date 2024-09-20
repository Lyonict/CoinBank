<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\TransactionType;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CryptoTransactionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TransactionRepository $transactionRepository,
        private CoinGeckoService $coinGeckoService,
        private UserBankService $userBankService,
        private TranslatorInterface $translator,
        private readonly GlobalStateService $globalStateService,
    ) {}

    public function createTransaction(Transaction $transaction, User $user): void
    {
        if($this->globalStateService->isLockdown()) {
            throw new \InvalidArgumentException($this->translator->trans('Lockdown is enabled : all transactions are disabled'));
        }

        if ($user->getIsFrozen()) {
            throw new \InvalidArgumentException($this->translator->trans('Your account is frozen : all transactions are disabled'));
        }
        $cryptoPrices = $this->coinGeckoService->getAllCryptoCurrentPrice();
        $selectedCryptocurrency = $transaction->getCryptocurrency();
        $cryptoAmount = $transaction->getCryptoAmount();
        $moneyAmount = $cryptoPrices[$selectedCryptocurrency->getCoingeckoId()] * $cryptoAmount;

        if ($transaction->getTransactionType() === TransactionType::BUY) {
            $this->userBankService->processCryptoBuy($user, $moneyAmount);
        } else {
            $this->userBankService->processCryptoSell($user, $selectedCryptocurrency, $cryptoAmount, $moneyAmount);
        }

        $transaction->setUser($user);
        $transaction->setDate(new \DateTimeImmutable());
        $transaction->setDollarAmount($moneyAmount);

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();
    }

    public function getCryptoBalances(User $user): array
    {
        $cryptoPrices = $this->coinGeckoService->getAllCryptoCurrentPrice();
        $cryptosData = $this->transactionRepository->getCryptosOfUserWithBalance($user);

        return $this->enrichCryptoData($cryptosData, $cryptoPrices);
    }

    public function getSingleCryptoBalance(User $user, string $coingeckoId): ?array
    {
        $cryptoPrice = $this->coinGeckoService->getCryptoCurrentPrice($coingeckoId);
        $cryptoData = $this->transactionRepository->getCryptoBalanceForUserAndCrypto($user, $coingeckoId);

        if (!$cryptoData) {
            return null;
        }

        return $this->enrichSingleCryptoData($cryptoData, $cryptoPrice);
    }

    private function enrichCryptoData(array $cryptoData, array $cryptoPrices): array
    {
        $enrichedCryptoData = [];
        foreach ($cryptoData as $crypto) {
            if (isset($cryptoPrices[$crypto['coingecko_id']])) {
                $enrichedCryptoData[] = $this->enrichSingleCryptoData($crypto, $cryptoPrices[$crypto['coingecko_id']]);
            }
        }
        return $enrichedCryptoData;
    }

    private function enrichSingleCryptoData(array $crypto, float $currentPrice): array
    {
        $crypto['currentPrice'] = $currentPrice;
        $currentValue = $crypto['cryptoBalance'] * $currentPrice;
        $crypto['profitPercentage'] = $this->calculateProfitPercentage($currentValue, $crypto['dollarBalance']);
        $crypto['formattedCurrentValue'] = $this->formatCurrentValue($currentValue);
        $crypto['currentValue'] = $currentValue;
        return $crypto;
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
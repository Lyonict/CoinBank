<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\TransactionType;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CryptoTransactionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TransactionRepository $transactionRepository,
        private CoinGeckoService $coinGeckoService,
        private UserBankService $userBankService,
        private TranslatorInterface $translator,
        private readonly LoggerInterface $logger
    ) {}

    public function createTransaction(Transaction $transaction, User $user): void
    {
        $cryptoPrices = $this->coinGeckoService->getAllCryptoCurrentPrice();
        $this->logger->info('cryptoPrices', ['cryptoPrices' => $cryptoPrices]);
        $selectedCryptocurrency = $transaction->getCryptocurrency();
        $cryptoAmount = $transaction->getCryptoAmount();

        // Check if cryptoPrices is set
        if (isset($cryptoPrices) && !empty($cryptoPrices)) {
            echo "cryptoPrices is set and not empty\n";
            print_r($cryptoPrices);
        } else {
            echo "cryptoPrices is not set or empty\n";
        }

        // Log input data
        echo "Creating transaction:\n";
        echo "User ID: " . ($user ? $user->getId() : 'null') . "\n";
        echo "Cryptocurrency: " . ($selectedCryptocurrency ? $selectedCryptocurrency->getName() : 'null') . "\n";
        echo "Crypto Amount: " . $cryptoAmount . "\n";
        echo "Transaction Type: " . $transaction->getTransactionType()->name . "\n";
        echo "bidulos" . "\n";

        if (!$selectedCryptocurrency) {
            echo "no selected cryptocurrency" . "\n";
            throw new \InvalidArgumentException("Cryptocurrency is not set");
        }

        $moneyAmount = $cryptoPrices[$selectedCryptocurrency->getCoingeckoId()] * $cryptoAmount;
        echo "Money amount " . $moneyAmount . "\n";

        if ($transaction->getTransactionType() === TransactionType::BUY) {
            echo "Processing crypto buy transaction\n";
            echo "User bank balance before: " . $user->getBank() . "\n";
            $this->userBankService->processCryptoBuy($user, $moneyAmount);
        } else {
            echo "Processing crypto sell transaction\n";
            $this->userBankService->processCryptoSell($user, $selectedCryptocurrency, $cryptoAmount, $moneyAmount);
        }

        $transaction->setUser($user);
        $transaction->setDate(new \DateTimeImmutable());
        $transaction->setDollarAmount($moneyAmount);

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        // Log transaction data
        echo "Transaction created:\n";
        echo "User: " . $user->getEmail() . "\n";
        echo "Cryptocurrency: " . $selectedCryptocurrency->getName() . "\n";
        echo "Type: " . $transaction->getTransactionType()->name . "\n";
        echo "Crypto Amount: " . $cryptoAmount . "\n";
        echo "Dollar Amount: $" . $moneyAmount . "\n";
        echo "Date: " . $transaction->getDate()->format('Y-m-d H:i:s') . "\n";
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
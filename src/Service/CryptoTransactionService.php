<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\TransactionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CryptoTransactionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CoinGeckoService $coinGeckoService,
        private UserBankService $userBankService,
        private TranslatorInterface $translator
    ) {}

    public function createTransaction(Transaction $transaction, User $user): void
    {
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
}
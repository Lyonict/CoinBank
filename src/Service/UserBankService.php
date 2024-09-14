<?php

namespace App\Service;

use App\Entity\Cryptocurrency;
use App\Entity\User;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
class UserBankService
{
    private $entityManager;
    private $translator;
    private $transactionRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        TransactionRepository $transactionRepository)
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->transactionRepository = $transactionRepository;
    }

    public function updateUserBank(User $user, float $amount, string $bankTransactionMode): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException($this->translator->trans('Amount must be a positive number.'));
        }

        if (!in_array($bankTransactionMode, ['deposit', 'withdraw'], true)) {
            throw new \InvalidArgumentException($this->translator->trans('Invalid bank transaction mode.'));
        }

        if ($bankTransactionMode === 'withdraw' && $amount > $user->getBank()) {
            throw new \InvalidArgumentException($this->translator->trans('You cannot withdraw more than your current balance.'));
        }

        if($bankTransactionMode === 'deposit' && $amount+$user->getBank() > 100000) {
            throw new \InvalidArgumentException($this->translator->trans('You cannot deposit more than 100000.'));
        }

        $newBalance = $bankTransactionMode === 'deposit'
        ? $user->getBank() + $amount
        : $user->getBank() - $amount;

        $user->setBank($newBalance);
        $this->entityManager->flush();
    }

    public function processCryptoBuy(User $user, float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException($this->translator->trans('Amount must be a positive number.'));
        }

        if ($user->getBank() < $amount) {
            throw new \InvalidArgumentException($this->translator->trans('Insufficient funds for this purchase.'));
        }

        $newBalance = $user->getBank() - $amount;
        $user->setBank($newBalance);
        $this->entityManager->flush();
    }

    public function processCryptoSell(User $user, Cryptocurrency $cryptocurrency, float $cryptoAmount, float $moneyAmount): void
    {
        if ($cryptoAmount <= 0) {
            throw new \InvalidArgumentException($this->translator->trans('Amount must be a positive number.'));
        }

        $netAmount = $this->transactionRepository->getNetAmountByName($cryptocurrency->getName());

        if ($netAmount === null || $cryptoAmount > $moneyAmount) {
            throw new \InvalidArgumentException($this->translator->trans('Insufficient cryptocurrency balance for this sale.'));
        }

        $newBalance = $user->getBank() + $moneyAmount;
        $user->setBank($newBalance);
        $this->entityManager->flush();
    }
}
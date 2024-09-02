<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserBankService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function updateUserBank(User $user, float $amount, string $bankTransactionMode): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be a positive number.');
        }

        if (!in_array($bankTransactionMode, ['deposit', 'withdraw'], true)) {
            throw new \InvalidArgumentException('Invalid bank transaction mode.');
        }

        if ($bankTransactionMode === 'withdraw' && $amount > $user->getBank()) {
            throw new \InvalidArgumentException('You cannot withdraw more than your current balance.');
        }

        $newBalance = $bankTransactionMode === 'deposit'
        ? $user->getBank() + $amount
        : $user->getBank() - $amount;

        $user->setBank($newBalance);
        $this->entityManager->flush();
    }
}
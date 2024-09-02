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
        if ($bankTransactionMode === 'deposit') {
            $user->setBank($user->getBank() + $amount);
        } else {
            $user->setBank($user->getBank() - $amount);
        }

        $this->entityManager->flush();
    }
}
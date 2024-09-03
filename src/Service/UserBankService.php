<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
class UserBankService
{
    private $entityManager;
    private $translator;

    public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
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
}
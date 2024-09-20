<?php

namespace App\Service;

use App\Repository\CryptocurrencyRepository;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
class CryptoFormService
{
    public function __construct(
        private CryptocurrencyRepository $cryptocurrencyRepository,
        private CryptoTransactionService $cryptoTransactionService,
        private readonly GlobalStateService $globalStateService,
        private readonly TranslatorInterface $translator,
    ) {}

    public function handleCryptoSelection(FormInterface $form, ?string $crypto): void
    {
        if ($crypto) {
            $cryptocurrency = $this->cryptocurrencyRepository->findOneByCoingeckoId($crypto);
            if ($cryptocurrency) {
                $form->get('cryptocurrency')->setData($cryptocurrency);
            }
        }
    }

    public function processForm(FormInterface $form, $user): bool
    {
        if ($form->isSubmitted() && $form->isValid()) {
            $transaction = $form->getData();
            try {
                $this->cryptoTransactionService->createTransaction($transaction, $user);
                return true;
            } catch (\Exception $e) {
                throw $e;
            }
        }
        return false;
    }
}
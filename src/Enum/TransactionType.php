<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatorInterface;

enum TransactionType: string
{
    case BUY = 'buy';
    case SELL = 'sell';

    public function getLabel(TranslatorInterface $translator): string
    {
        return match ($this) {
            self::BUY => $translator->trans('Buy'),
            self::SELL => $translator->trans('Sell'),
        };
    }
}
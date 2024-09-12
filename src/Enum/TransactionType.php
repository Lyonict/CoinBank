<?php

namespace App\Enum;

enum TransactionType: string
{
    case BUY = 'buy';
    case SELL = 'sell';

    public function getLabel(): string
    {
        return match ($this) {
            self::BUY => 'Buy',
            self::SELL => 'Sell',
        };
    }
}
<?php

namespace App\Enum;

enum PromotionType: string
{
    case DateRangeMultiplier = 'date_range_multiplier';
    case FixedPriceVoucher = 'fixed_price_voucher';
    case EvenItemsMultiplier = 'even_items_multiplier';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

<?php

namespace App\Filter\Modifier;

use App\DTO\PromotionEnquiryInterface;
use App\Entity\Promotion;

class fixedPriceVoucher implements PriceModifierInterface
{

    public function modify(int $price, int $quantity, Promotion $promotion, PromotionEnquiryInterface $enquiry): int
    {
        if ($enquiry->getVoucherCode() !== $promotion->getCriteria()['code']) {
            return $price * $quantity;
        }

        return $quantity * $promotion->getAdjustment();
    }
}
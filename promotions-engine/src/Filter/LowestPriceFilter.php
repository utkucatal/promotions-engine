<?php

namespace App\Filter;

use App\DTO\PromotionEnquiryInterface;
use App\Entity\Promotion;
use DeepCopy\Filter\Filter;

class LowestPriceFilter implements PromotionsFilterInterface
{
    public function apply(PromotionEnquiryInterface $enquiry, Promotion ...$promotion): PromotionEnquiryInterface
    {
        $price = $enquiry->getPrice();
        $quantity = $enquiry->getQuantity();
        $lowestPrice = $quantity * $price;
        // $modifiedPrice = $priceModifier->modify($price, $quantity, $promotion, $enquiry);
        $enquiry->setDiscountedPrice(50);
        $enquiry->setPrice(100);
        $enquiry->setPromotionId(3);
        $enquiry->setPromotionName('Black Friday half price sale');
        return $enquiry;
    }
}

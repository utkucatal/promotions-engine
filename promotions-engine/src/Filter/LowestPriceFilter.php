<?php

namespace App\Filter;

use App\DTO\PromotionEnquiryInterface;
use DeepCopy\Filter\Filter;

class LowestPriceFilter implements PromotionsFilterInterface
{
    public function apply(PromotionEnquiryInterface $enquiry): PromotionEnquiryInterface
    {
        $enquiry->setDiscountedPrice(50);
        $enquiry->setPrice(100);
        $enquiry->setPromotionId(3);
        $enquiry->setPromotionName(3);
        return $enquiry;
    }
}

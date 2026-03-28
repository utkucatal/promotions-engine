<?php

namespace App\DTO;

use App\Entity\Promotion;

readonly class PromotionResponse
{
    public function __construct(
        public int    $id,
        public string $name,
        public string $type,
        public float  $adjustment,
        public array  $criteria,
    ) {
    }

    public static function fromEntity(Promotion $promotion): self
    {
        return new self(
            id: $promotion->getId(),
            name: $promotion->getName(),
            type: $promotion->getType(),
            adjustment: $promotion->getAdjustment(),
            criteria: $promotion->getCriteria()
        );
    }

}
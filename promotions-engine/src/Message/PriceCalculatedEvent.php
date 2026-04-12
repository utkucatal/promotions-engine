<?php


namespace App\Message;

readonly class PriceCalculatedEvent
{
    public function __construct(
        public int $productId,
        public string $productName,
        public int $originalPrice,
        public ?int $discountedPrice,
        public ?int $promotionId,
        public ?string $promotionName,
        public int $quantity,
        public string $requestDate,
        public string $calculatedAt
    ){}
}

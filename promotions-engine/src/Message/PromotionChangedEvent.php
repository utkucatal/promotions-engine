<?php

namespace App\Message;

readonly class PromotionChangedEvent
{
    public function __construct(
        public string $action,
        public int $promotionId,
        public ?array $data = null,
    )
    {}
}

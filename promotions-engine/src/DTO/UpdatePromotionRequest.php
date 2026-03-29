<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdatePromotionRequest
{
    #[Assert\Length(max: 255)]
    public ?string $name = null;

    #[Assert\Positive]
    public ?float $adjustment = null;

    public ?array $criteria = null;
}

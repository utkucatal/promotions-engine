<?php

namespace App\DTO;

use App\Enum\PromotionType;
use Symfony\Component\Validator\Constraints as Assert;

class CreatePromotionRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $name = null;

    #[Assert\NotBlank]
    #[Assert\Choice(callback: [PromotionType::class, 'values'])]
    public ?string $type = null;

    #[Assert\NotBlank]
    #[Assert\Positive]
    public ?float $adjustment = null;

    #[Assert\NotBlank]
    public array $criteria = [];
}
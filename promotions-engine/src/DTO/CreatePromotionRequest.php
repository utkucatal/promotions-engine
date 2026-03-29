<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreatePromotionRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $name = null;

    #[Assert\NotBlank]
    #[ValidPromotionType]
    public ?string $type = null;

    #[Assert\NotBlank]
    #[Assert\Positive]
    public ?float $adjustment = null;

    #[Assert\NotBlank]
    public array $criteria = [];
}
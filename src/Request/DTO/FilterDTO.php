<?php

namespace App\Request\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class FilterDTO
{
    public function __construct(
        #[Assert\Length(min: 1, max: 200)]
        public readonly ?string $brand = null
    ) {
    }
}

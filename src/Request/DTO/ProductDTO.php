<?php

declare(strict_types=1);

namespace App\Request\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ProductDTO
{
    public function __construct(
        #[Assert\Length(min: 1, max: 255)]
        public readonly ?string $brand = null,

        #[Assert\Length(min: 1, max: 255)]
        public readonly ?string $name = null,

        #[Assert\Length(min: 1)]
        public readonly ?string $description = null,

        #[Assert\Length(min: 1, max: 255)]
        public readonly ?string $reference = null
    ) {
    }
}

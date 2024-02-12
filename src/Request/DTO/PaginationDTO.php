<?php

namespace App\Request\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class PaginationDTO
{
    public function __construct(
        #[Assert\Positive()]
        public readonly int $page = 1,

        #[Assert\Positive()]
        public readonly int $limit = 3
    ) {
    }
}

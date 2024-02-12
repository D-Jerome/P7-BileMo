<?php

namespace App\Request\DTO;

class FilterDTO
{
    public function __construct(
        public readonly ?string $brand = null
    ) {
    }
}

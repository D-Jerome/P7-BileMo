<?php

declare(strict_types=1);

namespace App\Request\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CustomerDTO
{
    public function __construct(
        #[Assert\NotNull(message: 'the value name should not be null')]
        #[Assert\NotBlank(message: 'the value name should not be blank')]
        #[Assert\Length(max: 255)]
        public readonly ?string $name = null,
    ) {
    }
}

<?php

namespace App\Request\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UserDTO
{
    public function __construct(
        // #[Assert\NotNull(message:"the value username should not be null")]
        #[Assert\Length(min: 5)]
        public readonly ?string $username = null,
        #[Assert\Email(message: 'the value email is not a valid email address.')]
        public readonly ?string $email = null,
        #[Assert\PasswordStrength(message: 'the password is too weak')]
        public readonly ?string $password = null,

        #[Assert\Choice(choices: ['ROLE_USER', 'ROLE_COMPANY_ADMIN'], message: 'the role is incorrect')]
        public readonly ?string $roles = null
    ) {
    }
}

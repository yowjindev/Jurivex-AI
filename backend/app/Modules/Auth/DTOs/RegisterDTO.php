<?php

namespace App\Modules\Auth\DTOs;

readonly class RegisterDTO
{
    public function __construct(
        public string $organizationName,
        public string $name,
        public string $email,
        public string $password,
    ) {}
}

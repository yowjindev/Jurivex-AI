<?php

namespace App\Modules\Auth\DTOs;

readonly class RegisterDTO
{
    public function __construct(
        public string $invitationCode,
        public string $name,
        public string $email,
        public string $password,
    ) {}
}

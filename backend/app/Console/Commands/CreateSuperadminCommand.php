<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateSuperadminCommand extends Command
{
    protected $signature   = 'superadmin:create';
    protected $description = 'Create a platform superadmin account';

    public function handle(): int
    {
        $name     = $this->ask('Name');
        $email    = $this->ask('Email');
        $password = $this->secret('Password');

        if (User::where('email', $email)->exists()) {
            $this->error("A user with email {$email} already exists.");
            return Command::FAILURE;
        }

        $org = Organization::firstOrCreate(
            ['slug' => 'jurivex-ai-platform'],
            ['name' => 'Jurivex AI Platform'],
        );

        $user = User::create([
            'organization_id' => $org->id,
            'name'            => $name,
            'email'           => $email,
            'password'        => Hash::make($password),
        ]);

        $user->assignRole('superadmin');

        $this->info("Superadmin created: {$email}");

        return Command::SUCCESS;
    }
}

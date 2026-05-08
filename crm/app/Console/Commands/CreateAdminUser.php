<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:user {email : Admin email} {password : Admin password} {--name=Alan : Admin name}';

    protected $description = 'Create or update the primary admin user.';

    public function handle(): int
    {
        $user = User::updateOrCreate(
            ['email' => $this->argument('email')],
            [
                'name' => $this->option('name'),
                'password' => Hash::make($this->argument('password')),
            ]
        );

        $this->info($user->wasRecentlyCreated ? 'Admin user created.' : 'Admin user updated.');

        return self::SUCCESS;
    }
}

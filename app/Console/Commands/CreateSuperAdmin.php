<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateSuperAdmin extends Command
{
    protected $signature = 'motogrip:create-super-admin {--name=} {--email=}';
    protected $description = 'Create the first super administrator (password is prompted, never stored in files or env)';

    public function handle(): int
    {
        if (User::role('super-admin')->exists() && ! $this->confirm('A super administrator already exists. Create another?')) {
            return self::SUCCESS;
        }

        $name = $this->option('name') ?: $this->ask('Name');
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->secret('Password (min 14 characters)');

        $validator = Validator::make(
            compact('name', 'email', 'password'),
            [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'unique:users,email'],
                'password' => ['required', Password::min(14)->mixedCase()->numbers()->uncompromised()],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);
        $user->forceFill(['status' => 'active', 'email_verified_at' => now(), 'approved_at' => now()])->save();
        $user->assignRole('super-admin');

        $this->info("Super administrator {$email} created.");
        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MakeAdminCommand extends Command
{
    use PasswordValidationRules, ProfileValidationRules;

    protected $signature = 'make:admin';

    protected $description = 'Create an admin user (only way to bootstrap the first login — registration is disabled)';

    public function handle(): int
    {
        $name = $this->ask('Name');
        $username = $this->ask('Username');
        $email = $this->ask('Email (optional)') ?: null;
        $password = $this->secret('Password');
        $passwordConfirmation = $this->secret('Confirm password');

        $validator = Validator::make(
            [
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
            ],
            [
                ...$this->profileRules(),
                'username' => $this->usernameRules(),
                'password' => $this->passwordRules(),
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => UserRole::Admin,
        ]);

        $this->info("Admin user \"{$username}\" created.");

        return self::SUCCESS;
    }
}

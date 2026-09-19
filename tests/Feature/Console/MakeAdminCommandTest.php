<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MakeAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_admin_user(): void
    {
        $this->artisan('make:admin')
            ->expectsQuestion('Name', 'Stuart Millington')
            ->expectsQuestion('Username', 'stuart')
            ->expectsQuestion('Email (optional)', 'stuart@example.com')
            ->expectsQuestion('Password', 'a-strong-password')
            ->expectsQuestion('Confirm password', 'a-strong-password')
            ->assertExitCode(0);

        $user = User::where('username', 'stuart')->firstOrFail();

        $this->assertSame(UserRole::Admin, $user->role);
    }

    public function test_it_fails_when_passwords_do_not_match(): void
    {
        $this->artisan('make:admin')
            ->expectsQuestion('Name', 'Stuart Millington')
            ->expectsQuestion('Username', 'stuart')
            ->expectsQuestion('Email (optional)', 'stuart@example.com')
            ->expectsQuestion('Password', 'a-strong-password')
            ->expectsQuestion('Confirm password', 'does-not-match')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['username' => 'stuart']);
    }
}

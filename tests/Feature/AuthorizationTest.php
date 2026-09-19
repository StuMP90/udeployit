<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_can_manage_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('manage-users'));
    }

    public function test_staff_cannot_manage_users(): void
    {
        $staff = User::factory()->create();

        $this->assertFalse(Gate::forUser($staff)->allows('manage-users'));
    }
}

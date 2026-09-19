<?php

namespace Tests\Feature\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_view_the_users_index(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('users.index'))->assertForbidden();
    }

    public function test_admin_can_view_the_users_index(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('users.index'))->assertOk();
    }

    public function test_admin_can_create_a_user(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::users.create')
            ->set('name', 'New Person')
            ->set('username', 'newperson')
            ->set('email', 'new@example.com')
            ->set('role', 'staff')
            ->set('password', 'a-strong-password')
            ->set('password_confirmation', 'a-strong-password')
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('username', 'newperson')->firstOrFail();

        $this->assertSame(UserRole::Staff, $user->role);
        $this->assertTrue(Hash::check('a-strong-password', $user->password));
    }

    public function test_admin_can_edit_a_user(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $target = User::factory()->create(['username' => 'target']);

        Livewire::test('pages::users.edit', ['user' => $target])
            ->set('name', 'Updated Name')
            ->set('role', 'admin')
            ->call('save')
            ->assertHasNoErrors();

        $target->refresh();

        $this->assertSame('Updated Name', $target->name);
        $this->assertSame(UserRole::Admin, $target->role);
    }

    public function test_admin_can_reset_another_users_password(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $target = User::factory()->create();

        Livewire::test('pages::users.edit', ['user' => $target])
            ->set('password', 'brand-new-password')
            ->set('password_confirmation', 'brand-new-password')
            ->call('resetPassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('brand-new-password', $target->refresh()->password));
    }

    public function test_the_last_admin_cannot_be_demoted(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test('pages::users.edit', ['user' => $admin])
            ->set('role', 'staff')
            ->call('save')
            ->assertHasErrors(['role']);

        $this->assertSame(UserRole::Admin, $admin->refresh()->role);
    }

    public function test_the_last_admin_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test('pages::users.index')->call('deleteUser', $admin);

        $this->assertNotNull($admin->fresh());
    }

    public function test_admin_can_delete_another_user(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $target = User::factory()->create();

        Livewire::test('pages::users.index')->call('deleteUser', $target);

        $this->assertNull($target->fresh());
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        // Ensure there is another admin so the "last admin" rule isn't what blocks this.
        User::factory()->admin()->create();

        Livewire::test('pages::users.index')->call('deleteUser', $admin);

        $this->assertNotNull($admin->fresh());
    }
}

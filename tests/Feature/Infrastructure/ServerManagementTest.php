<?php

namespace Tests\Feature\Infrastructure;

use App\Enums\ServerAuthType;
use App\Models\Server;
use App\Models\User;
use App\Services\Ssh\ConnectionTester;
use App\Services\Ssh\ConnectionTestResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_view_servers(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('servers.index'))->assertForbidden();
    }

    public function test_admin_can_create_a_key_authenticated_server(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::servers.create')
            ->set('name', 'Prod web')
            ->set('host', '192.168.1.10')
            ->set('port', 22)
            ->set('username', 'deploy')
            ->set('auth_type', 'key')
            ->set('private_key', 'fake-key')
            ->call('save')
            ->assertHasNoErrors();

        $server = Server::where('name', 'Prod web')->firstOrFail();

        $this->assertSame(ServerAuthType::Key, $server->auth_type);
        $this->assertSame('fake-key', $server->private_key);
    }

    public function test_password_is_required_when_auth_type_is_password(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::servers.create')
            ->set('name', 'Prod web')
            ->set('host', '192.168.1.10')
            ->set('username', 'deploy')
            ->set('auth_type', 'password')
            ->call('save')
            ->assertHasErrors(['password']);
    }

    public function test_private_key_is_required_when_auth_type_is_key(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::servers.create')
            ->set('name', 'Prod web')
            ->set('host', '192.168.1.10')
            ->set('username', 'deploy')
            ->set('auth_type', 'key')
            ->call('save')
            ->assertHasErrors(['private_key']);
    }

    public function test_admin_can_delete_a_server(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $server = Server::create([
            'name' => 'A', 'host' => 'a.example.com', 'port' => 22, 'auth_type' => 'key',
            'username' => 'deploy', 'private_key' => 'k',
        ]);

        Livewire::test('pages::servers.index')->call('deleteServer', $server);

        $this->assertNull($server->fresh());
    }

    public function test_test_connection_dispatches_a_notification(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->mock(ConnectionTester::class, function ($mock) {
            $mock->shouldReceive('testServer')->once()->andReturn(ConnectionTestResult::failure('nope'));
        });

        Livewire::test('pages::servers.create')
            ->set('name', 'Prod web')
            ->set('host', 'example.com')
            ->set('username', 'deploy')
            ->set('auth_type', 'key')
            ->set('private_key', 'fake-key')
            ->call('testConnection')
            ->assertDispatched('notify', text: 'nope', variant: 'danger');
    }
}

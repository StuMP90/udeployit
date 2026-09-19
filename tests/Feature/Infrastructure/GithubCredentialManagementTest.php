<?php

namespace Tests\Feature\Infrastructure;

use App\Models\GithubCredential;
use App\Models\User;
use App\Services\Ssh\ConnectionTester;
use App\Services\Ssh\ConnectionTestResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GithubCredentialManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_view_github_credentials(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('github-credentials.index'))->assertForbidden();
    }

    public function test_admin_can_create_a_credential(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::github-credentials.create')
            ->set('name', 'Deploy key')
            ->set('private_key', "-----BEGIN OPENSSH PRIVATE KEY-----\nfake\n-----END OPENSSH PRIVATE KEY-----")
            ->set('is_default', true)
            ->call('save')
            ->assertHasNoErrors();

        $credential = GithubCredential::where('name', 'Deploy key')->firstOrFail();

        $this->assertTrue($credential->is_default);
    }

    public function test_only_one_credential_can_be_default(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $first = GithubCredential::create(['name' => 'A', 'private_key' => 'key-a', 'is_default' => true]);
        $second = GithubCredential::create(['name' => 'B', 'private_key' => 'key-b', 'is_default' => true]);

        $this->assertFalse($first->refresh()->is_default);
        $this->assertTrue($second->refresh()->is_default);
    }

    public function test_admin_can_delete_a_credential(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $credential = GithubCredential::create(['name' => 'A', 'private_key' => 'key-a']);

        Livewire::test('pages::github-credentials.index')->call('deleteCredential', $credential);

        $this->assertNull($credential->fresh());
    }

    public function test_test_connection_dispatches_a_notification(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->mock(ConnectionTester::class, function ($mock) {
            $mock->shouldReceive('testGithubCredential')->once()->andReturn(ConnectionTestResult::success('ok'));
        });

        Livewire::test('pages::github-credentials.create')
            ->set('private_key', 'fake-key')
            ->call('testConnection')
            ->assertDispatched('notify', text: 'ok', variant: 'success');
    }
}

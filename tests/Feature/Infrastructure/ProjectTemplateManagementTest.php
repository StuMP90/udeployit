<?php

namespace Tests\Feature\Infrastructure;

use App\Models\ProjectTemplate;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectTemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_view_templates(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('templates.index'))->assertForbidden();
    }

    public function test_admin_can_create_a_template(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::templates.create')
            ->set('name', 'Standard web app')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project_templates', ['name' => 'Standard web app']);
    }

    public function test_admin_can_attach_a_server_with_a_default_path(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $template = ProjectTemplate::create(['name' => 'Standard']);
        $server = Server::create([
            'name' => 'Prod', 'host' => 'a.example.com', 'port' => 22, 'auth_type' => 'key',
            'username' => 'deploy', 'private_key' => 'k',
        ]);

        Livewire::test('pages::templates.edit', ['template' => $template])
            ->set('newServerId', (string) $server->id)
            ->set('newDefaultPath', '/var/www/app')
            ->call('addServer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('template_servers', [
            'project_template_id' => $template->id,
            'server_id' => $server->id,
            'default_deployment_path' => '/var/www/app',
        ]);
    }

    public function test_admin_can_update_a_servers_default_path(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $template = ProjectTemplate::create(['name' => 'Standard']);
        $server = Server::create([
            'name' => 'Prod', 'host' => 'a.example.com', 'port' => 22, 'auth_type' => 'key',
            'username' => 'deploy', 'private_key' => 'k',
        ]);
        $templateServer = $template->templateServers()->create([
            'server_id' => $server->id,
            'default_deployment_path' => '/old/path',
        ]);

        Livewire::test('pages::templates.edit', ['template' => $template])
            ->set("paths.{$templateServer->id}", '/new/path')
            ->call('updatePath', $templateServer->id);

        $this->assertSame('/new/path', $templateServer->fresh()->default_deployment_path);
    }

    public function test_admin_can_remove_a_server_from_a_template(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $template = ProjectTemplate::create(['name' => 'Standard']);
        $server = Server::create([
            'name' => 'Prod', 'host' => 'a.example.com', 'port' => 22, 'auth_type' => 'key',
            'username' => 'deploy', 'private_key' => 'k',
        ]);
        $templateServer = $template->templateServers()->create(['server_id' => $server->id]);

        Livewire::test('pages::templates.edit', ['template' => $template])
            ->call('removeServer', $templateServer->id);

        $this->assertNull($templateServer->fresh());
    }
}

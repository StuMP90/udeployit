<?php

use App\Exceptions\GitRepositoryException;
use App\Models\GithubCredential;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\Server;
use App\Services\Git\GitRepositoryService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Project')] class extends Component {
    public Project $project;

    public string $name = '';
    public string $repo_url = '';
    public string $github_credential_id = '';
    public string $project_template_id = '';

    public string $newServerId = '';
    public string $newBranch = '';
    public string $newDeploymentPath = '';
    public bool $newAutoDeploy = false;

    public array $branch = [];

    public array $deploymentPath = [];

    public array $autoDeploy = [];

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->name = $project->name;
        $this->repo_url = $project->repo_url;
        $this->github_credential_id = (string) $project->github_credential_id;
        $this->project_template_id = (string) $project->project_template_id;

        foreach ($project->projectServers as $projectServer) {
            $this->branch[$projectServer->id] = (string) $projectServer->branch;
            $this->deploymentPath[$projectServer->id] = (string) $projectServer->deployment_path;
            $this->autoDeploy[$projectServer->id] = $projectServer->auto_deploy;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'repo_url' => ['required', 'string', 'max:255'],
            'github_credential_id' => ['nullable', 'integer', 'exists:github_credentials,id'],
            'project_template_id' => ['nullable', 'integer', 'exists:project_templates,id'],
        ]);

        $this->project->update([
            'name' => $validated['name'],
            'repo_url' => $validated['repo_url'],
            'github_credential_id' => $validated['github_credential_id'] ?: null,
            'project_template_id' => $validated['project_template_id'] ?: null,
        ]);

        $this->dispatch('notify', text: __('Project updated.'));
    }

    public function refreshBranches(): void
    {
        try {
            app(GitRepositoryService::class)->syncBranches($this->project);
            unset($this->project->projectBranches);
            $this->dispatch('notify', text: __('Branches refreshed.'));
        } catch (GitRepositoryException $e) {
            $this->dispatch('notify', text: $e->getMessage(), variant: 'danger');
        }
    }

    public function deleteProject(): void
    {
        Gate::authorize('manage-projects');

        $this->project->delete();

        $this->redirect(route('projects.index'), navigate: true);
    }

    public function addServer(): void
    {
        $validated = $this->validate([
            'newServerId' => ['required', 'integer', 'exists:servers,id'],
            'newBranch' => ['nullable', 'string', 'max:255'],
            'newDeploymentPath' => ['nullable', 'string', 'max:255'],
        ]);

        $projectServer = $this->project->projectServers()->create([
            'server_id' => $validated['newServerId'],
            'branch' => $validated['newBranch'] ?: null,
            'deployment_path' => $validated['newDeploymentPath'] ?: null,
            'auto_deploy' => $this->newAutoDeploy,
        ]);

        $this->branch[$projectServer->id] = (string) $projectServer->branch;
        $this->deploymentPath[$projectServer->id] = (string) $projectServer->deployment_path;
        $this->autoDeploy[$projectServer->id] = $projectServer->auto_deploy;

        $this->newServerId = '';
        $this->newBranch = '';
        $this->newDeploymentPath = '';
        $this->newAutoDeploy = false;

        unset($this->project->projectServers);
    }

    public function updateServer(int $projectServerId): void
    {
        $projectServer = $this->project->projectServers()->findOrFail($projectServerId);

        $projectServer->update([
            'branch' => $this->branch[$projectServerId] ?: null,
            'deployment_path' => $this->deploymentPath[$projectServerId] ?: null,
            'auto_deploy' => (bool) ($this->autoDeploy[$projectServerId] ?? false),
        ]);

        $this->dispatch('notify', text: __('Server association updated.'));
    }

    public function removeServer(int $projectServerId): void
    {
        $this->project->projectServers()->findOrFail($projectServerId)->delete();

        unset($this->branch[$projectServerId], $this->deploymentPath[$projectServerId], $this->autoDeploy[$projectServerId], $this->project->projectServers);
    }

    #[Computed]
    public function githubCredentials()
    {
        return GithubCredential::orderBy('name')->get();
    }

    #[Computed]
    public function projectTemplates()
    {
        return ProjectTemplate::orderBy('name')->get();
    }

    #[Computed]
    public function availableServers()
    {
        return Server::whereNotIn('id', $this->project->projectServers->pluck('server_id'))
            ->orderBy('name')
            ->get();
    }
}; ?>

<div class="max-w-3xl space-y-10">
    <div>
        <div class="flex items-center justify-between">
            <div>
                <x-ui.heading size="xl" level="1">{{ $project->name }}</x-ui.heading>
                <x-ui.subheading class="mb-6">{{ $project->repo_url }}</x-ui.subheading>
            </div>

            @can('manage-projects')
                <button
                    type="button"
                    wire:click="deleteProject"
                    wire:confirm="{{ __('Delete this project? This cannot be undone.') }}"
                    class="text-sm text-red-600 hover:underline dark:text-red-400"
                >
                    {{ __('Delete project') }}
                </button>
            @endcan
        </div>

        <form wire:submit="save" class="space-y-6">
            <x-ui.input wire:model="name" name="name" :label="__('Name')" type="text" required />

            <x-ui.input wire:model="repo_url" name="repo_url" :label="__('Repository (SSH URL)')" type="text" required />

            <x-ui.select wire:model="github_credential_id" name="github_credential_id" :label="__('GitHub credential (optional override)')">
                <option value="">{{ __('Use the global default') }}</option>
                @foreach ($this->githubCredentials as $credential)
                    <option value="{{ $credential->id }}">{{ $credential->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select wire:model="project_template_id" name="project_template_id" :label="__('Project template (optional)')">
                <option value="">{{ __('None') }}</option>
                @foreach ($this->projectTemplates as $template)
                    <option value="{{ $template->id }}">{{ $template->name }}</option>
                @endforeach
            </x-ui.select>

            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
        </form>
    </div>

    <div class="border-t border-zinc-200 pt-6 dark:border-zinc-700">
        <div class="flex items-center justify-between">
            <div>
                <x-ui.heading size="md">{{ __('Branches') }}</x-ui.heading>
                <x-ui.subheading class="mb-4">{{ __('Latest known commit per branch.') }}</x-ui.subheading>
            </div>

            <flux:button type="button" wire:click="refreshBranches">{{ __('Refresh branches') }}</flux:button>
        </div>

        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-start text-sm">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">{{ __('Branch') }}</th>
                        <th class="px-4 py-3 text-start font-medium">{{ __('Latest commit') }}</th>
                        <th class="px-4 py-3 text-start font-medium">{{ __('Committed') }}</th>
                        <th class="px-4 py-3 text-start font-medium">{{ __('Snapshot at creation') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($project->projectBranches as $projectBranch)
                        <tr wire:key="branch-{{ $projectBranch->id }}">
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $projectBranch->branch_name }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ substr($projectBranch->latest_sha, 0, 10) }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $projectBranch->latest_committed_at?->diffForHumans() }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ substr($projectBranch->created_snapshot_sha, 0, 10) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">
                                {{ __('No branches found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="border-t border-zinc-200 pt-6 dark:border-zinc-700">
        <x-ui.heading size="md">{{ __('Servers') }}</x-ui.heading>
        <x-ui.subheading class="mb-4">{{ __('Each server deploys one branch. Enable auto-deploy to deploy automatically when that branch updates.') }}</x-ui.subheading>

        <div class="space-y-3">
            @forelse ($project->projectServers as $projectServer)
                <div wire:key="project-server-{{ $projectServer->id }}" class="space-y-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <div class="flex items-end gap-3">
                        <div class="w-36 shrink-0 pb-2 text-sm font-medium text-zinc-900 dark:text-white">
                            {{ $projectServer->server->name }}
                        </div>

                        <x-ui.input wire:model="branch.{{ $projectServer->id }}" :label="__('Branch')" type="text" class="w-40" />
                        <x-ui.input wire:model="deploymentPath.{{ $projectServer->id }}" :label="__('Deployment path')" type="text" class="flex-1" />

                        <label class="flex items-center gap-2 pb-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="checkbox" wire:model="autoDeploy.{{ $projectServer->id }}" class="size-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800" />
                            {{ __('Auto-deploy') }}
                        </label>
                    </div>

                    <div class="flex items-center gap-3">
                        <flux:button type="button" wire:click="updateServer({{ $projectServer->id }})">{{ __('Save') }}</flux:button>

                        <button
                            type="button"
                            wire:click="removeServer({{ $projectServer->id }})"
                            wire:confirm="{{ __('Remove this server from the project?') }}"
                            class="text-sm text-red-600 hover:underline dark:text-red-400"
                        >
                            {{ __('Remove') }}
                        </button>
                    </div>
                </div>
            @empty
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No servers attached yet.') }}</p>
            @endforelse
        </div>

        @if ($this->availableServers->isNotEmpty())
            <div class="mt-6 flex items-end gap-3 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                <x-ui.select wire:model="newServerId" name="newServerId" :label="__('Add a server')" class="w-44">
                    <option value="">{{ __('Choose a server') }}</option>
                    @foreach ($this->availableServers as $server)
                        <option value="{{ $server->id }}">{{ $server->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.input wire:model="newBranch" name="newBranch" :label="__('Branch')" type="text" class="w-40" />
                <x-ui.input wire:model="newDeploymentPath" name="newDeploymentPath" :label="__('Deployment path')" type="text" class="flex-1" />

                <label class="flex items-center gap-2 pb-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <input type="checkbox" wire:model="newAutoDeploy" class="size-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800" />
                    {{ __('Auto-deploy') }}
                </label>

                <flux:button type="button" wire:click="addServer">{{ __('Add') }}</flux:button>
            </div>
        @endif
    </div>
</div>

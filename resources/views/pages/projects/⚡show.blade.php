<?php

use App\Enums\DeploymentScriptType;
use App\Enums\ScriptFailureAction;
use App\Exceptions\GitRepositoryException;
use App\Models\GithubCredential;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\Server;
use App\Services\Deployment\DeploymentDispatcher;
use App\Services\Git\GitRepositoryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
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

    public array $scriptCommand = ['before' => '', 'after' => ''];

    public array $scriptTimeout = ['before' => 300, 'after' => 300];

    public array $scriptOnFailure = ['before' => 'abort', 'after' => 'abort'];

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

        foreach ($project->deploymentScripts as $script) {
            $this->scriptCommand[$script->type->value] = $script->command;
            $this->scriptTimeout[$script->type->value] = $script->timeout_seconds;
            $this->scriptOnFailure[$script->type->value] = $script->on_failure->value;
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

    public function saveScript(string $type): void
    {
        $type = DeploymentScriptType::from($type);

        $this->validate([
            "scriptCommand.{$type->value}" => ['required', 'string'],
            "scriptTimeout.{$type->value}" => ['required', 'integer', 'min:1', 'max:3600'],
            "scriptOnFailure.{$type->value}" => ['required', Rule::enum(ScriptFailureAction::class)],
        ]);

        $this->project->deploymentScripts()->updateOrCreate(
            ['type' => $type],
            [
                'command' => $this->scriptCommand[$type->value],
                'timeout_seconds' => $this->scriptTimeout[$type->value],
                'on_failure' => $this->scriptOnFailure[$type->value],
            ],
        );

        $this->dispatch('notify', text: __(':type script saved.', ['type' => $type->label()]));
    }

    public function removeScript(string $type): void
    {
        $type = DeploymentScriptType::from($type);

        $this->project->deploymentScripts()->where('type', $type)->delete();

        $this->scriptCommand[$type->value] = '';
        $this->scriptTimeout[$type->value] = 300;
        $this->scriptOnFailure[$type->value] = 'abort';

        $this->dispatch('notify', text: __(':type script removed.', ['type' => $type->label()]));
    }

    public function deploy(int $projectServerId, string $mode = 'incremental'): void
    {
        $projectServer = $this->project->projectServers()->findOrFail($projectServerId);

        if (blank($projectServer->branch)) {
            $this->dispatch('notify', text: __('Set a branch for this server before deploying.'), variant: 'danger');

            return;
        }

        $projectBranch = $this->project->projectBranches()->where('branch_name', $projectServer->branch)->first();

        if (! $projectBranch) {
            $this->dispatch('notify', text: __('That branch was not found. Try refreshing branches.'), variant: 'danger');

            return;
        }

        app(DeploymentDispatcher::class)->dispatch($projectServer, $projectBranch, $mode, Auth::id());

        $this->dispatch('notify', text: __('Deployment started.'));
    }

    #[Computed]
    public function recentDeployments()
    {
        return $this->project->deployments()->with(['projectServer.server', 'triggeredBy'])->latest()->limit(20)->get();
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

<div>
    <div class="grid gap-8 lg:grid-cols-3 lg:items-start">
        <div class="lg:col-span-2 space-y-10">
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
                <div wire:key="project-server-{{ $projectServer->id }}" class="space-y-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $projectServer->server->name }}</p>
                            @if ($projectServer->last_deployed_sha)
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ __('Last deployed :time (:sha)', ['time' => $projectServer->last_deployed_at?->diffForHumans(), 'sha' => substr($projectServer->last_deployed_sha, 0, 10)]) }}
                                </p>
                            @else
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Never deployed') }}</p>
                            @endif
                        </div>

                        <button
                            type="button"
                            wire:click="removeServer({{ $projectServer->id }})"
                            wire:confirm="{{ __('Remove this server from the project?') }}"
                            class="text-sm text-red-600 hover:underline dark:text-red-400"
                        >
                            {{ __('Remove') }}
                        </button>
                    </div>

                    <div class="flex items-end gap-4">
                        <x-ui.input wire:model="branch.{{ $projectServer->id }}" :label="__('Branch')" type="text" class="w-40 shrink-0" />
                        <x-ui.input wire:model="deploymentPath.{{ $projectServer->id }}" :label="__('Deployment path')" type="text" class="min-w-0 flex-1" />

                        <label class="flex h-[38px] shrink-0 items-center gap-2 whitespace-nowrap text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="checkbox" wire:model="autoDeploy.{{ $projectServer->id }}" class="size-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800" />
                            {{ __('Auto-deploy') }}
                        </label>
                    </div>

                    <div class="flex items-center gap-3">
                        <flux:button type="button" wire:click="updateServer({{ $projectServer->id }})">{{ __('Save') }}</flux:button>

                        @if ($projectServer->branch)
                            @if ($projectServer->last_deployed_sha)
                                <flux:button
                                    type="button"
                                    variant="primary"
                                    wire:click="deploy({{ $projectServer->id }})"
                                    wire:confirm="{{ __('Deploy the latest commit on :branch to :server?', ['branch' => $projectServer->branch, 'server' => $projectServer->server->name]) }}"
                                >
                                    {{ __('Deploy') }}
                                </flux:button>
                            @else
                                <flux:button
                                    type="button"
                                    variant="primary"
                                    wire:click="deploy({{ $projectServer->id }}, 'full')"
                                    wire:confirm="{{ __('This is the first deploy to :server. Upload every file?', ['server' => $projectServer->server->name]) }}"
                                >
                                    {{ __('Full deploy') }}
                                </flux:button>

                                <flux:button
                                    type="button"
                                    wire:click="deploy({{ $projectServer->id }}, 'incremental')"
                                    wire:confirm="{{ __('This is the first deploy to :server. Only upload changes since the project was created?', ['server' => $projectServer->server->name]) }}"
                                >
                                    {{ __('Incremental from creation') }}
                                </flux:button>
                            @endif
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No servers attached yet.') }}</p>
            @endforelse
        </div>

        @if ($this->availableServers->isNotEmpty())
            <div class="mt-6 flex items-end gap-4 rounded-lg border border-dashed border-zinc-300 p-4 dark:border-zinc-600">
                <x-ui.select wire:model="newServerId" name="newServerId" :label="__('Add a server')" class="w-44 shrink-0">
                    <option value="">{{ __('Choose a server') }}</option>
                    @foreach ($this->availableServers as $server)
                        <option value="{{ $server->id }}">{{ $server->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.input wire:model="newBranch" name="newBranch" :label="__('Branch')" type="text" class="w-40 shrink-0" />
                <x-ui.input wire:model="newDeploymentPath" name="newDeploymentPath" :label="__('Deployment path')" type="text" class="min-w-0 flex-1" />

                <label class="flex h-[38px] shrink-0 items-center gap-2 whitespace-nowrap text-sm text-zinc-700 dark:text-zinc-300">
                    <input type="checkbox" wire:model="newAutoDeploy" class="size-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800" />
                    {{ __('Auto-deploy') }}
                </label>

                <flux:button type="button" wire:click="addServer">{{ __('Add') }}</flux:button>
            </div>
        @endif
    </div>

    <div class="border-t border-zinc-200 pt-6 dark:border-zinc-700">
        <x-ui.heading size="md">{{ __('Deployment scripts') }}</x-ui.heading>
        <x-ui.subheading class="mb-4">{{ __('Optional shell commands run over SSH on the target server, before and after files are uploaded.') }}</x-ui.subheading>

        <div class="mb-6 rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-xs text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
            {{ __('These run as a single non-interactive SSH command — chain multiple steps with') }}
            <code class="rounded bg-zinc-200 px-1 py-0.5 font-mono dark:bg-zinc-800">&amp;&amp;</code>
            {{ __('(stop on the first failure) or') }}
            <code class="rounded bg-zinc-200 px-1 py-0.5 font-mono dark:bg-zinc-800">;</code>
            {{ __('(run every step regardless), e.g.') }}
            <code class="rounded bg-zinc-200 px-1 py-0.5 font-mono dark:bg-zinc-800">cd /var/www/app &amp;&amp; npm install &amp;&amp; npm run build</code>.
            {{ __("It won't have a login shell's environment (no sourced .bashrc/.profile) unless your command sources it explicitly.") }}
        </div>

        <div class="space-y-6">
            @foreach (\App\Enums\DeploymentScriptType::cases() as $scriptType)
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <x-ui.heading size="sm">{{ $scriptType->label() }}</x-ui.heading>

                    <div class="mt-4 space-y-4">
                        <x-ui.textarea wire:model="scriptCommand.{{ $scriptType->value }}" :label="__('Command')" rows="4" placeholder="cd /var/www/app && npm run build" />

                        <div class="flex items-end gap-4">
                            <x-ui.input wire:model="scriptTimeout.{{ $scriptType->value }}" :label="__('Timeout (seconds)')" type="number" min="1" max="3600" class="w-40 shrink-0" />

                            <x-ui.select wire:model="scriptOnFailure.{{ $scriptType->value }}" :label="__('If it fails')" class="w-64 shrink-0">
                                @foreach (\App\Enums\ScriptFailureAction::cases() as $failureAction)
                                    <option value="{{ $failureAction->value }}">{{ $failureAction->label() }}</option>
                                @endforeach
                            </x-ui.select>

                            <flux:button type="button" wire:click="saveScript('{{ $scriptType->value }}')">{{ __('Save') }}</flux:button>

                            @if ($project->deploymentScripts->firstWhere('type', $scriptType))
                                <button
                                    type="button"
                                    wire:click="removeScript('{{ $scriptType->value }}')"
                                    wire:confirm="{{ __('Remove this script?') }}"
                                    class="ml-auto pb-2 text-sm text-red-600 hover:underline dark:text-red-400"
                                >
                                    {{ __('Remove') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
        </div>

        <div class="lg:sticky lg:top-6">
            <div class="flex items-center justify-between">
                <x-ui.heading size="md">{{ __('Recent deployments') }}</x-ui.heading>
                <x-ui.link href="{{ route('projects.deployments', $project) }}" wire:navigate class="text-sm">{{ __('View all') }}</x-ui.link>
            </div>

            <div class="mt-4 divide-y divide-zinc-200 rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                @forelse ($this->recentDeployments as $deployment)
                    <a href="{{ route('deployments.show', $deployment) }}" wire:navigate wire:key="deployment-{{ $deployment->id }}" class="block px-4 py-3 text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $deployment->projectServer->server->name }}</span>
                            <x-ui.badge :color="match ($deployment->status->value) { 'success' => 'green', 'failed' => 'red', default => 'zinc' }">
                                {{ $deployment->status->label() }}
                            </x-ui.badge>
                        </div>
                        <div class="mt-1 flex items-center justify-between gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                            <span class="font-mono">{{ substr($deployment->commit_sha, 0, 10) }}</span>
                            <span>{{ $deployment->created_at?->diffForHumans() }}</span>
                        </div>
                    </a>
                @empty
                    <p class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('No deployments yet.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

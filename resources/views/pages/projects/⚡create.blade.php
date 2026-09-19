<?php

use App\Exceptions\GitRepositoryException;
use App\Models\GithubCredential;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Services\Git\GitRepositoryService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Add project')] class extends Component {
    public string $name = '';
    public string $repo_url = '';
    public string $github_credential_id = '';
    public string $project_template_id = '';

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'repo_url' => ['required', 'string', 'max:255'],
            'github_credential_id' => ['nullable', 'integer', 'exists:github_credentials,id'],
            'project_template_id' => ['nullable', 'integer', 'exists:project_templates,id'],
        ]);

        $project = Project::create([
            'name' => $validated['name'],
            'repo_url' => $validated['repo_url'],
            'github_credential_id' => $validated['github_credential_id'] ?: null,
            'project_template_id' => $validated['project_template_id'] ?: null,
            'created_by' => Auth::id(),
        ]);

        try {
            app(GitRepositoryService::class)->syncBranches($project);
        } catch (GitRepositoryException $e) {
            $project->delete();
            $this->addError('repo_url', __('Could not read this repository: :message', ['message' => $e->getMessage()]));

            return;
        }

        if ($project->project_template_id) {
            foreach ($project->projectTemplate->templateServers as $templateServer) {
                $project->projectServers()->create([
                    'server_id' => $templateServer->server_id,
                    'deployment_path' => $templateServer->default_deployment_path,
                ]);
            }
        }

        $this->redirect(route('projects.show', $project), navigate: true);
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
}; ?>

<div class="max-w-2xl">
    <x-ui.heading size="xl" level="1">{{ __('Add project') }}</x-ui.heading>
    <x-ui.subheading class="mb-6">{{ __('Connect a repository. Branches and their latest commits are checked automatically.') }}</x-ui.subheading>

    <form wire:submit="save" class="space-y-6">
        <x-ui.input wire:model="name" name="name" :label="__('Name')" type="text" required autofocus />

        <x-ui.input wire:model="repo_url" name="repo_url" :label="__('Repository (SSH URL)')" type="text" placeholder="git@github.com:org/repo.git" required />

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

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">{{ __('Create project') }}</flux:button>
            <flux:button variant="ghost" href="{{ route('projects.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</div>

<?php

use App\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Projects')] class extends Component {
    #[Computed]
    public function projects()
    {
        return Project::withCount(['projectBranches', 'projectServers'])->orderBy('name')->get();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <x-ui.heading size="xl" level="1">{{ __('Projects') }}</x-ui.heading>
            <x-ui.subheading>{{ __('Repositories connected to uDeployIt.') }}</x-ui.subheading>
        </div>

        @can('manage-projects')
            <flux:button variant="primary" href="{{ route('projects.create') }}" wire:navigate>
                {{ __('Add project') }}
            </flux:button>
        @endcan
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-start text-sm">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3 text-start font-medium">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-start font-medium">{{ __('Repository') }}</th>
                    <th class="px-4 py-3 text-start font-medium">{{ __('Branches') }}</th>
                    <th class="px-4 py-3 text-start font-medium">{{ __('Servers') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($this->projects as $project)
                    <tr wire:key="project-{{ $project->id }}">
                        <td class="px-4 py-3 font-medium">
                            <a href="{{ route('projects.show', $project) }}" wire:navigate class="text-zinc-900 hover:underline dark:text-white">{{ $project->name }}</a>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $project->repo_url }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $project->project_branches_count }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $project->project_servers_count }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('No projects yet.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

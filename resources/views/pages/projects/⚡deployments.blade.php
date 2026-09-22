<?php

use App\Models\Project;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Deployments')] class extends Component {
    use WithPagination;

    public Project $project;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function with(): array
    {
        return [
            'deployments' => $this->project->deployments()
                ->with(['projectServer.server', 'triggeredBy'])
                ->latest()
                ->paginate(30),
        ];
    }
}; ?>

<div class="max-w-4xl flex flex-col gap-6">
    <div>
        <x-ui.link href="{{ route('projects.show', $project) }}" wire:navigate>&larr; {{ $project->name }}</x-ui.link>

        <x-ui.heading size="xl" level="1" class="mt-2">{{ __('Deployments') }}</x-ui.heading>
        <x-ui.subheading>{{ __('Every deployment for this project, newest first.') }}</x-ui.subheading>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-start text-sm">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3 text-start font-medium">{{ __('Server') }}</th>
                    <th class="px-4 py-3 text-start font-medium">{{ __('Commit') }}</th>
                    <th class="px-4 py-3 text-start font-medium">{{ __('Type') }}</th>
                    <th class="px-4 py-3 text-start font-medium">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-start font-medium">{{ __('Triggered') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($deployments as $deployment)
                    <tr wire:key="deployment-{{ $deployment->id }}">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $deployment->projectServer->server->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ substr($deployment->commit_sha, 0, 10) }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ ucfirst($deployment->type->value) }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :color="match ($deployment->status->value) { 'success' => 'green', 'failed' => 'red', default => 'zinc' }">
                                {{ $deployment->status->label() }}
                            </x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ $deployment->created_at?->diffForHumans() }}
                            @if ($deployment->triggeredBy)
                                {{ __('by :name', ['name' => $deployment->triggeredBy->name]) }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end">
                            <x-ui.link href="{{ route('deployments.show', $deployment) }}" wire:navigate>{{ __('View log') }}</x-ui.link>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('No deployments yet.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $deployments->links() }}
</div>

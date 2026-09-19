<?php

use App\Models\ProjectTemplate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Project templates')] class extends Component {
    public function deleteTemplate(ProjectTemplate $template): void
    {
        $template->delete();

        $this->dispatch('notify', text: __('Template deleted.'));
    }

    #[Computed]
    public function templates()
    {
        return ProjectTemplate::withCount('templateServers')->orderBy('name')->get();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <x-ui.heading size="xl" level="1">{{ __('Project templates') }}</x-ui.heading>
            <x-ui.subheading>{{ __('Reusable server bundles for quickly setting up new projects.') }}</x-ui.subheading>
        </div>

        <flux:button variant="primary" href="{{ route('templates.create') }}" wire:navigate>
            {{ __('Add template') }}
        </flux:button>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-start text-sm">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3 text-start font-medium">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-start font-medium">{{ __('Servers') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($this->templates as $template)
                    <tr wire:key="template-{{ $template->id }}">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $template->name }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $template->template_servers_count }}</td>
                        <td class="px-4 py-3 text-end">
                            <div class="flex items-center justify-end gap-3">
                                <x-ui.link href="{{ route('templates.edit', $template) }}" wire:navigate>{{ __('Edit') }}</x-ui.link>

                                <button
                                    type="button"
                                    wire:click="deleteTemplate({{ $template->id }})"
                                    wire:confirm="{{ __('Delete this template? This cannot be undone.') }}"
                                    class="text-red-600 hover:underline dark:text-red-400"
                                >
                                    {{ __('Delete') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('No templates yet.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

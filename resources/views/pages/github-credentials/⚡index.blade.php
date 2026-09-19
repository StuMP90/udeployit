<?php

use App\Models\GithubCredential;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('GitHub credentials')] class extends Component {
    public function deleteCredential(GithubCredential $credential): void
    {
        $credential->delete();

        $this->dispatch('notify', text: __('Credential deleted.'));
    }

    #[Computed]
    public function credentials()
    {
        return GithubCredential::orderBy('name')->get();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <x-ui.heading size="xl" level="1">{{ __('GitHub credentials') }}</x-ui.heading>
            <x-ui.subheading>{{ __('SSH keys used to read your repositories. Mark one as the global default.') }}</x-ui.subheading>
        </div>

        <flux:button variant="primary" href="{{ route('github-credentials.create') }}" wire:navigate>
            {{ __('Add credential') }}
        </flux:button>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-start text-sm">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3 text-start font-medium">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-start font-medium">{{ __('Default') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($this->credentials as $credential)
                    <tr wire:key="credential-{{ $credential->id }}">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $credential->name }}</td>
                        <td class="px-4 py-3">
                            @if ($credential->is_default)
                                <x-ui.badge color="green">{{ __('Default') }}</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="flex items-center justify-end gap-3">
                                <x-ui.link href="{{ route('github-credentials.edit', $credential) }}" wire:navigate>{{ __('Edit') }}</x-ui.link>

                                <button
                                    type="button"
                                    wire:click="deleteCredential({{ $credential->id }})"
                                    wire:confirm="{{ __('Delete this credential? This cannot be undone.') }}"
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
                            {{ __('No GitHub credentials yet.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

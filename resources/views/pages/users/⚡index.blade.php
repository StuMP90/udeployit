<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Users')] class extends Component {
    public function deleteUser(User $user): void
    {
        if ($user->is(Auth::user())) {
            $this->dispatch('notify', text: __('You cannot delete your own account.'), variant: 'danger');

            return;
        }

        if ($user->role === UserRole::Admin && User::where('role', UserRole::Admin)->count() <= 1) {
            $this->dispatch('notify', text: __('You cannot delete the last remaining admin.'), variant: 'danger');

            return;
        }

        $user->delete();

        $this->dispatch('notify', text: __('User deleted.'));
    }

    #[Computed]
    public function users()
    {
        return User::orderBy('name')->get();
    }
}; ?>

<div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <x-ui.heading size="xl" level="1">{{ __('Users') }}</x-ui.heading>
                <x-ui.subheading>{{ __('Only admins can add, edit, or remove users.') }}</x-ui.subheading>
            </div>

            <flux:button variant="primary" href="{{ route('users.create') }}" wire:navigate>
                {{ __('Add user') }}
            </flux:button>
        </div>

        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-start text-sm">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">{{ __('Name') }}</th>
                        <th class="px-4 py-3 text-start font-medium">{{ __('Username') }}</th>
                        <th class="px-4 py-3 text-start font-medium">{{ __('Email') }}</th>
                        <th class="px-4 py-3 text-start font-medium">{{ __('Role') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($this->users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ '@'.$user->username }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $user->email ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <x-ui.badge :color="$user->role === UserRole::Admin ? 'green' : 'zinc'">
                                    {{ $user->role->label() }}
                                </x-ui.badge>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <div class="flex items-center justify-end gap-3">
                                    <x-ui.link href="{{ route('users.edit', $user) }}" wire:navigate>{{ __('Edit') }}</x-ui.link>

                                    <button
                                        type="button"
                                        wire:click="deleteUser({{ $user->id }})"
                                        wire:confirm="{{ __('Delete this user? This cannot be undone.') }}"
                                        class="text-red-600 hover:underline dark:text-red-400"
                                    >
                                        {{ __('Delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

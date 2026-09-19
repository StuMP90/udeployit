<?php

use App\Models\Server;
use App\Services\Ssh\ConnectionTester;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Servers')] class extends Component {
    public function deleteServer(Server $server): void
    {
        $server->delete();

        $this->dispatch('notify', text: __('Server deleted.'));
    }

    public function testConnection(Server $server): void
    {
        $result = app(ConnectionTester::class)->testServer($server);

        $this->dispatch('notify', text: $result->message, variant: $result->ok ? 'success' : 'danger');
    }

    #[Computed]
    public function servers()
    {
        return Server::orderBy('name')->get();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <x-ui.heading size="xl" level="1">{{ __('Servers') }}</x-ui.heading>
            <x-ui.subheading>{{ __('Deployment targets, reusable across projects and templates.') }}</x-ui.subheading>
        </div>

        <flux:button variant="primary" href="{{ route('servers.create') }}" wire:navigate>
            {{ __('Add server') }}
        </flux:button>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-start text-sm">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3 text-start font-medium">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-start font-medium">{{ __('Host') }}</th>
                    <th class="px-4 py-3 text-start font-medium">{{ __('Auth') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($this->servers as $server)
                    <tr wire:key="server-{{ $server->id }}">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $server->name }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $server->username }}@{{ $server->host }}:{{ $server->port }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge>{{ $server->auth_type->label() }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="flex items-center justify-end gap-3">
                                <button type="button" wire:click="testConnection({{ $server->id }})" class="text-zinc-600 hover:underline dark:text-zinc-400">
                                    {{ __('Test') }}
                                </button>

                                <x-ui.link href="{{ route('servers.edit', $server) }}" wire:navigate>{{ __('Edit') }}</x-ui.link>

                                <button
                                    type="button"
                                    wire:click="deleteServer({{ $server->id }})"
                                    wire:confirm="{{ __('Delete this server? This cannot be undone.') }}"
                                    class="text-red-600 hover:underline dark:text-red-400"
                                >
                                    {{ __('Delete') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('No servers yet.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

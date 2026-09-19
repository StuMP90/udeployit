<?php

use App\Models\ProjectTemplate;
use App\Models\Server;
use App\Models\TemplateServer;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit template')] class extends Component {
    public ProjectTemplate $template;

    public string $name = '';

    public string $newServerId = '';

    public string $newDefaultPath = '';

    public array $paths = [];

    public function mount(ProjectTemplate $template): void
    {
        $this->template = $template;
        $this->name = $template->name;

        foreach ($template->templateServers as $templateServer) {
            $this->paths[$templateServer->id] = (string) $templateServer->default_deployment_path;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $this->template->update($validated);

        $this->dispatch('notify', text: __('Template updated.'));
    }

    public function addServer(): void
    {
        $validated = $this->validate([
            'newServerId' => ['required', 'integer', 'exists:servers,id'],
            'newDefaultPath' => ['nullable', 'string', 'max:255'],
        ]);

        $templateServer = TemplateServer::create([
            'project_template_id' => $this->template->id,
            'server_id' => $validated['newServerId'],
            'default_deployment_path' => $validated['newDefaultPath'],
        ]);

        $this->paths[$templateServer->id] = (string) $templateServer->default_deployment_path;
        $this->newServerId = '';
        $this->newDefaultPath = '';

        unset($this->template->templateServers);
    }

    public function updatePath(int $templateServerId): void
    {
        $templateServer = $this->template->templateServers()->findOrFail($templateServerId);
        $templateServer->update(['default_deployment_path' => $this->paths[$templateServerId] ?? null]);

        $this->dispatch('notify', text: __('Path updated.'));
    }

    public function removeServer(int $templateServerId): void
    {
        $this->template->templateServers()->findOrFail($templateServerId)->delete();

        unset($this->paths[$templateServerId], $this->template->templateServers);
    }

    #[Computed]
    public function availableServers()
    {
        return Server::whereNotIn('id', $this->template->templateServers->pluck('server_id'))
            ->orderBy('name')
            ->get();
    }
}; ?>

<div class="max-w-2xl space-y-10">
    <div>
        <x-ui.heading size="xl" level="1">{{ __('Edit template') }}</x-ui.heading>
        <x-ui.subheading class="mb-6">{{ $template->name }}</x-ui.subheading>

        <form wire:submit="save" class="space-y-6">
            <x-ui.input wire:model="name" name="name" :label="__('Name')" type="text" required autofocus />

            <div class="flex items-center gap-3">
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
                <flux:button variant="ghost" href="{{ route('templates.index') }}" wire:navigate>{{ __('Back') }}</flux:button>
            </div>
        </form>
    </div>

    <div class="border-t border-zinc-200 pt-6 dark:border-zinc-700">
        <x-ui.heading size="md">{{ __('Servers') }}</x-ui.heading>
        <x-ui.subheading class="mb-4">{{ __('Servers in this template, with a default deployment path each.') }}</x-ui.subheading>

        <div class="space-y-3">
            @forelse ($template->templateServers as $templateServer)
                <div wire:key="template-server-{{ $templateServer->id }}" class="flex items-end gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <div class="w-40 shrink-0 pb-2 text-sm font-medium text-zinc-900 dark:text-white">
                        {{ $templateServer->server->name }}
                    </div>

                    <x-ui.input wire:model="paths.{{ $templateServer->id }}" :label="__('Default deployment path')" type="text" class="flex-1" />

                    <flux:button type="button" wire:click="updatePath({{ $templateServer->id }})">{{ __('Save') }}</flux:button>

                    <button
                        type="button"
                        wire:click="removeServer({{ $templateServer->id }})"
                        wire:confirm="{{ __('Remove this server from the template?') }}"
                        class="pb-2 text-sm text-red-600 hover:underline dark:text-red-400"
                    >
                        {{ __('Remove') }}
                    </button>
                </div>
            @empty
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No servers attached yet.') }}</p>
            @endforelse
        </div>

        @if ($this->availableServers->isNotEmpty())
            <div class="mt-6 flex items-end gap-3 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                <x-ui.select wire:model="newServerId" name="newServerId" :label="__('Add a server')" class="w-56">
                    <option value="">{{ __('Choose a server') }}</option>
                    @foreach ($this->availableServers as $server)
                        <option value="{{ $server->id }}">{{ $server->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.input wire:model="newDefaultPath" name="newDefaultPath" :label="__('Default deployment path')" type="text" class="flex-1" />

                <flux:button type="button" wire:click="addServer">{{ __('Add') }}</flux:button>
            </div>
        @endif
    </div>
</div>

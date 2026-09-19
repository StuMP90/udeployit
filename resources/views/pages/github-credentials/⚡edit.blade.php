<?php

use App\Models\GithubCredential;
use App\Services\Ssh\ConnectionTester;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit GitHub credential')] class extends Component {
    public GithubCredential $credential;

    public string $name = '';
    public string $private_key = '';
    public string $public_key = '';
    public bool $is_default = false;

    public function mount(GithubCredential $credential): void
    {
        $this->credential = $credential;
        $this->name = $credential->name;
        $this->private_key = $credential->private_key;
        $this->public_key = (string) $credential->public_key;
        $this->is_default = $credential->is_default;
    }

    public function testConnection(): void
    {
        $this->validate(['private_key' => ['required', 'string']]);

        $result = app(ConnectionTester::class)->testGithubCredential(
            new GithubCredential(['private_key' => $this->private_key]),
        );

        $this->dispatch('notify', text: $result->message, variant: $result->ok ? 'success' : 'danger');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'private_key' => ['required', 'string'],
            'public_key' => ['nullable', 'string'],
            'is_default' => ['boolean'],
        ]);

        $this->credential->update($validated);

        $this->dispatch('notify', text: __('Credential updated.'));
    }
}; ?>

<div class="max-w-2xl">
    <x-ui.heading size="xl" level="1">{{ __('Edit GitHub credential') }}</x-ui.heading>
    <x-ui.subheading class="mb-6">{{ $credential->name }}</x-ui.subheading>

    <form wire:submit="save" class="space-y-6">
        <x-ui.input wire:model="name" name="name" :label="__('Name')" type="text" required autofocus />

        <x-ui.textarea wire:model="private_key" name="private_key" :label="__('Private key (PEM)')" required />

        <x-ui.textarea wire:model="public_key" name="public_key" :label="__('Public key (optional, for reference)')" rows="3" />

        <x-ui.checkbox wire:model="is_default" name="is_default" :label="__('Use as the global default')" />

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            <flux:button type="button" wire:click="testConnection">{{ __('Test connection') }}</flux:button>
            <flux:button variant="ghost" href="{{ route('github-credentials.index') }}" wire:navigate>{{ __('Back') }}</flux:button>
        </div>
    </form>
</div>

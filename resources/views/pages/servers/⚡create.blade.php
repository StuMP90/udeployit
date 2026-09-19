<?php

use App\Enums\ServerAuthType;
use App\Models\Server;
use App\Services\Ssh\ConnectionTester;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Add server')] class extends Component {
    public string $name = '';
    public string $host = '';
    public int $port = 22;
    public string $auth_type = 'key';
    public string $username = '';
    public string $password = '';
    public string $private_key = '';
    public string $passphrase = '';

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'auth_type' => ['required', Rule::enum(ServerAuthType::class)],
            'username' => ['required', 'string', 'max:255'],
            'password' => [$this->auth_type === 'password' ? 'required' : 'nullable', 'string'],
            'private_key' => [$this->auth_type === 'key' ? 'required' : 'nullable', 'string'],
            'passphrase' => ['nullable', 'string'],
        ];
    }

    public function testConnection(): void
    {
        $this->validate($this->rules());

        $result = app(ConnectionTester::class)->testServer(new Server($this->only([
            'host', 'port', 'auth_type', 'username', 'password', 'private_key', 'passphrase',
        ])));

        $this->dispatch('notify', text: $result->message, variant: $result->ok ? 'success' : 'danger');
    }

    public function save(): void
    {
        $validated = $this->validate($this->rules());

        Server::create($validated);

        $this->redirect(route('servers.index'), navigate: true);
    }
}; ?>

<div class="max-w-2xl">
    <x-ui.heading size="xl" level="1">{{ __('Add server') }}</x-ui.heading>
    <x-ui.subheading class="mb-6">{{ __('Accessible by URL or IP address, on the internet or your LAN.') }}</x-ui.subheading>

    <form wire:submit="save" class="space-y-6">
        <x-ui.input wire:model="name" name="name" :label="__('Name')" type="text" required autofocus />

        <div class="flex gap-4">
            <x-ui.input wire:model="host" name="host" :label="__('Host')" type="text" placeholder="example.com or 192.168.1.10" required class="flex-1" />
            <x-ui.input wire:model="port" name="port" :label="__('Port')" type="number" required class="w-28" />
        </div>

        <x-ui.input wire:model="username" name="username" :label="__('Username')" type="text" required />

        <x-ui.select wire:model.live="auth_type" name="auth_type" :label="__('Authentication')">
            @foreach (ServerAuthType::cases() as $option)
                <option value="{{ $option->value }}">{{ $option->label() }}</option>
            @endforeach
        </x-ui.select>

        @if ($auth_type === 'password')
            <x-ui.input wire:model="password" name="password" :label="__('Password')" type="password" required viewable />
        @else
            <x-ui.textarea wire:model="private_key" name="private_key" :label="__('Private key (PEM)')" required />
            <x-ui.input wire:model="passphrase" name="passphrase" :label="__('Passphrase (optional)')" type="password" viewable />
        @endif

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">{{ __('Create server') }}</flux:button>
            <flux:button type="button" wire:click="testConnection">{{ __('Test connection') }}</flux:button>
            <flux:button variant="ghost" href="{{ route('servers.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</div>

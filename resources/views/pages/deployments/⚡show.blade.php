<?php

use App\Models\Deployment;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Deployment')] class extends Component {
    public Deployment $deployment;

    public function mount(Deployment $deployment): void
    {
        $this->deployment = $deployment->load(['project', 'projectServer.server', 'triggeredBy', 'logs']);
    }
}; ?>

<div class="max-w-3xl space-y-6">
    <div>
        <x-ui.link href="{{ route('projects.show', $deployment->project) }}" wire:navigate>&larr; {{ $deployment->project->name }}</x-ui.link>

        <div class="mt-2 flex items-center justify-between">
            <div>
                <x-ui.heading size="xl" level="1">{{ __('Deployment #:id', ['id' => $deployment->id]) }}</x-ui.heading>
                <x-ui.subheading>
                    {{ $deployment->projectServer->server->name }} &middot;
                    {{ ucfirst($deployment->type->value) }} &middot;
                    <span class="font-mono">{{ substr($deployment->commit_sha, 0, 10) }}</span>
                </x-ui.subheading>
            </div>

            <x-ui.badge :color="match ($deployment->status->value) { 'success' => 'green', 'failed' => 'red', default => 'zinc' }">
                {{ $deployment->status->label() }}
            </x-ui.badge>
        </div>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-zinc-950 p-4 font-mono text-xs text-zinc-100 dark:border-zinc-700">
        @forelse ($deployment->logs as $log)
            <div class="{{ $log->level->value === 'error' ? 'text-red-400' : 'text-zinc-100' }}">
                <span class="text-zinc-500">[{{ $log->created_at?->format('H:i:s') }}] [{{ $log->stage }}]</span>
                <span class="whitespace-pre-wrap">{{ $log->message }}</span>
            </div>
        @empty
            <p class="text-zinc-500">{{ __('No log output yet.') }}</p>
        @endforelse
    </div>
</div>

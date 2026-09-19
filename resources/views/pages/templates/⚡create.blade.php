<?php

use App\Models\ProjectTemplate;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Add template')] class extends Component {
    public string $name = '';

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $template = ProjectTemplate::create($validated);

        $this->redirect(route('templates.edit', $template), navigate: true);
    }
}; ?>

<div class="max-w-lg">
    <x-ui.heading size="xl" level="1">{{ __('Add template') }}</x-ui.heading>
    <x-ui.subheading class="mb-6">{{ __('Give it a name, then attach servers on the next screen.') }}</x-ui.subheading>

    <form wire:submit="save" class="space-y-6">
        <x-ui.input wire:model="name" name="name" :label="__('Name')" type="text" required autofocus />

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">{{ __('Create template') }}</flux:button>
            <flux:button variant="ghost" href="{{ route('templates.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</div>

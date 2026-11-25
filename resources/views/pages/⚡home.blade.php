<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Home')] class extends Component
{
    public bool $isMessageVisible = false;

    public function toggleMessage(): void
    {
        $this->isMessageVisible = ! $this->isMessageVisible;
    }
};
?>

<div class="flex flex-col items-start gap-6">
    <div class="flex flex-col gap-2">
        <flux:heading size="xl" level="1">{{ config('app.name') }}</flux:heading>
        <flux:text>This page is built with Livewire and Flux.</flux:text>
    </div>

    <flux:button variant="primary" wire:click="toggleMessage">
        {{ $isMessageVisible ? 'Hide message' : 'Show message' }}
    </flux:button>

    @if ($isMessageVisible)
        <flux:text>Livewire is up and running.</flux:text>
    @endif
</div>

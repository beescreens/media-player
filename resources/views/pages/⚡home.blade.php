<?php

use Illuminate\View\View;
use Livewire\Component;

new class extends Component
{
    public bool $isMessageVisible = false;

    public function toggleMessage(): void
    {
        $this->isMessageVisible = ! $this->isMessageVisible;
    }

    public function render(): View
    {
        return $this->view()
            ->title(__('ui.home.title'))
            ->layoutData(['description' => __('ui.home.description')]);
    }
};
?>

<div class="flex flex-col items-start gap-6">
    <div class="flex flex-col gap-1">
        <flux:heading size="xl" level="1">{{ config('app.name') }}</flux:heading>
        <flux:text>{{ __('ui.home.introduction') }}</flux:text>
    </div>

    <flux:button variant="primary" wire:click="toggleMessage">
        {{ $isMessageVisible ? __('ui.home.hide_message') : __('ui.home.show_message') }}
    </flux:button>

    @if ($isMessageVisible)
        <flux:text>{{ __('ui.home.message') }}</flux:text>
    @endif
</div>

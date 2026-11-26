<?php

use App\Enums\Locale;
use Illuminate\Support\Facades\Cookie;
use Livewire\Component;

new class extends Component
{
    public function switchLocale(string $locale): void
    {
        $selectedLocale = Locale::tryFrom($locale);

        if ($selectedLocale === null) {
            return;
        }

        Cookie::queue(Cookie::forever('locale', $selectedLocale->value));

        $this->js('window.location.reload()');
    }

    /**
     * Get the name of the language in itself, followed by its name in the current language when they differ.
     */
    public function localeName(Locale $locale): string
    {
        $key = "ui.common.locales.{$locale->value}";
        $nativeName = __($key, locale: $locale->value);

        if ($locale->value === app()->getLocale()) {
            return $nativeName;
        }

        return "{$nativeName} (".__($key).')';
    }
};
?>

<div>
    <flux:dropdown align="end">
        <flux:button variant="ghost" square icon="language" :aria-label="__('ui.common.locale_switcher')" />

        <flux:menu>
            @foreach (Locale::cases() as $locale)
                <flux:menu.item
                    wire:key="{{ $locale->value }}"
                    wire:click="switchLocale('{{ $locale->value }}')"
                    :icon="$locale->value === app()->getLocale() ? 'check' : null"
                    lang="{{ $locale->value }}"
                >
                    {{ $this->localeName($locale) }}
                </flux:menu.item>
            @endforeach
        </flux:menu>
    </flux:dropdown>
</div>

<flux:dropdown x-data align="end">
    <flux:button variant="ghost" square :aria-label="__('ui.common.theme_switcher')">
        <flux:icon.sun variant="mini" class="dark:hidden" />
        <flux:icon.moon variant="mini" class="hidden dark:block" />
    </flux:button>

    <flux:menu>
        <flux:menu.radio.group x-model="$flux.appearance">
            <flux:menu.radio value="light" icon="sun">{{ __('ui.common.themes.light') }}</flux:menu.radio>
            <flux:menu.radio value="dark" icon="moon">{{ __('ui.common.themes.dark') }}</flux:menu.radio>
            <flux:menu.radio value="system" icon="computer-desktop">{{ __('ui.common.themes.system') }}</flux:menu.radio>
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>

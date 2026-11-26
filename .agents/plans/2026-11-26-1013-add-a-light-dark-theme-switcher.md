# Add a light/dark theme switcher

<!-- plan-id: 727a93a4-ad97-4dcd-b39e-c253e3fb9053 -->

- Date: 2026-11-26 10:13 UTC
- Agent: Claude Code
- Status: approved

## Initial request

> Add a light theme/dark theme switcher.

## Discussion

None.

## Final plan

<!-- final-plan -->

# Add a light/dark theme switcher

## Context

The layout already includes `@fluxAppearance`, and `resources/css/app.css` already uses the `.dark` class variant, so pages follow the system color scheme, but users cannot choose. Flux provides `$flux.appearance` (`light`, `dark` or `system`), which applies the `.dark` class, stores the choice in `localStorage`, follows system changes, and is applied in the `<head>` before rendering (no flash). The switcher only needs to set it: no server state, cookie or middleware.

It offers **Light**, **Dark** and **System** (the current behavior, kept as the default), following Flux's dark mode dropdown example.

## Steps

1. **Translations** in `lang/{en,fr}/ui.php`, under `common`:
   - `theme_switcher`: "Change theme" / "Changer de thème" (accessible label)
   - `themes.light`, `themes.dark`, `themes.system`: "Light", "Dark", "System" / "Clair", "Sombre", "Système"

2. **Component** `resources/views/components/theme-switcher.blade.php`: an anonymous Blade component (no Livewire, as it has no server state):
   - `flux:dropdown x-data align="end"`
   - button `variant="ghost"` (as the locale switcher), `square`, `aria-label` from `ui.common.theme_switcher`, showing a sun or moon icon depending on `$flux.dark` (`x-show`)
   - `flux:menu.radio.group x-model="$flux.appearance"` with three `flux:menu.radio` items (`light`/sun, `dark`/moon, `system`/computer-desktop), so the current choice is checked

3. **Layout** `resources/views/layouts/app.blade.php`: add `<x-theme-switcher />` before `<livewire:locale-switcher />`.

4. **Test** `HomePageTest`: the page contains the switcher's translated label in both languages (via the existing dataset). The client-side behavior is Flux's and is checked in the browser.

5. `vendor/bin/pint --dirty --format agent`, `npm run build`.

No commit unless requested.

## Verification

- `php artisan test --compact`, `npm run build`
- In a browser: choosing Dark and Light changes the page immediately and survives a reload; System follows the OS setting; the checked item matches the choice; labels are translated in French

# Add Livewire 4 and Flux (free)

<!-- plan-id: a1ccd896-094e-4921-b251-ffd05519aa88 -->

- Date: 2026-11-25 16:09 UTC
- Agent: Claude Code
- Status: approved

## Initial request

> I want to use Livewire and Flux for my Laravel project.

## Discussion

**Question:** Which Flux edition do you want to install?

- Free (Recommended): livewire/flux only. No license needed; Pro can be added
  later without rework.
- Pro: Also livewire/flux-pro. Requires a paid license; you run flux:activate
  yourself so the key stays out of this chat.

**Answer:**

> Free (Recommended)

**Question:** The project only has Laravel's default welcome page. How much
should be set up beyond installing the packages?

- Layout + home page (Recommended): Install, add a Flux layout, and replace the
  welcome page with a minimal Livewire home page and a test.
- Install + layout only: Install and add the Flux layout; keep the welcome page
  as it is.
- Full starter with auth: Also login, registration and settings screens,
  modelled on Laravel's Livewire starter kit (adds Fortify). Much larger change.

**Answer:**

> Layout + home page (Recommended)

## Final plan

<!-- final-plan -->

# Add Livewire 4 and Flux (free)

## Context

The project is a fresh Laravel 13 skeleton (PHP 8.5, Tailwind 4.3, Vite 8,
Pest 5) with only the default welcome page. The goal is to build the UI with
Livewire and Flux. Chosen scope: Flux free edition, an application layout, and a
minimal Livewire home page replacing the welcome page. No authentication.

A Composer dry run resolves Livewire 4.4.10 and Flux 2.22.0 without changing
other packages. Tailwind 4.3.5 meets Flux's 4.2 minimum.

## Steps

1. **Install**: `composer require livewire/livewire:^4.4 livewire/flux:^2.22`.
   No config files are published; Livewire's defaults suit the project
   (`layouts::app`, `pages::`, single-file components).
2. **Boost**: run `php artisan boost:update --no-discover --no-interaction` to
   regenerate guidelines. Check that `livewire-development` and
   `fluxui-development` appear in [.agents/skills/](.agents/skills/) and
   `boost.json`; if not, run
   `php artisan boost:install --guidelines --skills --no-interaction`. Review
   the diff, and confirm the uncommitted `archive-plan` skill survives
   untouched. Read the new skills and any generated `.ai/rules` before writing
   views.
3. **CSS** in `resources/css/app.css`: after the Tailwind import, add
   `@import '../../vendor/livewire/flux/dist/flux.css';` and
   `@custom-variant dark (&:where(.dark, .dark *));`. Keep the existing sources
   and Instrument Sans theme.
4. **Layout** `resources/views/layouts/app.blade.php` (generate with
   `php artisan livewire:layout --no-interaction`, then edit):
    - head: charset, viewport, CSRF token, title
      (`$title ?? config('app.name')`), the existing `@fonts`, `@vite([...])`,
      `@fluxAppearance`
    - body: `flux:header` with the brand linking to `route('home')`, `flux:main`
      containing `$slot`, then `@livewireScripts` and `@fluxScripts`; add
      `@livewireStyles` in head
    - only free Flux components
5. **Home page** `resources/views/pages/⚡home.blade.php`:
   `php artisan make:livewire pages::home --no-interaction`. Use a `#[Title]`
   attribute and a Flux heading, text and one `wire:click` button toggling a
   short message, so server interaction is proven end to end.
6. **Route** in `routes/web.php`:
   `Route::livewire('/', 'pages::home')->name('home');`. Delete
   `resources/views/welcome.blade.php`, which becomes unused.
7. **Test**: `php artisan make:test --pest HomePageTest --no-interaction`. Cover
   that `route('home')` returns 200 and contains the component
   (`assertSeeLivewire`), and that the action toggles the message
   (`Livewire::test`). Leave `tests/Feature/ExampleTest.php` in place. Call
   `withoutVite()` in the HTTP tests that need it, as the new layout has no
   missing-manifest fallback: put it in `tests/TestCase.php` `setUp()` so
   ExampleTest also passes on a clean checkout.
8. **Format**: `vendor/bin/pint --dirty --format agent`.

No commit is made unless requested. Existing uncommitted changes are left alone.

## Verification

- `php artisan test --compact`
- `npm run build` succeeds and the built CSS contains Flux styles
- `php artisan route:list --except-vendor` shows `home`
- Serve the app: `/` and the Flux and Livewire script URLs return 200; check
  `browser-logs` for errors
- User check in a browser: the button responds, and light and dark appearance
  both render

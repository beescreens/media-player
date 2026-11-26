# Use Laravel Folio for pages

<!-- plan-id: 8301c0c3-eaaf-439d-81e1-510d0a4e1313 -->

- Date: 2026-11-25 16:20 UTC
- Agent: Claude Code
- Status: approved

## Initial request

> I want to use Folio for managing the pages.

## Discussion

None.

## Final plan

<!-- final-plan -->

# Use Laravel Folio for pages

## Context

Pages should be managed with Folio's file-based routing instead of `routes/web.php`. The only page today is the home page, a full-page Livewire component routed with `Route::livewire()`.

Folio 1.4.0 supports Laravel 13, and a Composer dry run adds it without changing other packages. Folio and Livewire both default to `resources/views/pages`: Folio would treat the existing `⚡home.blade.php` as a page. The two therefore need separate roles:

- **Folio pages** in `resources/views/pages/`: plain Blade that declares the URL, route name, middleware and layout.
- **Livewire components** in `resources/views/components/`: interactive parts, embedded in pages. No `pages::` components. Volt is not needed, as Livewire 4 has single-file components.

## Steps

1. **Install**: `composer require laravel/folio:^1.4`, then `php artisan folio:install --no-interaction`. This adds `app/Providers/FolioServiceProvider.php` and registers it in `bootstrap/providers.php`. Keep the default mount of `resources/views/pages` at `/`.
2. **Boost**: Composer's update hook or `php artisan boost:update --no-discover --no-interaction` should add the `folio-routing` skill to `.agents/skills/` and `boost.json`. Read it before continuing, and review the generated diff.
3. **Component**: `git mv` `resources/views/pages/⚡home.blade.php` to `resources/views/components/⚡home.blade.php` (component name `home`). Remove `#[Title]`, which only applies to full-page components.
4. **Page**: `php artisan folio:page index --no-interaction`, then:

   ```blade
   <?php

   use function Laravel\Folio\name;

   name('home');

   ?>

   <x-layouts::app title="Home">
       <livewire:home />
   </x-layouts::app>
   ```

   `layouts::` is already registered by Livewire as an anonymous component namespace, so `resources/views/layouts/app.blade.php` is reused unchanged, including its `route('home')` link.
5. **Routes**: remove the `Route::livewire()` line and unused import from `routes/web.php`. Keep the file, as `bootstrap/app.php` loads it.
6. **Tests**: in `tests/Feature/HomePageTest.php`, change `pages::home` to `home` and assert the rendered title, which now comes from the page. The tests keep their current behavior coverage.
7. **Format**: `vendor/bin/pint --dirty --format agent`.

No commit unless requested.

## Verification

- `php artisan test --compact`
- `php artisan folio:list` shows `/` named `home` and no `⚡` route
- `npm run build` succeeds
- Serve the app: `/` returns 200 with the title, Livewire component and Flux/Livewire scripts; an unknown URL returns 404
- `php artisan route:cache`, request `/`, then `php artisan route:clear`: the named route also works when cached
- User check in a browser: the button still toggles the message

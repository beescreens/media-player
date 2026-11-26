# Remove Folio and route Livewire pages directly

<!-- plan-id: 848f6163-e302-4806-a55e-284dcfae4b8b -->

- Date: 2026-11-25 16:26 UTC
- Agent: Claude Code
- Status: approved

## Initial request

> It seems Folio is a bit overkill/unnecessary in this context. If every page only has a few lines such as:
>
> ```php
>
> <pasted_content id="a607">
> <?php
>
> use function Laravel\Folio\name;
>
> name('home');
>
> ?>
>
> <x-layouts::app title="Home">
>     <livewire:home />
> </x-layouts::app>
> </pasted_content id="a607">
>
> ```
>
> to initialize a Livewire component, we don't gain much from the `routes/web.php` page.
>
> What do you think? Is removing Folio a good idea? Am I missing something?

## Discussion

None.

## Final plan

<!-- final-plan -->

# Remove Folio and route Livewire pages directly

## Context

With Folio, each page is a short wrapper around one Livewire component. Livewire 4's `Route::livewire()` provides the URL, name, layout, parameters, model binding and middleware in one line, and Folio and Livewire's `pages::` namespace compete for `resources/views/pages`. Folio adds a dependency and a file per page for little benefit, so it is removed. Its loss (URLs derived from file names) matters mainly for sites with many static pages; `Route::view()` covers a few of those.

All Folio changes are uncommitted, so the target is mostly the state of commit `3266dc1`, except that the page title assertion in the test is kept and `boost.json` remains valid JSON.

## Steps

1. **Unregister Folio first**, as the provider would break Artisan once the package is gone: `git restore bootstrap/providers.php` and delete `app/Providers/FolioServiceProvider.php` (check `git diff` first to confirm only Folio changes are there).
2. **Page**: delete `resources/views/pages/index.blade.php`, `git mv` `resources/views/components/⚡home.blade.php` back to `resources/views/pages/⚡home.blade.php`, and restore `#[Title('Home')]` and its import. The layout is unchanged.
3. **Route**: `git restore routes/web.php`, which brings back `Route::livewire('/', 'pages::home')->name('home')`.
4. **Package**: `composer remove laravel/folio`. Afterwards `git diff composer.json composer.lock` must be empty; if it is not, review before changing anything.
5. **Boost**: run `php artisan boost:update --no-discover --no-interaction` if Composer did not. Confirm that the `folio-routing` skill, its `boost.json` entry and the Folio section of `AGENTS.md` are gone; remove leftovers by hand.
6. **Tests**: in `tests/Feature/HomePageTest.php`, use `pages::home` again and keep the title assertion.
7. **Format**: `vendor/bin/pint --dirty --format agent`.

Plan archives in `docs/plans/` are kept, including the Folio one, as the record of the decision. No commit unless requested.

## Verification

- `php artisan test --compact`
- `grep -ri folio` outside `vendor`, `node_modules`, `.git` and `docs/plans` finds only mentions unrelated to the package being installed
- `php artisan route:list --name=home` shows `/`
- `npm run build` succeeds
- Serve the app: `/` returns 200 with title, component and scripts; unknown URL returns 404; `/` also works with `route:cache` (cleared afterwards)
- `git diff HEAD --stat` shows only `boost.json`, the test and the plan archives

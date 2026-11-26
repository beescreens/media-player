# Support English and French with short translation keys

<!-- plan-id: 50a5d7a3-b45e-4ccf-af45-2a616ca1ade8 -->

- Date: 2026-11-25 16:31 UTC
- Agent: Claude Code
- Status: approved

## Initial request

> I want to support i18n, in English and French. Use short keys instead of translation strings as keys.

## Discussion

**Question:** How should the active language be determined and kept for a visitor?

- Cookie + browser (Recommended): URLs stay the same (/). First visit follows the browser's language; a switcher choice is remembered in a long-lived cookie. Simple, suits an application.
- URL prefix: /en/... and /fr/... Each language has its own shareable URL and is indexable by search engines, but every route and link carries the locale.
- Session + browser: Same as the cookie option, but the choice is forgotten when the session expires.

**Answer:**

> Cookie + browser (Recommended)

**Question:** Which language should be used when the visitor's language is neither English nor French?

- English (Recommended): Keeps the current Laravel configuration (default and fallback: en).
- French: French becomes the default and the fallback.

**Answer:**

> English (Recommended)

**Question:** How should Laravel's own messages (validation, authentication, pagination, passwords) be available in French?

- Write them in lang/fr (Recommended): Publish Laravel's English files and add French versions to the repository. No new dependency.
- laravel-lang package: Community-maintained translations, added as a development dependency and published to lang/.
- Later: Only translate the application's own text for now; validation messages stay in English.

**Answer:**

> laravel-lang package

---

**Question:** I found two problems with the laravel-lang package you chose. (1) In May 2026 attackers replaced release tags of laravel-lang/lang and three sibling packages with credential-stealing code; reports say they have since been cleaned and relisted, and Packagist currently lists no advisory. (2) The current 15.x release does not install here: its dependencies need the PHP bcmath extension, which the dev container lacks. Composer instead picks the outdated 11.0.20. How should French framework messages be provided?

- Write them in lang/fr (Recommended): About 260 lines across four files, added to the repository. No dependency and no supply chain exposure. A test checks that English and French keys stay in sync.
- laravel-lang anyway: Add bcmath to the dev container (requires a rebuild) and install 15.39 as a development dependency, after inspecting the downloaded code before it runs.
- Later: Translate only the application's own text for now.

**Answer:**

> Install bcmath and use latest version of laravel-lang. What are your sources for the credentials-stealing code?

## Final plan

<!-- final-plan -->

# Support English and French with short translation keys

## Context

All interface text is hard-coded in English. The application must support English and French, with short keys (`__('home.title')`) in PHP files rather than sentences as keys. Decisions taken:

- The language follows a cookie, then the browser, then English. URLs do not change.
- Laravel's own messages come from the latest stable `laravel-lang/lang` (15.39.2; 20.0 is only a release candidate), as a development dependency. The published files are committed, so production does not need the package.
- The `bcmath` PHP extension, required by its dependencies, is added to the dev container.

`laravel-lang` had malicious tags published in May 2026 (removed since). Its code is therefore inspected before it runs.

## Steps

1. **bcmath**: in `.devcontainer/post-create.sh`, add `sudo docker-php-ext-install bcmath`, following the file's style. Run the same command now, so a rebuild is not needed in this container.
2. **Install, inspecting before running**:
   - `composer require --dev laravel-lang/lang:^15.39 --no-install --no-scripts --no-plugins` to update only the lock file. Expect 11 new packages, as in the dry resolution.
   - `composer install --no-scripts --no-plugins --no-autoloader` to download without running package code.
   - In the 11 packages, check for: `src/helpers.php`, `autoload.files`, `flipboxstudio`, `.laravel_locale`, `exec`/`shell_exec`/`proc_open`/`eval`/`base64_decode`/`curl_`, `array_map('chr'`, and read the service providers. Compare the `laravel-lang/lang` lock reference with the 15.39.2 tag on GitHub (`git ls-remote`).
   - **Stop and report if anything is suspicious**, without running Artisan. Otherwise run `composer install` normally, with `composer audit`.
3. **Framework text**: `php artisan lang:add fr --no-interaction` and, if needed, `lang:add en` (check `php artisan list lang` first). Review the result. The package may also create `lang/en.json` and `lang/fr.json`: these hold strings that Laravel and Flux use internally as keys, such as "Showing". They stay, but application code must only use short keys.
4. **Locale enum** `app/Enums/Locale.php` (new folder): `English = 'en'`, `French = 'fr'`, with a `label()` method returning "English" and "Français" (each language named in itself, not translated).
5. **Middleware** `app/Http/Middleware/SetLocale.php` (`make:middleware`), appended to the `web` group in `bootstrap/app.php`: use the `locale` cookie if it is a valid enum value (`Locale::tryFrom`), otherwise `$request->getPreferredLanguage()` restricted to the enum values (English first, as Symfony returns the first when nothing matches). Call `app()->setLocale()`. Livewire already keeps the locale across its update requests.
6. **Switcher** `resources/views/components/⚡locale-switcher.blade.php` (`make:livewire locale-switcher`): a `flux:dropdown` with one item per language, the current one marked. The action validates with `Locale::tryFrom`, ignores unknown values, queues a long-lived cookie (`Cookie::forever`) and reloads the page with `$this->js('window.location.reload()')`, so that `<html lang>` and the whole layout change. Add it to `resources/views/layouts/app.blade.php` after a `flux:spacer`.
7. **Application text**: `lang/{en,fr}/home.php` (title, introduction, show and hide labels, message) and `lang/{en,fr}/locale.php` (accessible label of the switcher). Replace the hard-coded text in `resources/views/pages/⚡home.blade.php`. `#[Title]` cannot hold a translation, so use `render()` with `$this->view()->title(__('home.title'))`.
8. **Tests** (Pest):
   - `HomePageTest`: assert translated text in both languages.
   - `SetLocaleTest`: browser language (`fr-CH` gives French, `de` gives English), cookie over browser, invalid cookie ignored.
   - `LocaleSwitcherTest`: queues the cookie; ignores an unsupported value.
   - `TranslationTest`: each PHP language file exists in both languages with identical keys, so a missing translation fails the suite.
9. **Format**: `vendor/bin/pint --dirty --format agent`.

No commit unless requested. Existing uncommitted changes are left alone.

## Verification

- `php artisan test --compact`, `npm run build`, `composer audit`
- `grep` the views: no hard-coded interface text and no sentence used as a key
- Serve the app on a spare port and stop it afterwards: request `/` with `Accept-Language: fr`, `en` and `de`; check the title, `<html lang>` and text
- `composer install --no-dev --dry-run` confirms that production needs neither `laravel-lang` nor `bcmath`
- User check in a browser: switching language works and survives a reload
- Known limit: 404 pages for unmatched URLs do not go through the `web` group, so they stay in the default language

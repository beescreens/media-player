<?php

use Illuminate\Support\Facades\Cookie;
use Livewire\Livewire;

test('the selected locale is saved', function () {
    Livewire::test('locale-switcher')->call('switchLocale', 'fr');

    expect(Cookie::queued('locale')?->getValue())->toBe('fr');
});

test('an unsupported locale is not saved', function () {
    Livewire::test('locale-switcher')->call('switchLocale', 'de');

    expect(Cookie::hasQueued('locale'))->toBeFalse();
});

test('each language is named in itself and in the current language', function (string $locale, array $names) {
    app()->setLocale($locale);

    Livewire::test('locale-switcher')->assertSeeInOrder($names);
})->with([
    'English' => ['en', ['English', 'Français (French)']],
    'French' => ['fr', ['English (Anglais)', 'Français']],
]);

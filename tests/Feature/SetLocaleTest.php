<?php

test('the locale follows the browser language when none is saved', function (string $acceptLanguage, string $expectedLocale) {
    $this->get(route('home'), ['Accept-Language' => $acceptLanguage])->assertOk();

    expect(app()->getLocale())->toBe($expectedLocale);
})->with([
    'English' => ['en-GB,en;q=0.9', 'en'],
    'French' => ['fr', 'fr'],
    'regional French' => ['fr-CH,fr;q=0.9,en;q=0.8', 'fr'],
    'unsupported first choice' => ['de,fr;q=0.8', 'fr'],
    'unsupported language' => ['de', 'en'],
    'no preference' => ['', 'en'],
]);

test('the saved locale takes precedence over the browser language', function () {
    $this->withUnencryptedCookie('locale', 'fr')->get(route('home'), ['Accept-Language' => 'en'])->assertOk();

    expect(app()->getLocale())->toBe('fr');
});

test('an unsupported saved locale is ignored', function () {
    $this->withUnencryptedCookie('locale', 'de')->get(route('home'), ['Accept-Language' => 'fr'])->assertOk();

    expect(app()->getLocale())->toBe('fr');
});

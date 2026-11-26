<?php

use App\Enums\Locale;
use Illuminate\Support\Arr;

/**
 * @return array<int, string>
 */
function translationKeys(string $locale): array
{
    return collect(glob(lang_path("{$locale}/*.php")))
        ->flatMap(fn (string $file): array => array_keys(Arr::dot([basename($file, '.php') => require $file])))
        ->sort()
        ->values()
        ->all();
}

test('every locale defines the same translation keys', function (Locale $locale) {
    expect(translationKeys($locale->value))
        ->not->toBeEmpty()
        ->toBe(translationKeys(Locale::English->value));
})->with(Locale::cases());

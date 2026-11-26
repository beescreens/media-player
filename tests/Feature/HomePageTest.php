<?php

use Livewire\Livewire;

test('the home page is rendered in the requested language', function (string $locale, string $title, string $description, string $introduction, string $themeSwitcher) {
    $response = $this->get(route('home'), ['Accept-Language' => $locale]);

    $response
        ->assertOk()
        ->assertSee("<html lang=\"{$locale}\">", false)
        ->assertSee("<title>{$title}</title>", false)
        ->assertSee("<meta name=\"description\" content=\"{$description}\">", false)
        ->assertSee($introduction)
        ->assertSee("aria-label=\"{$themeSwitcher}\"", false)
        ->assertSeeLivewire('pages::home')
        ->assertSeeLivewire('locale-switcher');
})->with([
    'English' => ['en', 'Home', 'A media player built with Laravel, Livewire and Flux.', 'This page is built with Livewire and Flux.', 'Change theme'],
    'French' => ['fr', 'Accueil', 'Un lecteur multimédia construit avec Laravel, Livewire et Flux.', 'Cette page est construite avec Livewire et Flux.', 'Changer de thème'],
]);

test('the message is shown and then hidden when toggled', function () {
    Livewire::test('pages::home')
        ->assertDontSee('Livewire is up and running.')
        ->call('toggleMessage')
        ->assertSee('Livewire is up and running.')
        ->call('toggleMessage')
        ->assertDontSee('Livewire is up and running.');
});

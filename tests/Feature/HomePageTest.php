<?php

use Livewire\Livewire;

test('the home page renders the home component', function () {
    $response = $this->get(route('home'));

    $response->assertOk()->assertSeeLivewire('pages::home');
});

test('the message is shown and then hidden when toggled', function () {
    Livewire::test('pages::home')
        ->assertDontSee('Livewire is up and running.')
        ->call('toggleMessage')
        ->assertSee('Livewire is up and running.')
        ->call('toggleMessage')
        ->assertDontSee('Livewire is up and running.');
});

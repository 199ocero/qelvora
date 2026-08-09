<?php

use Inertia\Testing\AssertableInertia as Assert;

test('the docs index redirects to the first page', function () {
    $this->get(route('docs.index'))
        ->assertRedirect(route('docs.show', 'introduction'));
});

test('a documentation page is publicly accessible without authentication', function () {
    $this->get(route('docs.show', 'introduction'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('docs/Show')
            ->where('page.slug', 'introduction')
            ->where('page.title', 'Introduction')
            ->where('page.section', 'Getting started')
            ->has('page.html')
            ->has('navigation')
        );
});

test('the navigation is grouped into ordered sections', function () {
    $this->get(route('docs.show', 'introduction'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('docs/Show')
            ->where('navigation.0.title', 'Getting started')
            ->has('navigation.0.items')
            ->where('navigation.0.items.0.slug', 'introduction')
        );
});

test('rendered markdown includes anchored headings for the outline', function () {
    $this->get(route('docs.show', 'connect-a-provider'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('docs/Show')
            ->where('page.html', fn (string $html) => str_contains($html, '<h2 id="'))
            ->has('page.headings.0.id')
            ->has('page.headings.0.text')
        );
});

test('a page exposes previous and next neighbours', function () {
    $this->get(route('docs.show', 'quickstart'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('docs/Show')
            ->where('prev.title', 'Introduction')
            ->where('next.title', 'Connect a provider')
        );
});

test('the first page has no previous link', function () {
    $this->get(route('docs.show', 'introduction'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('docs/Show')
            ->where('prev', null)
        );
});

test('an unknown documentation page returns a 404', function () {
    $this->get(route('docs.show', 'does-not-exist'))
        ->assertNotFound();
});

<?php

it('loads the homepage', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

it('make sure auth pages are protected', function () {
    $response = $this->getJson('/api/teams');

    $response->assertUnauthorized();
});

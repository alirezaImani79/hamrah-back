<?php

it('returns a json envelope for an unknown endpoint', function () {
    $response = $this->getJson('/api/v1/does-not-exist');

    $response->assertStatus(404)
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonPath('success', false)
        ->assertJsonPath('code', 'ENDPOINT_NOT_FOUND')
        ->assertJsonStructure(['success', 'message', 'code', 'errors']);
});

it('returns a json envelope for unauthenticated requests', function () {
    $this->getJson('/api/v1/auth/me')
        ->assertStatus(401)
        ->assertJsonPath('success', false)
        ->assertJsonPath('code', 'UNAUTHENTICATED');
});

it('returns a json envelope for validation errors', function () {
    $this->postJson('/api/v1/auth/otp/request', [])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('code', 'VALIDATION_FAILED')
        ->assertJsonStructure(['success', 'message', 'code', 'errors' => ['phone_number']]);
});

it('returns a json envelope when the http method is not allowed', function () {
    $this->getJson('/api/v1/auth/otp/request')
        ->assertStatus(405)
        ->assertJsonPath('success', false)
        ->assertJsonPath('code', 'METHOD_NOT_ALLOWED');
});

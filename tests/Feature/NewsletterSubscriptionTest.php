<?php

use App\Models\User;

it('subscribes the authenticated user to the newsletter', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/newsletter/subscribe')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.is_subscribed_to_newsletter', true);

    expect($user->fresh()->newsletter_subscribed_at)->not->toBeNull();
});

it('keeps the original timestamp when subscribing twice', function () {
    $user = User::factory()->subscribedToNewsletter()->create();
    $original = $user->newsletter_subscribed_at;
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/newsletter/subscribe')->assertOk();

    expect($user->fresh()->newsletter_subscribed_at->toIso8601String())
        ->toBe($original->toIso8601String());
});

it('unsubscribes the authenticated user from the newsletter', function () {
    $user = User::factory()->subscribedToNewsletter()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/newsletter/unsubscribe')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.is_subscribed_to_newsletter', false);

    expect($user->fresh()->newsletter_subscribed_at)->toBeNull();
});

it('reports the current newsletter status', function () {
    $user = User::factory()->subscribedToNewsletter()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/newsletter')
        ->assertOk()
        ->assertJsonPath('data.is_subscribed_to_newsletter', true);
});

it('rejects unauthenticated newsletter requests', function () {
    $this->postJson('/api/v1/newsletter/subscribe')
        ->assertStatus(401)
        ->assertJsonPath('success', false);
});

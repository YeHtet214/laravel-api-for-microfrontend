<?php

use App\Models\SsoClient;
use App\Models\User;

it('issues an authorization code and exchanges it for a sanctum token', function () {
    $client = SsoClient::query()->create([
        'name' => 'User Portal',
        'client_id' => 'user-portal',
        'client_secret' => 'super-secret',
        'redirect_uris' => ['https://portal.example.com/callback'],
        'is_active' => true,
    ]);

    $user = User::factory()->create(['status' => 'active']);

    $response = $this->actingAs($user)->get('/sso/authorize?client_id=user-portal&redirect_uri=https%3A%2F%2Fportal.example.com%2Fcallback&state=abc123');

    $response->assertRedirect();

    $location = $response->headers->get('Location');
    parse_str(parse_url($location, PHP_URL_QUERY), $query);

    expect($query)->toHaveKey('code');
    expect($query['state'] ?? null)->toBe('abc123');

    $tokenResponse = $this->postJson('/api/sso/token', [
        'grant_type' => 'authorization_code',
        'client_id' => 'user-portal',
        'client_secret' => 'super-secret',
        'code' => $query['code'],
        'redirect_uri' => 'https://portal.example.com/callback',
    ]);

    $tokenResponse->assertOk()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.id', $user->id);

    $accessToken = $tokenResponse->json('access_token');

    $this->withHeader('Authorization', 'Bearer '.$accessToken)
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('user.email', $user->email);

    $this->postJson('/api/sso/token', [
        'grant_type' => 'authorization_code',
        'client_id' => 'user-portal',
        'client_secret' => 'super-secret',
        'code' => $query['code'],
        'redirect_uri' => 'https://portal.example.com/callback',
    ])->assertStatus(422);

    expect($client->fresh())->not->toBeNull();
});

it('rejects unauthorized authorize calls and invalid token credentials', function () {
    SsoClient::query()->create([
        'name' => 'Product Portal',
        'client_id' => 'product-portal',
        'client_secret' => 'correct-secret',
        'redirect_uris' => ['https://product.example.com/callback'],
        'is_active' => true,
    ]);

    $this->getJson('/sso/authorize?client_id=product-portal&redirect_uri=https%3A%2F%2Fproduct.example.com%2Fcallback')
        ->assertStatus(401)
        ->assertJsonPath('message', 'Unauthenticated.');

    $this->postJson('/api/sso/token', [
        'grant_type' => 'authorization_code',
        'client_id' => 'product-portal',
        'client_secret' => 'wrong-secret',
        'code' => 'invalid-code',
        'redirect_uri' => 'https://product.example.com/callback',
    ])->assertStatus(401)
      ->assertJsonPath('message', 'Invalid client credentials.');
});

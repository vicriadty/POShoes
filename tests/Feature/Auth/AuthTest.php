<?php

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->branch = Branch::factory()->create(['code' => 'TEST-CASH']);
    $this->user = User::factory()->create([
        'email' => 'kasir@poshoes.test',
        'password' => bcrypt('password'),
        'branch_id' => $this->branch->id,
        'is_active' => true,
    ]);
    $this->user->assignRole('kasir');
});

it('can login with valid credentials', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'kasir@poshoes.test',
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonStructure(['data' => ['user', 'token', 'token_type']]);
});

it('rejects invalid credentials', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'kasir@poshoes.test',
        'password' => 'wrongpass',
    ])
        ->assertStatus(422)
        ->assertJsonStructure(['message', 'errors']);
});

it('returns current user via /me', function () {
    $token = $this->user->createToken('test')->plainTextToken;

    $this->getJson('/api/v1/auth/me', ['Authorization' => "Bearer {$token}"])
        ->assertOk()
        ->assertJsonPath('data.email', 'kasir@poshoes.test');
});

it('prevents access to /me when unauthenticated', function () {
    $this->getJson('/api/v1/auth/me')->assertStatus(401);
});

it('logs out and revokes the token', function () {
    $token = $this->user->createToken('test')->plainTextToken;

    $this->postJson('/api/v1/auth/logout', [], ['Authorization' => "Bearer {$token}"])
        ->assertNoContent();

    // Guard Sanctum ter-cache antar-request dalam satu test; paksa re-resolve.
    Auth::forgetGuards();

    // Token sudah dihapus pada DB -> request berikutnya harus ditolak (401).
    $this->getJson('/api/v1/auth/me', ['Authorization' => "Bearer {$token}"])
        ->assertStatus(401);
});

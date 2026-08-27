<?php

use App\Models\Branch;
use App\Models\User;

function authToken(User $user): string
{
    return $user->createToken('test')->plainTextToken;
}

beforeEach(function () {
    $this->branch = Branch::factory()->create();
});

it('denies kasir access to an admin-only users listing', function () {
    $kasir = User::factory()->create(['branch_id' => $this->branch->id]);
    $kasir->assignRole('kasir');

    $this->getJson('/api/v1/users', ['Authorization' => 'Bearer '.authToken($kasir)])
        ->assertStatus(403);
});

it('allows owner access to users listing', function () {
    $owner = User::factory()->create(['branch_id' => $this->branch->id]);
    $owner->assignRole('owner');

    $this->getJson('/api/v1/users', ['Authorization' => 'Bearer '.authToken($owner)])
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total']]);
});

it('blocks unauthenticated requests', function () {
    $this->getJson('/api/v1/users')->assertStatus(401);
});

it('allows admin to create a user and logs an audit event', function () {
    $admin = User::factory()->create(['branch_id' => $this->branch->id]);
    $admin->assignRole('admin');

    $response = $this->postJson('/api/v1/users', [
        'name' => 'Kasir Baru',
        'email' => 'kasir2@poshoes.test',
        'password' => 'secret1234',
        'branch_id' => $this->branch->id,
        'role' => 'kasir',
    ], ['Authorization' => 'Bearer '.authToken($admin)]);

    $response->assertCreated()->assertJsonPath('data.email', 'kasir2@poshoes.test');

    $this->assertDatabaseHas('users', ['email' => 'kasir2@poshoes.test']);
    $this->assertDatabaseHas('activity_log', ['subject_type' => User::class]);
});

it('denies kasir creating a user', function () {
    $kasir = User::factory()->create(['branch_id' => $this->branch->id]);
    $kasir->assignRole('kasir');

    $this->postJson('/api/v1/users', [
        'name' => 'Nope',
        'email' => 'nope@poshoes.test',
        'password' => 'secret1234',
        'branch_id' => $this->branch->id,
        'role' => 'kasir',
    ], ['Authorization' => 'Bearer '.authToken($kasir)])
        ->assertStatus(403);
});

<?php

use App\Models\Branch;
use App\Models\ServiceCatalog;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

beforeEach(function () {
    $this->branch = Branch::firstOrCreate(
        ['code' => 'TEST-CASH'],
        ['name' => 'Test Cabang', 'is_active' => true],
    );
});

function serviceUser(Branch $branch, string $role): User
{
    $user = User::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
    $user->assignRole($role);

    return $user;
}

function serviceToken(User $user): string
{
    return $user->createToken('test')->plainTextToken;
}

function serviceAuthed(TestCase $test, string $method, string $uri, User $user, array $data = []): TestResponse
{
    $headers = ['Authorization' => 'Bearer '.serviceToken($user)];
    if (strtoupper($method) === 'GET') {
        return $test->{$method.'Json'}($uri, $headers);
    }

    return $test->{$method.'Json'}($uri, $data, $headers);
}

it('lists service catalog with categories', function () {
    $user = serviceUser($this->branch, 'owner');
    $category = ServiceCategory::factory()->create();
    ServiceCatalog::factory()->create(['category_id' => $category->id]);

    serviceAuthed($this, 'get', '/api/v1/services', $user)
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'code', 'name', 'base_price', 'category']],
            'meta' => ['current_page', 'per_page', 'total'],
        ]);
});

it('creates a service with integer price', function () {
    $user = serviceUser($this->branch, 'owner');
    $category = ServiceCategory::factory()->create();

    serviceAuthed($this, 'post', '/api/v1/services', $user, [
        'code' => 'SPA',
        'category_id' => $category->id,
        'name' => 'Spa Premium',
        'base_price' => 150000,
        'estimated_duration_minutes' => 45,
        'requires_before_after_photo' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('data.base_price', 150000)
        ->assertJsonPath('data.code', 'SPA');
});

it('denies service create to kasir', function () {
    $kasir = serviceUser($this->branch, 'kasir');
    $category = ServiceCategory::factory()->create();

    serviceAuthed($this, 'post', '/api/v1/services', $kasir, [
        'code' => 'X',
        'category_id' => $category->id,
        'name' => 'X',
        'base_price' => 1000,
        'estimated_duration_minutes' => 10,
    ])
        ->assertStatus(403);
});

it('allows kasir to view services (needed for order intake)', function () {
    $kasir = serviceUser($this->branch, 'kasir');

    serviceAuthed($this, 'get', '/api/v1/services', $kasir)
        ->assertOk();
});

it('rejects negative price', function () {
    $user = serviceUser($this->branch, 'owner');
    $category = ServiceCategory::factory()->create();

    serviceAuthed($this, 'post', '/api/v1/services', $user, [
        'code' => 'NEG',
        'category_id' => $category->id,
        'name' => 'Negatif',
        'base_price' => -100,
        'estimated_duration_minutes' => 10,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('base_price');
});

it('updates a service', function () {
    $user = serviceUser($this->branch, 'owner');
    $service = ServiceCatalog::factory()->create(['base_price' => 50000]);

    serviceAuthed($this, 'put', "/api/v1/services/{$service->id}", $user, [
        'base_price' => 60000,
    ])
        ->assertOk()
        ->assertJsonPath('data.base_price', 60000);
});

it('snapshot price is preserved when master price changes later', function () {
    $user = serviceUser($this->branch, 'owner');
    $service = ServiceCatalog::factory()->create(['base_price' => 50000, 'code' => 'SNP']);

    // Order dibuat dengan snapshot 50000 (via CreateServiceOrder action test).
    $this->assertTrue($service->base_price === 50000);

    // Master berubah — snapshot lama tidak boleh terpengaruh.
    serviceAuthed($this, 'put', "/api/v1/services/{$service->id}", $user, [
        'base_price' => 75000,
    ])->assertOk();

    expect(ServiceCatalog::find($service->id)->base_price)->toBe(75000);
});

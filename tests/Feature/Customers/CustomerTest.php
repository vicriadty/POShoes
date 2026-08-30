<?php

use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use App\Support\PhoneNormalizer;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

beforeEach(function () {
    $this->branch = Branch::firstOrCreate(
        ['code' => 'TEST-CASH'],
        ['name' => 'Test Cabang', 'is_active' => true],
    );
});

function customerUser(Branch $branch, string $role): User
{
    $user = User::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
    $user->assignRole($role);

    return $user;
}

function customerToken(User $user): string
{
    return $user->createToken('test')->plainTextToken;
}

function customerAuthed(TestCase $test, string $method, string $uri, User $user, array $data = []): TestResponse
{
    $headers = ['Authorization' => 'Bearer '.customerToken($user)];
    if (strtoupper($method) === 'GET') {
        return $test->{$method.'Json'}($uri, $headers);
    }

    return $test->{$method.'Json'}($uri, $data, $headers);
}

it('creates a customer and normalizes the phone number', function () {
    $user = customerUser($this->branch, 'owner');

    customerAuthed($this, 'post', '/api/v1/customers', $user, [
        'name' => 'Budi Santoso',
        'phone_wa' => '0812-3456-7890',
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Budi Santoso')
        ->assertJsonPath('data.phone_wa_normalized', '6281234567890');

    $this->assertDatabaseHas('customers', ['phone_wa_normalized' => '6281234567890']);
});

it('rejects duplicate phone numbers with a clear message', function () {
    $user = customerUser($this->branch, 'owner');
    Customer::factory()->create(['phone_wa_normalized' => '6281111111111']);

    customerAuthed($this, 'post', '/api/v1/customers', $user, [
        'name' => 'Dup',
        'phone_wa' => '0811-1111-1111',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('phone_wa');
});

it('lists customers with pagination and search', function () {
    $user = customerUser($this->branch, 'owner');
    Customer::factory()->create(['name' => 'Ayu Lestari', 'phone_wa_normalized' => '6281111111111']);
    Customer::factory()->create(['name' => 'Bambang', 'phone_wa_normalized' => '6282222222222']);

    $response = customerAuthed($this, 'get', '/api/v1/customers?search=Ayu', $user)
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total']]);

    expect($response->json('meta.total'))->toBe(1);
    expect($response->json('data.0.name'))->toBe('Ayu Lestari');
});

it('updates a customer and re-normalizes phone', function () {
    $user = customerUser($this->branch, 'owner');
    $customer = Customer::factory()->create(['phone_wa_normalized' => '6281111111111']);

    customerAuthed($this, 'put', "/api/v1/customers/{$customer->id}", $user, [
        'phone_wa' => '+62 811 2222 3333',
    ])
        ->assertOk()
        ->assertJsonPath('data.phone_wa_normalized', '6281122223333');
});

it('keeps phone unique on update while ignoring the same customer', function () {
    $user = customerUser($this->branch, 'owner');
    $customer = Customer::factory()->create(['phone_wa_normalized' => '6281111111111']);

    customerAuthed($this, 'put', "/api/v1/customers/{$customer->id}", $user, [
        'phone_wa' => '0811-1111-1111',
    ])
        ->assertOk();
});

it('denies customer create to a technician', function () {
    $teknisi = customerUser($this->branch, 'teknisi');

    customerAuthed($this, 'post', '/api/v1/customers', $teknisi, [
        'name' => 'X',
        'phone_wa' => '081122223333',
    ])
        ->assertStatus(403);
});

it('allows kasir to create a customer', function () {
    $kasir = customerUser($this->branch, 'kasir');

    customerAuthed($this, 'post', '/api/v1/customers', $kasir, [
        'name' => 'Kasir Customer',
        'phone_wa' => '0812-9999-8888',
    ])
        ->assertCreated();
});

it('requires phone_wa', function () {
    $user = customerUser($this->branch, 'owner');

    customerAuthed($this, 'post', '/api/v1/customers', $user, ['name' => 'No Phone'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('phone_wa');
});

it('normalizes various phone formats consistently', function () {
    expect(PhoneNormalizer::normalize('08123456789'))->toBe('628123456789');
    expect(PhoneNormalizer::normalize('+628123456789'))->toBe('628123456789');
    expect(PhoneNormalizer::normalize('628123456789'))->toBe('628123456789');
    expect(PhoneNormalizer::normalize('8123456789'))->toBe('628123456789');
    expect(PhoneNormalizer::normalize('0812-3456-789'))->toBe('628123456789');
});

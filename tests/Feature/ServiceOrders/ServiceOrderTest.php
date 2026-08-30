<?php

use App\Domain\ServiceOrders\Actions\CreateServiceOrder;
use App\Models\Branch;
use App\Models\Customer;
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
    $this->category = ServiceCategory::factory()->create();
    $this->service = ServiceCatalog::factory()->create([
        'category_id' => $this->category->id,
        'base_price' => 75000,
        'estimated_duration_minutes' => 60,
    ]);
});

function orderUser(Branch $branch, string $role): User
{
    $user = User::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
    $user->assignRole($role);

    return $user;
}

function orderToken(User $user): string
{
    return $user->createToken('test')->plainTextToken;
}

function orderAuthed(TestCase $test, string $method, string $uri, User $user, array $data = []): TestResponse
{
    $headers = ['Authorization' => 'Bearer '.orderToken($user)];
    if (strtoupper($method) === 'GET') {
        return $test->{$method.'Json'}($uri, $headers);
    }

    return $test->{$method.'Json'}($uri, $data, $headers);
}

it('creates an order with a generated unique order number and totals', function () {
    $kasir = orderUser($this->branch, 'kasir');
    $customer = Customer::factory()->create();

    $response = orderAuthed($this, 'post', '/api/v1/service-orders', $kasir, [
        'customer_id' => $customer->id,
        'items' => [
            ['service_catalog_id' => $this->service->id, 'quantity' => 2],
        ],
        'shoes' => [
            ['brand' => 'Nike', 'model' => 'Air Max', 'size' => '42'],
        ],
    ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.subtotal', 150000)
        ->assertJsonPath('data.total_amount', 150000)
        ->assertJsonPath('data.remaining_amount', 150000);

    $orderNumber = $response->json('data.order_number');
    expect($orderNumber)->toMatch('/^SO-\d{8}-\d{4}$/');

    $this->assertDatabaseHas('service_orders', ['order_number' => $orderNumber]);
});

it('snapshots the service name and price at intake', function () {
    $kasir = orderUser($this->branch, 'kasir');
    $customer = Customer::factory()->create();

    orderAuthed($this, 'post', '/api/v1/service-orders', $kasir, [
        'customer_id' => $customer->id,
        'items' => [
            ['service_catalog_id' => $this->service->id],
        ],
    ])
        ->assertCreated()
        ->assertJsonPath('data.items.0.service_name', $this->service->name)
        ->assertJsonPath('data.items.0.unit_price', 75000)
        ->assertJsonPath('data.items.0.status', 'pending');
});

it('requires at least one service item', function () {
    $kasir = orderUser($this->branch, 'kasir');
    $customer = Customer::factory()->create();

    orderAuthed($this, 'post', '/api/v1/service-orders', $kasir, [
        'customer_id' => $customer->id,
        'items' => [],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('items');
});

it('rejects inactive services', function () {
    $kasir = orderUser($this->branch, 'kasir');
    $customer = Customer::factory()->create();
    $inactive = ServiceCatalog::factory()->create(['active' => false]);

    orderAuthed($this, 'post', '/api/v1/service-orders', $kasir, [
        'customer_id' => $customer->id,
        'items' => [
            ['service_catalog_id' => $inactive->id],
        ],
    ])
        ->assertStatus(409);
});

it('creates an order with multiple items and shoes via pivot', function () {
    $kasir = orderUser($this->branch, 'kasir');
    $customer = Customer::factory()->create();
    $second = ServiceCatalog::factory()->create([
        'category_id' => $this->category->id,
        'base_price' => 40000,
    ]);

    orderAuthed($this, 'post', '/api/v1/service-orders', $kasir, [
        'customer_id' => $customer->id,
        'items' => [
            ['service_catalog_id' => $this->service->id],
            ['service_catalog_id' => $second->id],
        ],
        'shoes' => [
            ['brand' => 'Converse'],
            ['brand' => 'Vans'],
        ],
    ])
        ->assertCreated()
        ->assertJsonPath('data.subtotal', 115000)
        ->assertJsonCount(2, 'data.items')
        ->assertJsonCount(2, 'data.shoes');
});

it('lists orders with filter by status', function () {
    $kasir = orderUser($this->branch, 'kasir');
    $customer = Customer::factory()->create();

    $order = CreateServiceOrder::create(
        customer: $customer,
        branchId: $this->branch->id,
        receivedBy: $kasir->id,
        items: [['service_catalog_id' => $this->service->id]],
    );

    orderAuthed($this, 'get', '/api/v1/service-orders?status=draft', $kasir)
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $order->id);
});

it('shows an order with items, shoes and history', function () {
    $kasir = orderUser($this->branch, 'kasir');
    $customer = Customer::factory()->create();

    $order = CreateServiceOrder::create(
        customer: $customer,
        branchId: $this->branch->id,
        receivedBy: $kasir->id,
        items: [['service_catalog_id' => $this->service->id]],
        shoes: [['brand' => 'Nike']],
    );

    orderAuthed($this, 'get', "/api/v1/service-orders/{$order->id}", $kasir)
        ->assertOk()
        ->assertJsonPath('data.id', $order->id)
        ->assertJsonStructure([
            'data' => ['order_number', 'status', 'items', 'shoes', 'status_histories'],
        ]);
});

it('transitions order through valid states and records history', function () {
    $kasir = orderUser($this->branch, 'kasir');
    $customer = Customer::factory()->create();

    $order = CreateServiceOrder::create(
        customer: $customer,
        branchId: $this->branch->id,
        receivedBy: $kasir->id,
        items: [['service_catalog_id' => $this->service->id]],
    );

    orderAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/status", $kasir, [
        'status' => 'received',
    ])
        ->assertOk()
        ->assertJsonPath('data.status', 'received');

    $this->assertDatabaseHas('service_order_status_histories', [
        'service_order_id' => $order->id,
        'from_status' => 'draft',
        'to_status' => 'received',
    ]);
});

it('rejects invalid status transition with 409', function () {
    $kasir = orderUser($this->branch, 'kasir');
    $customer = Customer::factory()->create();

    $order = CreateServiceOrder::create(
        customer: $customer,
        branchId: $this->branch->id,
        receivedBy: $kasir->id,
        items: [['service_catalog_id' => $this->service->id]],
    );

    // draft → approved tidak valid.
    orderAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/status", $kasir, [
        'status' => 'approved',
    ])
        ->assertStatus(409);
});

it('requires reason when cancelling', function () {
    $kasir = orderUser($this->branch, 'kasir');
    $customer = Customer::factory()->create();

    $order = CreateServiceOrder::create(
        customer: $customer,
        branchId: $this->branch->id,
        receivedBy: $kasir->id,
        items: [['service_catalog_id' => $this->service->id]],
    );

    orderAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/status", $kasir, [
        'status' => 'cancelled',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('reason');
});

it('denies order creation to technician', function () {
    $teknisi = orderUser($this->branch, 'teknisi');
    $customer = Customer::factory()->create();

    orderAuthed($this, 'post', '/api/v1/service-orders', $teknisi, [
        'customer_id' => $customer->id,
        'items' => [['service_catalog_id' => $this->service->id]],
    ])
        ->assertStatus(403);
});

it('snapshots master price so later price change does not affect the order', function () {
    $kasir = orderUser($this->branch, 'kasir');
    $customer = Customer::factory()->create();

    $order = CreateServiceOrder::create(
        customer: $customer,
        branchId: $this->branch->id,
        receivedBy: $kasir->id,
        items: [['service_catalog_id' => $this->service->id]],
    );

    expect($order->items->first()->unit_price)->toBe(75000);

    // Master price changes.
    $this->service->update(['base_price' => 90000]);

    $order->refresh();
    expect($order->items->first()->unit_price)->toBe(75000);
});

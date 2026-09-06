<?php

use App\Domain\Inventory\Actions\AdjustStockBalance;
use App\Domain\ServiceOrders\Actions\CreateServiceOrder;
use App\Exceptions\DomainConflictException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\ServiceCatalog;
use App\Models\ServiceCategory;
use App\Models\ServiceOrder;
use App\Models\StockBalance;
use App\Models\StockItem;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

beforeEach(function () {
    $this->branch = Branch::firstOrCreate(
        ['code' => 'TEST-CASH'],
        ['name' => 'Test Cabang', 'is_active' => true],
    );
});

function inv6User(Branch $branch, string $role): User
{
    $user = User::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
    $user->assignRole($role);

    return $user;
}

function inv6Token(User $user): string
{
    return $user->createToken('test')->plainTextToken;
}

function inv6Authed(TestCase $test, string $method, string $uri, User $user, array $data = []): TestResponse
{
    $headers = ['Authorization' => 'Bearer '.inv6Token($user)];
    if (strtoupper($method) === 'GET') {
        return $test->{$method.'Json'}($uri, $headers);
    }

    return $test->{$method.'Json'}($uri, $data, $headers);
}

function inv6Seed(Branch $branch, int $qty = 100, int $min = 5): StockItem
{
    $item = StockItem::factory()->create(['min_stock' => $min]);
    AdjustStockBalance::apply($item, $branch->id, $qty, 'stock_in');

    return $item;
}

function inv6Order(User $user): ServiceOrder
{
    $cat = ServiceCategory::factory()->create();
    $svc = ServiceCatalog::factory()->create(['category_id' => $cat->id, 'base_price' => 50000]);
    $customer = Customer::factory()->create();

    return CreateServiceOrder::create(
        customer: $customer,
        branchId: $user->branch_id,
        receivedBy: $user->id,
        items: [['service_catalog_id' => $svc->id]],
    );
}

it('creates a stock item via API', function () {
    $admin = inv6User($this->branch, 'admin');

    inv6Authed($this, 'post', '/api/v1/inventory/items', $admin, [
        'sku' => 'CLN-01',
        'name' => 'Pembersih Solvent',
        'unit' => 'ml',
        'min_stock' => 100,
    ])
        ->assertCreated()
        ->assertJsonPath('data.sku', 'CLN-01')
        ->assertJsonPath('data.quantity', 0);
});

it('adjusts stock up and records a movement', function () {
    $admin = inv6User($this->branch, 'admin');
    $item = StockItem::factory()->create();

    inv6Authed($this, 'post', '/api/v1/inventory/adjustments', $admin, [
        'type' => 'restock',
        'reason' => 'Beli dari supplier',
        'items' => [
            ['stock_item_id' => $item->id, 'quantity' => 50],
        ],
    ])
        ->assertCreated();

    $balance = StockBalance::where('stock_item_id', $item->id)->where('branch_id', $this->branch->id)->first();
    expect($balance?->quantity)->toBe(50);
    $this->assertDatabaseHas('stock_movements', ['stock_item_id' => $item->id, 'quantity' => 50, 'type' => 'adjustment']);
});

it('rejects adjustment that would drive stock negative', function () {
    $admin = inv6User($this->branch, 'admin');
    $item = inv6Seed($this->branch, 10);

    inv6Authed($this, 'post', '/api/v1/inventory/adjustments', $admin, [
        'type' => 'correction',
        'reason' => 'Salah catat',
        'items' => [
            ['stock_item_id' => $item->id, 'quantity' => -20],
        ],
    ])
        ->assertStatus(409);

    $balance = StockBalance::where('stock_item_id', $item->id)->where('branch_id', $this->branch->id)->first();
    expect($balance?->quantity)->toBe(10);
});

it('records usage against an order and decrements stock', function () {
    $admin = inv6User($this->branch, 'admin');
    $item = inv6Seed($this->branch, 50);
    $order = inv6Order($admin);

    inv6Authed($this, 'post', "/api/v1/service-orders/{$order->id}/inventory/usages", $admin, [
        'usages' => [
            ['stock_item_id' => $item->id, 'quantity' => 5],
        ],
    ])
        ->assertCreated();

    $balance = StockBalance::where('stock_item_id', $item->id)->where('branch_id', $this->branch->id)->first();
    expect($balance?->quantity)->toBe(45);
    $this->assertDatabaseHas('stock_usages', ['service_order_id' => $order->id, 'stock_item_id' => $item->id, 'quantity' => 5]);
    $this->assertDatabaseHas('stock_movements', ['stock_item_id' => $item->id, 'quantity' => -5, 'type' => 'usage']);
});

it('rejects usage when stock insufficient (non-negative)', function () {
    $admin = inv6User($this->branch, 'admin');
    $item = inv6Seed($this->branch, 3);
    $order = inv6Order($admin);

    inv6Authed($this, 'post', "/api/v1/service-orders/{$order->id}/inventory/usages", $admin, [
        'usages' => [
            ['stock_item_id' => $item->id, 'quantity' => 10],
        ],
    ])
        ->assertStatus(409);

    $balance = StockBalance::where('stock_item_id', $item->id)->where('branch_id', $this->branch->id)->first();
    expect($balance?->quantity)->toBe(3);
});

it('rolls back the whole usage batch if one item fails', function () {
    $admin = inv6User($this->branch, 'admin');
    $ok = inv6Seed($this->branch, 50);
    $low = inv6Seed($this->branch, 2);
    $order = inv6Order($admin);

    inv6Authed($this, 'post', "/api/v1/service-orders/{$order->id}/inventory/usages", $admin, [
        'usages' => [
            ['stock_item_id' => $ok->id, 'quantity' => 5],
            ['stock_item_id' => $low->id, 'quantity' => 99],
        ],
    ])
        ->assertStatus(409);

    // $ok tidak boleh terpotong karena batch digagalkan (rollback).
    $balance = StockBalance::where('stock_item_id', $ok->id)->where('branch_id', $this->branch->id)->first();
    expect($balance?->quantity)->toBe(50);
    $this->assertDatabaseCount('stock_usages', 0);
});

it('records opname adjusting balance to counted physical', function () {
    $admin = inv6User($this->branch, 'admin');
    $item = inv6Seed($this->branch, 40);

    inv6Authed($this, 'post', '/api/v1/inventory/opname', $admin, [
        'counts' => [
            ['stock_item_id' => $item->id, 'counted' => 37],
        ],
    ])
        ->assertCreated();

    $balance = StockBalance::where('stock_item_id', $item->id)->where('branch_id', $this->branch->id)->first();
    expect($balance?->quantity)->toBe(37);
});

it('lists stock items with balance and low_stock flag', function () {
    $admin = inv6User($this->branch, 'admin');
    $ok = inv6Seed($this->branch, 50, 5);
    $low = inv6Seed($this->branch, 2, 5);

    inv6Authed($this, 'get', '/api/v1/inventory/items', $admin)
        ->assertOk()
        ->assertJsonPath('meta.total', 2);

    inv6Authed($this, 'get', '/api/v1/inventory/items?low_stock=1', $admin)
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $low->id)
        ->assertJsonPath('data.0.low_stock', true);
});

it('lists stock movements (kartu stok)', function () {
    $admin = inv6User($this->branch, 'admin');
    $item = inv6Seed($this->branch, 30);

    inv6Authed($this, 'get', "/api/v1/inventory/items/{$item->id}/movements", $admin)
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.type', 'stock_in');
});

it('prevents concurrent over-draw using row lock', function () {
    $admin = inv6User($this->branch, 'admin');
    $item = inv6Seed($this->branch, 10);

    $balance = StockBalance::where('stock_item_id', $item->id)->where('branch_id', $this->branch->id)->firstOrFail();
    expect(fn () => AdjustStockBalance::apply($item, $this->branch->id, -8, 'usage'))
        ->not->toThrow(DomainConflictException::class);

    // Snapshot stale: saldo di memori masih 10, tapi aktual sudah 2.
    expect(fn () => AdjustStockBalance::apply($item, $this->branch->id, -8, 'usage'))
        ->toThrow(DomainConflictException::class);

    $balance->refresh();
    expect($balance->quantity)->toBe(2);
});

it('denies inventory adjust to teknisi', function () {
    $teknisi = inv6User($this->branch, 'teknisi');

    inv6Authed($this, 'post', '/api/v1/inventory/adjustments', $teknisi, [
        'type' => 'restock',
        'reason' => 'x',
        'items' => [],
    ])
        ->assertStatus(403);
});

it('allows teknisi to record usage (inventory.usage)', function () {
    $teknisi = inv6User($this->branch, 'teknisi');
    $item = inv6Seed($this->branch, 20);
    $order = inv6Order($teknisi);

    inv6Authed($this, 'post', "/api/v1/service-orders/{$order->id}/inventory/usages", $teknisi, [
        'usages' => [
            ['stock_item_id' => $item->id, 'quantity' => 3],
        ],
    ])
        ->assertCreated();

    $balance = StockBalance::where('stock_item_id', $item->id)->where('branch_id', $this->branch->id)->first();
    expect($balance?->quantity)->toBe(17);
});

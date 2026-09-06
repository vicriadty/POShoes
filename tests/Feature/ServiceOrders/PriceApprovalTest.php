<?php

use App\Domain\ServiceOrders\Actions\CreateServiceOrder;
use App\Domain\ServiceOrders\Actions\RequestPriceChange;
use App\Domain\ServiceOrders\Actions\TransitionOrderStatus;
use App\Domain\ServiceOrders\Enums\OrderStatus;
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
    $this->service = ServiceCatalog::factory()->create(['base_price' => 50000]);
});

function priceUser(Branch $branch, string $role): User
{
    $user = User::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
    $user->assignRole($role);

    return $user;
}

function priceToken(User $user): string
{
    return $user->createToken('test')->plainTextToken;
}

function priceAuthed(TestCase $test, string $method, string $uri, User $user, array $data = []): TestResponse
{
    $headers = ['Authorization' => 'Bearer '.priceToken($user)];
    if (strtoupper($method) === 'GET') {
        return $test->{$method.'Json'}($uri, $headers);
    }

    return $test->{$method.'Json'}($uri, $data, $headers);
}

/**
 * Order inspection dengan 1 item.
 */
function inspectionOrder(TestCase $test, User $user, int $basePrice = 50000): array
{
    $cat = ServiceCategory::factory()->create();
    $svc = ServiceCatalog::factory()->create(['category_id' => $cat->id, 'base_price' => $basePrice]);
    $customer = Customer::factory()->create();

    $order = CreateServiceOrder::create(
        customer: $customer,
        branchId: $user->branch_id,
        receivedBy: $user->id,
        items: [['service_catalog_id' => $svc->id]],
    );
    $item = $order->items()->first();

    TransitionOrderStatus::transition($order, OrderStatus::Received, changedBy: $user->id);
    TransitionOrderStatus::transition($order, OrderStatus::Inspection, changedBy: $user->id);

    return [$order, $item];
}

it('requests a price change and moves order to waiting_approval', function () {
    $kasir = priceUser($this->branch, 'kasir');
    [$order, $item] = inspectionOrder($this, $kasir, 50000);

    priceAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/items/{$item->id}/price-change", $kasir, [
        'unit_price' => 75000,
        'reason' => 'Butuh deep cleaning tambahan.',
    ])
        ->assertOk()
        ->assertJsonPath('data.status', 'waiting_approval')
        ->assertJsonPath('data.items.0.proposed_price', 75000)
        ->assertJsonPath('data.items.0.unit_price', 50000); // estimasi lama tak berubah
});

it('rejects price change unless order is in inspection', function () {
    $kasir = priceUser($this->branch, 'kasir');
    $cat = ServiceCategory::factory()->create();
    $svc = ServiceCatalog::factory()->create(['category_id' => $cat->id, 'base_price' => 50000]);
    $customer = Customer::factory()->create();
    $order = CreateServiceOrder::create(customer: $customer, branchId: $kasir->branch_id, receivedBy: $kasir->id, items: [['service_catalog_id' => $svc->id]]);
    $item = $order->items()->first();

    // Masih draft — bukan inspection.
    priceAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/items/{$item->id}/price-change", $kasir, [
        'unit_price' => 60000,
    ])
        ->assertStatus(409);
});

it('approves price change, snapshots final price and recalculates total', function () {
    $kasir = priceUser($this->branch, 'kasir');
    $admin = priceUser($this->branch, 'admin');
    [$order, $item] = inspectionOrder($this, $kasir, 50000);

    // Ajukan perubahan (via action, setup state).
    RequestPriceChange::request($item, 80000, changedBy: $kasir->id);

    // Approve (admin) — harga final disetujui 80000
    priceAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/items/{$item->id}/price-approve", $admin, [
        'approved_price' => 80000,
    ])
        ->assertOk()
        ->assertJsonPath('data.status', 'approved')
        ->assertJsonPath('data.total_amount', 80000)
        ->assertJsonPath('data.remaining_amount', 80000);

    $order->refresh();
    expect($order->items()->first()->unit_price)->toBe(80000);
    expect($order->items()->first()->price_approved_by)->toBe($admin->id);
    expect($order->items()->first()->price_approved_at)->not->toBeNull();
    expect($order->items()->first()->proposed_price)->toBeNull();
});

it('recalculates totals across multiple items on approval', function () {
    $kasir = priceUser($this->branch, 'kasir');
    $admin = priceUser($this->branch, 'admin');
    $cat = ServiceCategory::factory()->create();
    $svc1 = ServiceCatalog::factory()->create(['category_id' => $cat->id, 'base_price' => 50000]);
    $svc2 = ServiceCatalog::factory()->create(['category_id' => $cat->id, 'base_price' => 30000]);
    $customer = Customer::factory()->create();
    $order = CreateServiceOrder::create(
        customer: $customer,
        branchId: $kasir->branch_id,
        receivedBy: $kasir->id,
        items: [
            ['service_catalog_id' => $svc1->id],
            ['service_catalog_id' => $svc2->id],
        ],
    );
    $itemA = $order->items()->get()[0];
    $itemB = $order->items()->get()[1];

    TransitionOrderStatus::transition($order, OrderStatus::Received, changedBy: $kasir->id);
    TransitionOrderStatus::transition($order, OrderStatus::Inspection, changedBy: $kasir->id);

    expect($order->subtotal)->toBe(80000);

    // Ubah harga item A → 90000 (setup via action)
    RequestPriceChange::request($itemA, 90000, changedBy: $kasir->id);
    priceAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/items/{$itemA->id}/price-approve", $admin, ['approved_price' => 90000])
        ->assertOk()
        ->assertJsonPath('data.status', 'approved')
        ->assertJsonPath('data.total_amount', 120000); // 90k + 30k
});

it('denies approval to kasir (admin/owner only)', function () {
    $kasir = priceUser($this->branch, 'kasir');
    $kasir2 = priceUser($this->branch, 'kasir');
    [$order, $item] = inspectionOrder($this, $kasir, 50000);

    priceAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/items/{$item->id}/price-change", $kasir, ['unit_price' => 80000])->assertOk();

    // Kasir lain mencoba approve → 403.
    priceAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/items/{$item->id}/price-approve", $kasir2, ['approved_price' => 80000])
        ->assertStatus(403);
});

it('rejects approval when order is not waiting_approval', function () {
    $kasir = priceUser($this->branch, 'kasir');
    $admin = priceUser($this->branch, 'admin');
    [$order, $item] = inspectionOrder($this, $kasir, 50000);

    // Order masih inspection (belum ajukan perubahan).
    priceAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/items/{$item->id}/price-approve", $admin, ['approved_price' => 70000])
        ->assertStatus(409);
});

it('rejects item from another order in price endpoints', function () {
    $kasir = priceUser($this->branch, 'kasir');
    [$order, $item] = inspectionOrder($this, $kasir, 50000);

    // Buat order lain dengan item lain.
    $cat = ServiceCategory::factory()->create();
    $svc = ServiceCatalog::factory()->create(['category_id' => $cat->id, 'base_price' => 40000]);
    $customer2 = Customer::factory()->create();
    $other = CreateServiceOrder::create(customer: $customer2, branchId: $kasir->branch_id, receivedBy: $kasir->id, items: [['service_catalog_id' => $svc->id]]);
    $otherItem = $other->items()->first();

    priceAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/items/{$otherItem->id}/price-change", $kasir, ['unit_price' => 90000])
        ->assertStatus(404);
});

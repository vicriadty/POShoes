<?php

use App\Domain\Payments\Actions\RecordPayment;
use App\Domain\ServiceOrders\Actions\CreateServiceOrder;
use App\Domain\ServiceOrders\Actions\TransitionOrderStatus;
use App\Domain\ServiceOrders\Enums\OrderStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\ServiceCatalog;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

beforeEach(function () {
    $this->branch = Branch::firstOrCreate(
        ['code' => 'TEST-CASH'],
        ['name' => 'Test Cabang', 'is_active' => true],
    );
    $this->method = PaymentMethod::create([
        'code' => 'cash',
        'name' => 'Tunai',
        'type' => 'manual',
        'active' => true,
        'sort_order' => 10,
    ]);
});

function invUser(Branch $branch, string $role): User
{
    $user = User::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
    $user->assignRole($role);

    return $user;
}

function invToken(User $user): string
{
    return $user->createToken('test')->plainTextToken;
}

function invAuthed(TestCase $test, string $method, string $uri, User $user, array $data = []): TestResponse
{
    $headers = ['Authorization' => 'Bearer '.invToken($user)];
    if (strtoupper($method) === 'GET') {
        return $test->{$method.'Json'}($uri, $headers);
    }

    return $test->{$method.'Json'}($uri, $data, $headers);
}

function invOrder(User $kasir, int $basePrice = 100000): ServiceOrder
{
    $service = ServiceCatalog::factory()->create(['base_price' => $basePrice]);
    $customer = Customer::factory()->create();

    return CreateServiceOrder::create(
        customer: $customer,
        branchId: $kasir->branch_id,
        receivedBy: $kasir->id,
        items: [['service_catalog_id' => $service->id]],
    );
}

it('generates an invoice for an order', function () {
    $kasir = invUser($this->branch, 'kasir');
    $order = invOrder($kasir);

    invAuthed($this, 'get', "/api/v1/service-orders/{$order->id}/invoice", $kasir)
        ->assertOk()
        ->assertJsonStructure(['data' => ['invoice_number', 'status']])
        ->assertJsonPath('data.invoice_number', fn ($n) => str_starts_with($n, 'INV-'));
});

it('does not duplicate invoices for the same order', function () {
    $kasir = invUser($this->branch, 'kasir');
    $order = invOrder($kasir);

    $first = invAuthed($this, 'get', "/api/v1/service-orders/{$order->id}/invoice", $kasir)->json('data');
    $second = invAuthed($this, 'get', "/api/v1/service-orders/{$order->id}/invoice", $kasir)->json('data');

    expect($second['invoice_number'])->toBe($first['invoice_number']);
});

it('marks invoice as paid when order is fully paid', function () {
    $kasir = invUser($this->branch, 'kasir');
    $order = invOrder($kasir, 100000);

    RecordPayment::record($order, $this->method, 100000, $kasir->id);

    invAuthed($this, 'get', "/api/v1/service-orders/{$order->id}/invoice", $kasir)
        ->assertOk()
        ->assertJsonPath('data.status', 'paid');
});

it('generates a downloadable PDF invoice', function () {
    $kasir = invUser($this->branch, 'kasir');
    $order = invOrder($kasir, 100000);

    $this->withToken(invToken($kasir))
        ->get("/api/v1/service-orders/{$order->id}/invoice/pdf")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('generates unique invoice numbers across orders', function () {
    $kasir = invUser($this->branch, 'kasir');
    $order1 = invOrder($kasir);
    $order2 = invOrder($kasir);

    $i1 = invAuthed($this, 'get', "/api/v1/service-orders/{$order1->id}/invoice", $kasir)->json('data');
    $i2 = invAuthed($this, 'get', "/api/v1/service-orders/{$order2->id}/invoice", $kasir)->json('data');

    expect($i1['invoice_number'])->not->toBe($i2['invoice_number']);
});

it('marks invoice as sent', function () {
    $kasir = invUser($this->branch, 'kasir');
    $order = invOrder($kasir);

    invAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/invoice/send", $kasir, [])
        ->assertOk()
        ->assertJsonPath('data.sent_at', fn ($v) => $v !== null);
});

it('can pickup a fully paid order in ready_for_pickup state', function () {
    $kasir = invUser($this->branch, 'kasir');
    $admin = invUser($this->branch, 'admin');
    $order = invOrder($kasir, 100000);

    RecordPayment::record($order, $this->method, 100000, $kasir->id);

    // siap diambil (melalui state yang valid)
    TransitionOrderStatus::transition($order, OrderStatus::Received, changedBy: $kasir->id);
    TransitionOrderStatus::transition($order, OrderStatus::Inspection, changedBy: $kasir->id);
    TransitionOrderStatus::transition($order, OrderStatus::Approved, changedBy: $admin->id);
    TransitionOrderStatus::transition($order, OrderStatus::InProgress, changedBy: $admin->id);
    TransitionOrderStatus::transition($order, OrderStatus::QualityCheck, changedBy: $admin->id);
    TransitionOrderStatus::transition($order, OrderStatus::ReadyForPickup, changedBy: $admin->id);

    invAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/pickup", $kasir, [])
        ->assertOk()
        ->assertJsonPath('data.status', 'picked_up');
});

it('rejects pickup while order has remaining balance', function () {
    $kasir = invUser($this->branch, 'kasir');
    $admin = invUser($this->branch, 'admin');
    $order = invOrder($kasir, 100000);

    RecordPayment::record($order, $this->method, 40000, $kasir->id);

    TransitionOrderStatus::transition($order, OrderStatus::Received, changedBy: $kasir->id);
    TransitionOrderStatus::transition($order, OrderStatus::Inspection, changedBy: $kasir->id);
    TransitionOrderStatus::transition($order, OrderStatus::Approved, changedBy: $admin->id);
    TransitionOrderStatus::transition($order, OrderStatus::InProgress, changedBy: $admin->id);
    TransitionOrderStatus::transition($order, OrderStatus::QualityCheck, changedBy: $admin->id);
    TransitionOrderStatus::transition($order, OrderStatus::ReadyForPickup, changedBy: $admin->id);

    invAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/pickup", $kasir, [])
        ->assertStatus(409);
});

it('rejects pickup when order is not ready for pickup', function () {
    $kasir = invUser($this->branch, 'kasir');
    $admin = invUser($this->branch, 'admin');
    $order = invOrder($kasir, 100000);

    RecordPayment::record($order, $this->method, 100000, $kasir->id);

    // Masih draft — pickup tidak valid dari state ini
    invAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/pickup", $kasir, [])
        ->assertStatus(409);
});

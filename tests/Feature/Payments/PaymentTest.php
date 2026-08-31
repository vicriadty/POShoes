<?php

use App\Domain\Payments\Actions\RecordPayment;
use App\Domain\ServiceOrders\Actions\CreateServiceOrder;
use App\Domain\ServiceOrders\Actions\TransitionOrderStatus;
use App\Domain\ServiceOrders\Enums\OrderStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\ServiceCatalog;
use App\Models\ServiceCategory;
use App\Models\ServiceOrder;
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
        'base_price' => 100000,
    ]);
    $this->method = PaymentMethod::create([
        'code' => 'cash',
        'name' => 'Tunai',
        'type' => 'manual',
        'active' => true,
        'sort_order' => 10,
    ]);
});

function payUser(Branch $branch, string $role): User
{
    $user = User::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
    $user->assignRole($role);

    return $user;
}

function payToken(User $user): string
{
    return $user->createToken('test')->plainTextToken;
}

function payAuthed(TestCase $test, string $method, string $uri, User $user, array $data = []): TestResponse
{
    $headers = ['Authorization' => 'Bearer '.payToken($user)];
    if (strtoupper($method) === 'GET') {
        return $test->{$method.'Json'}($uri, $headers);
    }

    return $test->{$method.'Json'}($uri, $data, $headers);
}

function makePaidOrder(TestCase $test, User $kasir, int $basePrice = 100000): ServiceOrder
{
    $service = ServiceCatalog::factory()->create(['base_price' => $basePrice]);
    $customer = Customer::factory()->create();

    $order = CreateServiceOrder::create(
        customer: $customer,
        branchId: $kasir->branch_id,
        receivedBy: $kasir->id,
        items: [['service_catalog_id' => $service->id]],
    );

    return $order;
}

it('records a payment and recalculates order totals', function () {
    $kasir = payUser($this->branch, 'kasir');
    $order = makePaidOrder($this, $kasir, 100000);

    $response = payAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/payments", $kasir, [
        'payment_method_id' => $this->method->id,
        'amount' => 40000,
    ])
        ->assertCreated()
        ->assertJsonPath('data.amount', 40000)
        ->assertJsonStructure(['data' => ['payment_number', 'method']]);

    $this->assertDatabaseHas('payments', ['service_order_id' => $order->id, 'amount' => 40000]);

    $order->refresh();
    expect($order->paid_amount)->toBe(40000);
    expect($order->remaining_amount)->toBe(60000);
});

it('allows partial then full payment (DP + lunas)', function () {
    $kasir = payUser($this->branch, 'kasir');
    $order = makePaidOrder($this, $kasir, 100000);

    // DP 30000
    payAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/payments", $kasir, [
        'payment_method_id' => $this->method->id,
        'amount' => 30000,
    ])->assertCreated();

    // Pelunasan 70000
    payAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/payments", $kasir, [
        'payment_method_id' => $this->method->id,
        'amount' => 70000,
    ])->assertCreated();

    $order->refresh();
    expect($order->paid_amount)->toBe(100000);
    expect($order->remaining_amount)->toBe(0);
});

it('rejects overpayment beyond remaining balance', function () {
    $kasir = payUser($this->branch, 'kasir');
    $order = makePaidOrder($this, $kasir, 100000);

    payAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/payments", $kasir, [
        'payment_method_id' => $this->method->id,
        'amount' => 150000,
    ])
        ->assertStatus(409);
});

it('rejects payment on cancelled order', function () {
    $kasir = payUser($this->branch, 'kasir');
    $order = makePaidOrder($this, $kasir, 100000);

    TransitionOrderStatus::transition($order, OrderStatus::Cancelled, reason: 'batal', changedBy: $kasir->id);

    payAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/payments", $kasir, [
        'payment_method_id' => $this->method->id,
        'amount' => 10000,
    ])
        ->assertStatus(409);
});

it('rejects payment with inactive method', function () {
    $kasir = payUser($this->branch, 'kasir');
    $order = makePaidOrder($this, $kasir, 100000);
    $inactive = PaymentMethod::create([
        'code' => 'other',
        'name' => 'Lainnya',
        'type' => 'manual',
        'active' => false,
        'sort_order' => 90,
    ]);

    payAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/payments", $kasir, [
        'payment_method_id' => $inactive->id,
        'amount' => 10000,
    ])
        ->assertStatus(409);
});

it('lists payments for an order', function () {
    $kasir = payUser($this->branch, 'kasir');
    $order = makePaidOrder($this, $kasir, 100000);

    RecordPayment::record($order, $this->method, 40000, $kasir->id);

    payAuthed($this, 'get', "/api/v1/service-orders/{$order->id}/payments", $kasir)
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('denies payment creation to technician', function () {
    $teknisi = payUser($this->branch, 'teknisi');
    $order = makePaidOrder($this, $teknisi, 100000);

    payAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/payments", $teknisi, [
        'payment_method_id' => $this->method->id,
        'amount' => 10000,
    ])
        ->assertStatus(403);
});

it('generates unique payment numbers', function () {
    $kasir = payUser($this->branch, 'kasir');
    $order = makePaidOrder($this, $kasir, 100000);

    $p1 = RecordPayment::record($order, $this->method, 30000, $kasir->id);
    $p2 = RecordPayment::record($order, $this->method, 30000, $kasir->id);

    expect($p1->payment_number)->not->toBe($p2->payment_number);
    expect($p1->payment_number)->toMatch('/^PAY-\d{8}-\d{4}$/');
});

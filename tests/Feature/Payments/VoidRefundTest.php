<?php

use App\Domain\Payments\Actions\RecordPayment;
use App\Domain\Payments\Actions\RefundPayment;
use App\Domain\Payments\Actions\VoidPayment;
use App\Domain\ServiceOrders\Actions\CreateServiceOrder;
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

function vrUser(Branch $branch, string $role): User
{
    $user = User::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
    $user->assignRole($role);

    return $user;
}

function vrToken(User $user): string
{
    return $user->createToken('test')->plainTextToken;
}

function vrAuthed(TestCase $test, string $method, string $uri, User $user, array $data = []): TestResponse
{
    $headers = ['Authorization' => 'Bearer '.vrToken($user)];

    return $test->{$method.'Json'}($uri, $data, $headers);
}

function vrOrder(User $kasir, int $basePrice = 100000): ServiceOrder
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

it('voids a payment and restores remaining balance', function () {
    $kasir = vrUser($this->branch, 'kasir');
    $admin = vrUser($this->branch, 'admin');
    $order = vrOrder($kasir, 100000);

    $payment = RecordPayment::record($order, $this->method, 40000, $kasir->id);

    $order->refresh();
    expect($order->remaining_amount)->toBe(60000);

    vrAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/payments/{$payment->id}/void", $admin, [
        'reason' => 'Salah metode',
    ])
        ->assertOk()
        ->assertJsonPath('data.is_voided', true);

    $order->refresh();
    expect($order->paid_amount)->toBe(0);
    expect($order->remaining_amount)->toBe(100000);
});

it('rejects voiding an already-voided payment', function () {
    $kasir = vrUser($this->branch, 'kasir');
    $admin = vrUser($this->branch, 'admin');
    $order = vrOrder($kasir, 100000);

    $payment = RecordPayment::record($order, $this->method, 40000, $kasir->id);
    VoidPayment::void($payment, $admin->id, 'tes');

    vrAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/payments/{$payment->id}/void", $admin, [
        'reason' => 'lagi',
    ])
        ->assertStatus(409);
});

it('denies void to kasir (admin/owner only)', function () {
    $kasir = vrUser($this->branch, 'kasir');
    $order = vrOrder($kasir, 100000);

    $payment = RecordPayment::record($order, $this->method, 40000, $kasir->id);

    vrAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/payments/{$payment->id}/void", $kasir, [
        'reason' => 'tes',
    ])
        ->assertStatus(403);
});

it('refunds partially and keeps track of refundable amount', function () {
    $kasir = vrUser($this->branch, 'kasir');
    $admin = vrUser($this->branch, 'admin');
    $order = vrOrder($kasir, 100000);

    $payment = RecordPayment::record($order, $this->method, 100000, $kasir->id);

    // Refund 30000
    vrAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/payments/{$payment->id}/refund", $admin, [
        'payment_method_id' => $this->method->id,
        'amount' => 30000,
    ])
        ->assertCreated()
        ->assertJsonPath('data.amount', -30000)
        ->assertJsonPath('data.refunded_from', $payment->id);

    $order->refresh();
    expect($order->paid_amount)->toBe(70000);
    expect($order->remaining_amount)->toBe(30000);
});

it('rejects refund exceeding the remaining refundable', function () {
    $kasir = vrUser($this->branch, 'kasir');
    $admin = vrUser($this->branch, 'admin');
    $order = vrOrder($kasir, 100000);

    $payment = RecordPayment::record($order, $this->method, 50000, $kasir->id);

    vrAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/payments/{$payment->id}/refund", $admin, [
        'payment_method_id' => $this->method->id,
        'amount' => 60000,
    ])
        ->assertStatus(409);
});

it('rejects refunding a voided payment', function () {
    $kasir = vrUser($this->branch, 'kasir');
    $admin = vrUser($this->branch, 'admin');
    $order = vrOrder($kasir, 100000);

    $payment = RecordPayment::record($order, $this->method, 50000, $kasir->id);
    VoidPayment::void($payment, $admin->id, 'batal');

    vrAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/payments/{$payment->id}/refund", $admin, [
        'payment_method_id' => $this->method->id,
        'amount' => 10000,
    ])
        ->assertStatus(409);
});

it('denies refund to kasir', function () {
    $kasir = vrUser($this->branch, 'kasir');
    $order = vrOrder($kasir, 100000);

    $payment = RecordPayment::record($order, $this->method, 50000, $kasir->id);

    vrAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/payments/{$payment->id}/refund", $kasir, [
        'payment_method_id' => $this->method->id,
        'amount' => 10000,
    ])
        ->assertStatus(403);
});

it('allows full refund then pickup is still blocked by remaining amount', function () {
    $kasir = vrUser($this->branch, 'kasir');
    $admin = vrUser($this->branch, 'admin');
    $order = vrOrder($kasir, 100000);

    $payment = RecordPayment::record($order, $this->method, 100000, $kasir->id);

    RefundPayment::refund($payment, $this->method, 100000, $admin->id);

    $order->refresh();
    expect($order->paid_amount)->toBe(0);
    expect($order->remaining_amount)->toBe(100000);
});

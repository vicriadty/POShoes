<?php

use App\Domain\CashierShifts\Actions\CloseShift;
use App\Domain\CashierShifts\Actions\OpenShift;
use App\Domain\Payments\Actions\RecordPayment;
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

function shiftUser(Branch $branch, string $role): User
{
    $user = User::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
    $user->assignRole($role);

    return $user;
}

function shiftToken(User $user): string
{
    return $user->createToken('test')->plainTextToken;
}

function shiftAuthed(TestCase $test, string $method, string $uri, User $user, array $data = []): TestResponse
{
    $headers = ['Authorization' => 'Bearer '.shiftToken($user)];
    if (strtoupper($method) === 'GET') {
        return $test->{$method.'Json'}($uri, $headers);
    }

    return $test->{$method.'Json'}($uri, $data, $headers);
}

function shiftOrder(User $kasir, int $basePrice = 50000): ServiceOrder
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

it('opens a cashier shift', function () {
    $kasir = shiftUser($this->branch, 'kasir');

    shiftAuthed($this, 'post', '/api/v1/cashier-shifts', $kasir, [
        'opening_balance' => 200000,
    ])
        ->assertCreated()
        ->assertJsonPath('data.opening_balance', 200000)
        ->assertJsonPath('data.is_open', true);
});

it('prevents opening a second shift while one is active', function () {
    $kasir = shiftUser($this->branch, 'kasir');

    shiftAuthed($this, 'post', '/api/v1/cashier-shifts', $kasir, [
        'opening_balance' => 100000,
    ])->assertCreated();

    shiftAuthed($this, 'post', '/api/v1/cashier-shifts', $kasir, [
        'opening_balance' => 50000,
    ])
        ->assertStatus(409);
});

it('returns current active shift', function () {
    $kasir = shiftUser($this->branch, 'kasir');

    OpenShift::open($kasir->id, $this->branch->id, 100000);

    shiftAuthed($this, 'get', '/api/v1/cashier-shifts/current', $kasir)
        ->assertOk()
        ->assertJsonPath('data.is_open', true)
        ->assertJsonPath('data.opening_balance', 100000);
});

it('closes a shift and computes expected amount and discrepancy', function () {
    $kasir = shiftUser($this->branch, 'kasir');
    $shift = OpenShift::open($kasir->id, $this->branch->id, 200000);

    // Terima pembayaran 50000 di shift ini
    $order = shiftOrder($kasir, 50000);
    RecordPayment::record($order, $this->method, 50000, $kasir->id);

    shiftAuthed($this, 'post', "/api/v1/cashier-shifts/{$shift->id}/close", $kasir, [
        'closed_balance' => 260000, // 200k awal + 50k bayar + 10k selisih
    ])
        ->assertOk()
        ->assertJsonPath('data.is_open', false)
        ->assertJsonPath('data.expected_amount', 250000)
        ->assertJsonPath('data.discrepancy', 10000);
});

it('rejects closing an already-closed shift', function () {
    $kasir = shiftUser($this->branch, 'kasir');
    $shift = OpenShift::open($kasir->id, $this->branch->id, 100000);
    CloseShift::close($shift, 100000);

    shiftAuthed($this, 'post', "/api/v1/cashier-shifts/{$shift->id}/close", $kasir, [
        'closed_balance' => 100000,
    ])
        ->assertStatus(409);
});

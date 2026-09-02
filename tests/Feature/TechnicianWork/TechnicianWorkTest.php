<?php

use App\Domain\ServiceOrders\Actions\CreateServiceOrder;
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
    $this->category = ServiceCategory::factory()->create();
    $this->service = ServiceCatalog::factory()->create([
        'category_id' => $this->category->id,
        'base_price' => 75000,
        'estimated_duration_minutes' => 60,
    ]);
});

function techUser(Branch $branch, string $role): User
{
    $user = User::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
    $user->assignRole($role);

    return $user;
}

function techToken(User $user): string
{
    return $user->createToken('test')->plainTextToken;
}

function techAuthed(TestCase $test, string $method, string $uri, User $user, array $data = []): TestResponse
{
    $headers = ['Authorization' => 'Bearer '.techToken($user)];
    if (strtoupper($method) === 'GET') {
        return $test->{$method.'Json'}($uri, $headers);
    }

    return $test->{$method.'Json'}($uri, $data, $headers);
}

/**
 * Order dengan 1 item, sudah di-approve (siap produksi).
 */
function approvedOrder(TestCase $test, User $admin): array
{
    $service = ServiceCatalog::factory()->create(['base_price' => 75000]);
    $customer = Customer::factory()->create();

    $order = CreateServiceOrder::create(
        customer: $customer,
        branchId: $admin->branch_id,
        receivedBy: $admin->id,
        items: [['service_catalog_id' => $service->id]],
    );

    TransitionOrderStatus::transition($order, OrderStatus::Received, changedBy: $admin->id);
    TransitionOrderStatus::transition($order, OrderStatus::Inspection, changedBy: $admin->id);
    TransitionOrderStatus::transition($order, OrderStatus::Approved, changedBy: $admin->id);

    return [$order, $order->items()->first()];
}

it('lists technician queue scoped to assignee', function () {
    $teknisi = techUser($this->branch, 'teknisi');
    $admin = techUser($this->branch, 'admin');

    [$order, $item] = approvedOrder($this, $admin);
    $item->update(['assigned_to' => $teknisi->id]);

    techAuthed($this, 'get', '/api/v1/work/queue', $teknisi)
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $item->id);
});

it('owner can see full queue regardless of assignee', function () {
    $owner = techUser($this->branch, 'owner');
    $admin = techUser($this->branch, 'admin');

    [$order, $item] = approvedOrder($this, $admin);

    techAuthed($this, 'get', '/api/v1/work/queue', $owner)
        ->assertOk()
        ->assertJsonPath('meta.total', 1);
});

it('teknisi cannot see unassigned items in queue', function () {
    $teknisi = techUser($this->branch, 'teknisi');
    $admin = techUser($this->branch, 'admin');

    [$order, $item] = approvedOrder($this, $admin); // tidak di-assign

    techAuthed($this, 'get', '/api/v1/work/queue', $teknisi)
        ->assertOk()
        ->assertJsonPath('meta.total', 0);
});

it('admin assigns item to technician', function () {
    $admin = techUser($this->branch, 'admin');
    $teknisi = techUser($this->branch, 'teknisi');

    [$order, $item] = approvedOrder($this, $admin);

    techAuthed($this, 'post', "/api/v1/work/items/{$item->id}/assign", $admin, [
        'technician_id' => $teknisi->id,
    ])
        ->assertOk()
        ->assertJsonPath('data.assigned_to', $teknisi->id);
});

it('rejects assignment to a non-technician', function () {
    $admin = techUser($this->branch, 'admin');
    $kasir = techUser($this->branch, 'kasir');

    [$order, $item] = approvedOrder($this, $admin);

    techAuthed($this, 'post', "/api/v1/work/items/{$item->id}/assign", $admin, [
        'technician_id' => $kasir->id,
    ])
        ->assertStatus(409);
});

it('teknisi can start assigned item (pending → in_progress)', function () {
    $admin = techUser($this->branch, 'admin');
    $teknisi = techUser($this->branch, 'teknisi');

    [$order, $item] = approvedOrder($this, $admin);
    $item->update(['assigned_to' => $teknisi->id]);

    techAuthed($this, 'post', "/api/v1/work/items/{$item->id}/status", $teknisi, [
        'status' => 'in_progress',
    ])
        ->assertOk()
        ->assertJsonPath('data.status', 'in_progress');
});

it('teknisi cannot start an item not assigned to them', function () {
    $admin = techUser($this->branch, 'admin');
    $teknisi1 = techUser($this->branch, 'teknisi');
    $teknisi2 = techUser($this->branch, 'teknisi');

    [$order, $item] = approvedOrder($this, $admin);
    $item->update(['assigned_to' => $teknisi1->id]);

    techAuthed($this, 'post', "/api/v1/work/items/{$item->id}/status", $teknisi2, [
        'status' => 'in_progress',
    ])
        ->assertStatus(403);
});

it('cannot start item before it is assigned', function () {
    $admin = techUser($this->branch, 'admin');
    $teknisi = techUser($this->branch, 'teknisi');

    [$order, $item] = approvedOrder($this, $admin); // belum di-assign

    // Teknisi tidak berhak mengubah item yang tidak di-assign padanya.
    techAuthed($this, 'post', "/api/v1/work/items/{$item->id}/status", $teknisi, [
        'status' => 'in_progress',
    ])
        ->assertStatus(403);
});

it('sends item to quality check then completes it', function () {
    $admin = techUser($this->branch, 'admin');
    $teknisi = techUser($this->branch, 'teknisi');

    [$order, $item] = approvedOrder($this, $admin);
    $item->update(['assigned_to' => $teknisi->id]);

    // pending → in_progress
    techAuthed($this, 'post', "/api/v1/work/items/{$item->id}/status", $teknisi, [
        'status' => 'in_progress',
    ])->assertOk();

    // in_progress → quality_check
    techAuthed($this, 'post', "/api/v1/work/items/{$item->id}/status", $teknisi, [
        'status' => 'quality_check',
    ])->assertOk();

    // quality_check → completed
    techAuthed($this, 'post', "/api/v1/work/items/{$item->id}/status", $teknisi, [
        'status' => 'completed',
    ])->assertOk()
        ->assertJsonPath('data.status', 'completed');
});

it('rejects invalid item transition', function () {
    $admin = techUser($this->branch, 'admin');
    $teknisi = techUser($this->branch, 'teknisi');

    [$order, $item] = approvedOrder($this, $admin);
    $item->update(['assigned_to' => $teknisi->id]);

    // pending → completed tidak valid (langsung tanpa proses)
    techAuthed($this, 'post', "/api/v1/work/items/{$item->id}/status", $teknisi, [
        'status' => 'completed',
    ])
        ->assertStatus(409);
});

it('requires reason to cancel an item', function () {
    $admin = techUser($this->branch, 'admin');
    $teknisi = techUser($this->branch, 'teknisi');

    [$order, $item] = approvedOrder($this, $admin);
    $item->update(['assigned_to' => $teknisi->id]);

    techAuthed($this, 'post', "/api/v1/work/items/{$item->id}/status", $teknisi, [
        'status' => 'cancelled',
    ])
        ->assertStatus(422);
});

it('adds a work note to assigned item', function () {
    $admin = techUser($this->branch, 'admin');
    $teknisi = techUser($this->branch, 'teknisi');

    [$order, $item] = approvedOrder($this, $admin);
    $item->update(['assigned_to' => $teknisi->id]);

    techAuthed($this, 'post', "/api/v1/work/items/{$item->id}/notes", $teknisi, [
        'note' => 'Sole terlepas, perlu lem kuat.',
    ])
        ->assertOk()
        ->assertJsonPath('data.notes', 'Sole terlepas, perlu lem kuat.');
});

it('teknisi cannot add note to unassigned item', function () {
    $admin = techUser($this->branch, 'admin');
    $teknisi1 = techUser($this->branch, 'teknisi');
    $teknisi2 = techUser($this->branch, 'teknisi');

    [$order, $item] = approvedOrder($this, $admin);
    $item->update(['assigned_to' => $teknisi1->id]);

    techAuthed($this, 'post', "/api/v1/work/items/{$item->id}/notes", $teknisi2, [
        'note' => 'tidak boleh',
    ])
        ->assertStatus(403);
});

it('syncs order to in_progress when first item starts', function () {
    $admin = techUser($this->branch, 'admin');
    $teknisi = techUser($this->branch, 'teknisi');

    [$order, $item] = approvedOrder($this, $admin);
    $item->update(['assigned_to' => $teknisi->id]);

    techAuthed($this, 'post', "/api/v1/work/items/{$item->id}/status", $teknisi, [
        'status' => 'in_progress',
    ])->assertOk();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::InProgress);
});

it('syncs order to ready_for_pickup when all items completed', function () {
    $admin = techUser($this->branch, 'admin');
    $teknisi = techUser($this->branch, 'teknisi');

    [$order, $item] = approvedOrder($this, $admin);
    $item->update(['assigned_to' => $teknisi->id]);

    foreach (['in_progress', 'quality_check', 'completed'] as $status) {
        techAuthed($this, 'post', "/api/v1/work/items/{$item->id}/status", $teknisi, [
            'status' => $status,
        ])->assertOk();
    }

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::ReadyForPickup);
});

it('handles multi-item order with differing item statuses', function () {
    $admin = techUser($this->branch, 'admin');
    $teknisi = techUser($this->branch, 'teknisi');
    $secondService = ServiceCatalog::factory()->create(['base_price' => 40000]);

    $customer = Customer::factory()->create();
    $order = CreateServiceOrder::create(
        customer: $customer,
        branchId: $admin->branch_id,
        receivedBy: $admin->id,
        items: [
            ['service_catalog_id' => $this->service->id],
            ['service_catalog_id' => $secondService->id],
        ],
    );

    TransitionOrderStatus::transition($order, OrderStatus::Received, changedBy: $admin->id);
    TransitionOrderStatus::transition($order, OrderStatus::Inspection, changedBy: $admin->id);
    TransitionOrderStatus::transition($order, OrderStatus::Approved, changedBy: $admin->id);

    $itemA = $order->items()->get()[0];
    $itemB = $order->items()->get()[1];
    $itemA->update(['assigned_to' => $teknisi->id]);
    $itemB->update(['assigned_to' => $teknisi->id]);

    // A selesai; B masih in_progress.
    foreach (['in_progress', 'quality_check', 'completed'] as $status) {
        techAuthed($this, 'post', "/api/v1/work/items/{$itemA->id}/status", $teknisi, ['status' => $status])->assertOk();
    }
    techAuthed($this, 'post', "/api/v1/work/items/{$itemB->id}/status", $teknisi, ['status' => 'in_progress'])->assertOk();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::InProgress); // masih ada item berjalan

    // B selesai → semua selesai → ready_for_pickup.
    foreach (['quality_check', 'completed'] as $status) {
        techAuthed($this, 'post', "/api/v1/work/items/{$itemB->id}/status", $teknisi, ['status' => $status])->assertOk();
    }

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::ReadyForPickup);
});

it('writes immutable item status history', function () {
    $admin = techUser($this->branch, 'admin');
    $teknisi = techUser($this->branch, 'teknisi');

    [$order, $item] = approvedOrder($this, $admin);
    $item->update(['assigned_to' => $teknisi->id]);

    techAuthed($this, 'post', "/api/v1/work/items/{$item->id}/status", $teknisi, [
        'status' => 'in_progress',
    ])->assertOk();

    $this->assertDatabaseHas('service_order_status_histories', [
        'service_order_item_id' => $item->id,
        'from_status' => 'pending',
        'to_status' => 'in_progress',
    ]);
});

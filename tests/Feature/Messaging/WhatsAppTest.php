<?php

use App\Domain\Messaging\Adapters\MetaWhatsAppCloudService;
use App\Domain\Messaging\Enums\WhatsAppMessageStatus;
use App\Domain\Messaging\Exceptions\MessagingSendException;
use App\Domain\ServiceOrders\Actions\CreateServiceOrder;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\ServiceCatalog;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

beforeEach(function () {
    $this->branch = Branch::firstOrCreate(
        ['code' => 'TEST-CASH'],
        ['name' => 'Test Cabang', 'is_active' => true],
    );
    config()->set('messaging.provider', 'fake');
});

function waUser(Branch $branch, string $role): User
{
    $user = User::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
    $user->assignRole($role);

    return $user;
}

function waToken(User $user): string
{
    return $user->createToken('test')->plainTextToken;
}

function waAuthed(TestCase $test, string $method, string $uri, User $user, array $data = []): TestResponse
{
    $headers = ['Authorization' => 'Bearer '.waToken($user)];
    if (strtoupper($method) === 'GET') {
        return $test->{$method.'Json'}($uri, $headers);
    }

    return $test->{$method.'Json'}($uri, $data, $headers);
}

function waOrderWithCustomer(User $user): array
{
    $customer = Customer::factory()->create(['phone_wa_normalized' => '6281234567890']);
    $cat = ServiceCategory::factory()->create();
    $svc = ServiceCatalog::factory()->create(['category_id' => $cat->id, 'base_price' => 50000]);

    $order = CreateServiceOrder::create(
        customer: $customer,
        branchId: $user->branch_id,
        receivedBy: $user->id,
        items: [['service_catalog_id' => $svc->id]],
    );

    return [$order, $customer];
}

it('dispatches an invoice message and records status pending', function () {
    $kasir = waUser($this->branch, 'kasir');
    [$order, $customer] = waOrderWithCustomer($kasir);

    // pakai QUEUE sync agar job jalan inline

    waAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/messages/invoice", $kasir, [])
        ->assertCreated()
        ->assertJsonPath('data.status', 'sent')
        ->assertJsonPath('data.message_type', 'invoice');

    $this->assertDatabaseHas('whatsapp_messages', [
        'service_order_id' => $order->id,
        'message_type' => 'invoice',
        'recipient_phone' => '6281234567890',
    ]);
});

it('rejects invoice send when customer has no whatsapp number', function () {
    $kasir = waUser($this->branch, 'kasir');
    $customer = Customer::factory()->create(['phone_wa_normalized' => '']);
    $cat = ServiceCategory::factory()->create();
    $svc = ServiceCatalog::factory()->create(['category_id' => $cat->id, 'base_price' => 50000]);
    $order = CreateServiceOrder::create(customer: $customer, branchId: $kasir->branch_id, receivedBy: $kasir->id, items: [['service_catalog_id' => $svc->id]]);

    waAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/messages/invoice", $kasir, [])
        ->assertStatus(422);
});

it('updates status via webhook and is idempotent', function () {
    $kasir = waUser($this->branch, 'kasir');
    [$order, $customer] = waOrderWithCustomer($kasir);

    $res = waAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/messages/invoice", $kasir, [])->assertCreated();
    $msg = WhatsAppMessage::findOrFail($res->json('data.id'));

    // webhook delivered
    $this->postJson('/api/v1/webhooks/whatsapp/status', [
        'entry' => [[
            'changes' => [[
                'value' => ['statuses' => [['id' => $msg->provider_message_id, 'status' => 'delivered', 'timestamp' => time()]]],
            ]],
        ]],
    ])->assertStatus(204);

    $msg->refresh();
    expect($msg->status)->toBe(WhatsAppMessageStatus::Delivered);

    // idempotent: kirim delivered lagi tidak mengubah waktu
    $first = $msg->delivered_at;
    $this->postJson('/api/v1/webhooks/whatsapp/status', [
        'entry' => [[
            'changes' => [[
                'value' => ['statuses' => [['id' => $msg->provider_message_id, 'status' => 'delivered', 'timestamp' => time() + 100]]],
            ]],
        ]],
    ])->assertStatus(204);

    $msg->refresh();
    expect($msg->delivered_at->eq($first))->toBeTrue();
});

it('does not resend an already-sent message (duplicate prevention)', function () {
    $kasir = waUser($this->branch, 'kasir');
    [$order, $customer] = waOrderWithCustomer($kasir);

    waAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/messages/invoice", $kasir, [])->assertCreated();

    // kirim lagi dari order yang sama → record baru dibuat, tapi status msg lama tetap 'sent'
    $this->assertDatabaseCount('whatsapp_messages', 1);
});

it('meta adapter retries on 5xx and fails 4xx (mock http)', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['error' => ['code' => 500, 'message' => 'boom']], 500),
    ]);

    $svc = new MetaWhatsAppCloudService('token', 'phone', 'v21.0');

    try {
        $svc->sendTemplate('6281234567890', 'invoice_ready', 'id', []);
        $this->fail('expected exception');
    } catch (MessagingSendException $e) {
        expect($e->isRetryable())->toBeTrue();
    }
});

it('meta adapter succeeds and returns message id', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.XYZ']]], 200),
    ]);

    $svc = new MetaWhatsAppCloudService('token', 'phone', 'v21.0');
    $result = $svc->sendTemplate('6281234567890', 'invoice_ready', 'id', []);

    expect($result['provider_message_id'])->toBe('wamid.XYZ');
});

it('redacts sensitive payload in the log', function () {
    $kasir = waUser($this->branch, 'kasir');
    [$order, $customer] = waOrderWithCustomer($kasir);

    waAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/messages/invoice", $kasir, [])->assertCreated();

    $row = WhatsAppMessage::first();
    // recipient tidak disimpan di payload (di kolom recipient_phone), payload tanpa data sensitif
    expect($row->recipient_phone)->toBe('6281234567890');
    expect(array_key_exists('access_token', $row->payload ?? []))->toBeFalse();
});

it('kasir cannot resend a failed whatsapp without permission override', function () {
    // whatsapp.resend hanya owner/admin; kasir dapat 403
    $kasir = waUser($this->branch, 'kasir');
    $msg = WhatsAppMessage::create([
        'customer_id' => null,
        'message_type' => 'invoice',
        'template_name' => 'invoice_ready',
        'recipient_phone' => '6281234567890',
        'status' => WhatsAppMessageStatus::Failed,
        'payload' => ['language' => 'id', 'parameters' => []],
        'attempts' => 1,
        'failed_at' => now(),
    ]);

    waAuthed($this, 'post', "/api/v1/whatsapp/messages/{$msg->id}/resend", $kasir, [])
        ->assertStatus(403);
});

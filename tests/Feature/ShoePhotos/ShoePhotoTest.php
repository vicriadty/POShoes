<?php

use App\Domain\ServiceOrders\Actions\CreateServiceOrder;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\ServiceCatalog;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

beforeEach(function () {
    $this->branch = Branch::firstOrCreate(
        ['code' => 'TEST-CASH'],
        ['name' => 'Test Cabang', 'is_active' => true],
    );
});

function photoUser(Branch $branch, string $role): User
{
    $user = User::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
    $user->assignRole($role);

    return $user;
}

function photoToken(User $user): string
{
    return $user->createToken('test')->plainTextToken;
}

function photoAuthed(TestCase $test, string $method, string $uri, User $user, array $data = [], array $files = []): TestResponse
{
    $headers = ['Authorization' => 'Bearer '.photoToken($user)];
    if (strtoupper($method) === 'GET') {
        return $test->{$method.'Json'}($uri, $headers);
    }

    // post() mendukung UploadedFile di dalam $data (extractFilesFromDataArray).
    $merged = $files !== [] ? array_merge($data, $files) : $data;

    return $test->post($uri, $merged, $headers);
}

/**
 * Order approved dengan 1 sepatu.
 */
function photoOrder(TestCase $test, User $admin): array
{
    $cat = ServiceCategory::factory()->create();
    $svc = ServiceCatalog::factory()->create(['category_id' => $cat->id, 'base_price' => 75000]);
    $customer = Customer::factory()->create();

    $order = CreateServiceOrder::create(
        customer: $customer,
        branchId: $admin->branch_id,
        receivedBy: $admin->id,
        items: [['service_catalog_id' => $svc->id]],
        shoes: [['brand' => 'Nike', 'model' => 'Air Max', 'size' => '42']],
    );

    $shoe = $order->shoes()->first();

    return [$order, $shoe];
}

it('uploads a before photo for a shoe', function () {
    $teknisi = photoUser($this->branch, 'teknisi');
    [$order, $shoe] = photoOrder($this, $teknisi);

    $file = UploadedFile::fake()->image('before.jpg', 200, 200);

    photoAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/shoes/{$shoe->id}/photos", $teknisi, [
        'type' => 'before',
    ], ['photo' => $file])
        ->assertCreated()
        ->assertJsonPath('data.type', 'before')
        ->assertJsonStructure(['data' => ['url', 'file_path', 'mime_type', 'file_size']]);

    $this->assertDatabaseCount('shoe_photos', 1);
});

it('rejects non-image upload', function () {
    $teknisi = photoUser($this->branch, 'teknisi');
    [$order, $shoe] = photoOrder($this, $teknisi);

    $file = UploadedFile::fake()->create('notes.txt', 100, 'text/plain');

    photoAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/shoes/{$shoe->id}/photos", $teknisi, [
        'type' => 'before',
    ], ['photo' => $file])
        ->assertStatus(422);
});

it('rejects invalid photo type', function () {
    $teknisi = photoUser($this->branch, 'teknisi');
    [$order, $shoe] = photoOrder($this, $teknisi);

    $file = UploadedFile::fake()->image('x.png', 100, 100);

    photoAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/shoes/{$shoe->id}/photos", $teknisi, [
        'type' => 'panorama',
    ], ['photo' => $file])
        ->assertStatus(422);
});

it('lists photos for an order', function () {
    $teknisi = photoUser($this->branch, 'teknisi');
    [$order, $shoe] = photoOrder($this, $teknisi);

    $file1 = UploadedFile::fake()->image('a.png', 100, 100);
    $file2 = UploadedFile::fake()->image('b.png', 100, 100);

    photoAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/shoes/{$shoe->id}/photos", $teknisi, ['type' => 'before'], ['photo' => $file1])->assertCreated();
    photoAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/shoes/{$shoe->id}/photos", $teknisi, ['type' => 'after'], ['photo' => $file2])->assertCreated();

    photoAuthed($this, 'get', "/api/v1/service-orders/{$order->id}/photos", $teknisi)
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('serves photo file via authenticated endpoint', function () {
    $teknisi = photoUser($this->branch, 'teknisi');
    [$order, $shoe] = photoOrder($this, $teknisi);

    $file = UploadedFile::fake()->image('c.png', 100, 100);
    $resp = photoAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/shoes/{$shoe->id}/photos", $teknisi, ['type' => 'during'], ['photo' => $file])
        ->assertCreated();
    $photoId = $resp->json('data.id');

    $this->withToken(photoToken($teknisi))
        ->get("/api/v1/shoe-photos/{$photoId}/file")
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');
});

it('denies photo upload to kasir', function () {
    $kasir = photoUser($this->branch, 'kasir');
    [$order, $shoe] = photoOrder($this, $kasir);

    $file = UploadedFile::fake()->image('d.png', 100, 100);

    photoAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/shoes/{$shoe->id}/photos", $kasir, ['type' => 'before'], ['photo' => $file])
        ->assertStatus(403);
});

it('rejects photo for shoe not in order', function () {
    $teknisi = photoUser($this->branch, 'teknisi');
    [$order, $shoe] = photoOrder($this, $teknisi);

    $cat = ServiceCategory::factory()->create();
    $svc = ServiceCatalog::factory()->create(['category_id' => $cat->id, 'base_price' => 75000]);
    $otherOrder = CreateServiceOrder::create(
        customer: Customer::factory()->create(),
        branchId: $teknisi->branch_id,
        receivedBy: $teknisi->id,
        items: [['service_catalog_id' => $svc->id]],
        shoes: [['brand' => 'Other']],
    );
    $otherShoe = $otherOrder->shoes()->first();

    $file = UploadedFile::fake()->image('e.png', 100, 100);
    photoAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/shoes/{$otherShoe->id}/photos", $teknisi, ['type' => 'before'], ['photo' => $file])
        ->assertStatus(404);
});

it('stores the uploaded file on the photos disk', function () {
    $teknisi = photoUser($this->branch, 'teknisi');
    [$order, $shoe] = photoOrder($this, $teknisi);

    $file = UploadedFile::fake()->image('f.png', 100, 100);
    $resp = photoAuthed($this, 'post', "/api/v1/service-orders/{$order->id}/shoes/{$shoe->id}/photos", $teknisi, ['type' => 'after'], ['photo' => $file])
        ->assertCreated();

    $path = $resp->json('data.file_path');
    expect(Storage::disk('photos')->exists($path))->toBeTrue();
});

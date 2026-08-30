<?php

namespace App\Http\Controllers\Api\V1\Customers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\StoreCustomerRequest;
use App\Http\Requests\Customers\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customers = Customer::query()
            ->withCount('orders')
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where(function ($q) use ($request) {
                    $search = (string) $request->input('search');
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone_wa_normalized', 'like', "%{$search}%");
                }),
            )
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return ApiResponse::paginated(
            $customers,
            fn ($paginator) => CustomerResource::collection($paginator->items())->resolve(),
        );
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = Customer::create([
            'name' => $request->input('name'),
            'phone_wa' => $request->input('phone_wa'),
            'phone_wa_normalized' => $request->input('phone_wa_normalized'),
            'email' => $request->input('email'),
            'address' => $request->input('address'),
            'notes' => $request->input('notes'),
            'communication_consent_at' => $request->input('communication_consent_at'),
            'created_by' => $request->user()?->id,
        ]);

        return ApiResponse::created(new CustomerResource($customer));
    }

    public function show(Request $request, Customer $customer): JsonResponse
    {
        $customer->loadCount('orders');

        return ApiResponse::ok(new CustomerResource($customer));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $data = $request->only([
            'name', 'phone_wa', 'phone_wa_normalized', 'email',
            'address', 'notes', 'communication_consent_at',
        ]);

        $customer->update($data);

        return ApiResponse::ok(new CustomerResource($customer->refresh()));
    }
}

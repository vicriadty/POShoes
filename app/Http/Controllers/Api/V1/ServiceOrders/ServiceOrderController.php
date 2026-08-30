<?php

namespace App\Http\Controllers\Api\V1\ServiceOrders;

use App\Domain\ServiceOrders\Actions\CreateServiceOrder;
use App\Domain\ServiceOrders\Actions\TransitionOrderStatus;
use App\Domain\ServiceOrders\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceOrders\ChangeOrderStatusRequest;
use App\Http\Requests\ServiceOrders\StoreServiceOrderRequest;
use App\Http\Resources\ServiceOrderResource;
use App\Models\Customer;
use App\Models\ServiceOrder;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = ServiceOrder::query()
            ->with(['customer', 'items', 'shoes'])
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', $request->input('status')),
            )
            ->when(
                $request->filled('customer_id'),
                fn ($q) => $q->where('customer_id', (int) $request->input('customer_id')),
            )
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where('order_number', 'like', '%'.$request->input('search').'%'),
            )
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        return ApiResponse::paginated(
            $orders,
            fn ($paginator) => ServiceOrderResource::collection($paginator->items())->resolve(),
        );
    }

    public function store(StoreServiceOrderRequest $request): JsonResponse
    {
        $customer = Customer::query()->findOrFail((int) $request->input('customer_id'));

        $order = CreateServiceOrder::create(
            customer: $customer,
            branchId: $request->user()->branch_id ?? 1,
            receivedBy: $request->user()->id,
            items: $request->input('items'),
            shoes: $request->input('shoes', []),
            customerNotes: $request->input('customer_notes'),
            internalNotes: $request->input('internal_notes'),
            estimatedCompletedAt: $request->date('estimated_completed_at'),
            discountAmount: (int) $request->input('discount_amount', 0),
            taxAmount: (int) $request->input('tax_amount', 0),
        );

        return ApiResponse::created(new ServiceOrderResource($order));
    }

    public function show(Request $request, ServiceOrder $order): JsonResponse
    {
        $order->load(['customer', 'items', 'items.shoes', 'shoes', 'statusHistories']);

        return ApiResponse::ok(new ServiceOrderResource($order));
    }

    public function changeStatus(ChangeOrderStatusRequest $request, ServiceOrder $order): JsonResponse
    {
        $to = OrderStatus::from($request->input('status'));

        $updated = TransitionOrderStatus::transition(
            $order,
            $to,
            reason: $request->input('reason'),
            changedBy: $request->user()->id,
        );

        $updated->load(['customer', 'items', 'shoes', 'statusHistories']);

        return ApiResponse::ok(new ServiceOrderResource($updated));
    }

    public function pickup(Request $request, ServiceOrder $order): JsonResponse
    {
        $updated = TransitionOrderStatus::transition(
            $order,
            OrderStatus::PickedUp,
            changedBy: $request->user()->id,
        );

        $updated->load(['customer', 'items', 'shoes', 'statusHistories']);

        return ApiResponse::ok(new ServiceOrderResource($updated));
    }
}

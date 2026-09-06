<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Domain\Inventory\Actions\ApplyStockAdjustment;
use App\Domain\Inventory\Actions\RecordStockOpname;
use App\Domain\Inventory\Actions\RecordStockUsage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\ApplyAdjustmentRequest;
use App\Http\Requests\Inventory\RecordOpnameRequest;
use App\Http\Requests\Inventory\RecordUsageRequest;
use App\Http\Requests\Inventory\StoreStockItemRequest;
use App\Http\Resources\Inventory\StockAdjustmentResource;
use App\Http\Resources\Inventory\StockItemResource;
use App\Http\Resources\Inventory\StockMovementResource;
use App\Http\Resources\Inventory\StockUsageResource;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function items(Request $request): JsonResponse
    {
        $branchId = (int) ($request->user()->branch_id ?? 1);
        $branchScope = $request->input('branch_id');

        $items = StockItem::query()
            ->with(['balances' => fn ($q) => $q->where('branch_id', $branchScope ? (int) $branchScope : $branchId)])
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) {
                $search = (string) request('search');
                $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%");
            }))
            ->when($request->boolean('low_stock'), function ($q) use ($branchScope, $branchId) {
                $q->whereHas('balances', function ($q) use ($branchScope, $branchId) {
                    $q->where('branch_id', $branchScope ? (int) $branchScope : $branchId)
                        ->whereColumn('quantity', '<=', 'min_stock');
                });
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return ApiResponse::paginated(
            $items,
            fn ($p) => StockItemResource::collection($p->items())->resolve(),
        );
    }

    public function storeItem(StoreStockItemRequest $request): JsonResponse
    {
        $item = StockItem::create([
            'sku' => $request->input('sku'),
            'name' => $request->input('name'),
            'unit' => $request->input('unit'),
            'min_stock' => (int) $request->input('min_stock', 0),
            'active' => $request->boolean('active', true),
            'notes' => $request->input('notes'),
        ]);

        return ApiResponse::created(new StockItemResource($item));
    }

    public function movements(Request $request, StockItem $item): JsonResponse
    {
        $branchId = (int) ($request->user()->branch_id ?? 1);

        $movements = StockMovement::query()
            ->with('createdBy')
            ->where('stock_item_id', $item->id)
            ->where('branch_id', $branchId)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 25));

        return ApiResponse::paginated(
            $movements,
            fn ($p) => StockMovementResource::collection($p->items())->resolve(),
        );
    }

    public function applyAdjustment(ApplyAdjustmentRequest $request): JsonResponse
    {
        $branchId = (int) ($request->user()->branch_id ?? 1);

        $adjustment = ApplyStockAdjustment::apply(
            $branchId,
            (string) $request->input('type'),
            (string) $request->input('reason'),
            $request->input('items'),
            $request->user()->id,
            $request->input('notes'),
        );

        return ApiResponse::created(new StockAdjustmentResource($adjustment));
    }

    public function recordUsage(RecordUsageRequest $request, ServiceOrder $order): JsonResponse
    {
        $branchId = (int) ($request->user()->branch_id ?? 1);
        $item = null;
        if ($request->filled('service_order_item_id')) {
            $item = ServiceOrderItem::query()->findOrFail((int) $request->input('service_order_item_id'));
            if ((int) $item->service_order_id !== (int) $order->id) {
                abort(404);
            }
        }

        $usages = RecordStockUsage::record(
            $order,
            $branchId,
            $request->input('usages'),
            $item,
            $request->user()->id,
            $request->input('notes'),
        );

        return ApiResponse::created(StockUsageResource::collection($usages));
    }

    public function opname(RecordOpnameRequest $request): JsonResponse
    {
        $branchId = (int) ($request->user()->branch_id ?? 1);

        $adjustment = RecordStockOpname::record(
            $branchId,
            $request->input('counts'),
            $request->user()->id,
            $request->input('notes'),
        );

        return ApiResponse::created(new StockAdjustmentResource($adjustment));
    }
}

<?php

namespace App\Http\Controllers\Api\V1\TechnicianWork;

use App\Domain\ServiceOrders\Actions\AddItemNote;
use App\Domain\ServiceOrders\Actions\AssignItemToTechnician;
use App\Domain\ServiceOrders\Actions\TransitionItemStatus;
use App\Domain\ServiceOrders\Enums\ItemStatus;
use App\Exceptions\DomainConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TechnicianWork\AddItemNoteRequest;
use App\Http\Requests\TechnicianWork\AssignItemRequest;
use App\Http\Requests\TechnicianWork\ChangeItemStatusRequest;
use App\Http\Resources\WorkItemResource;
use App\Models\ServiceOrderItem;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TechnicianWorkController extends Controller
{
    /**
     * Work queue teknisi: item yang di-assign ke user login (teknisi) atau
     * seluruh antrean (admin/owner dengan permission work.view).
     */
    public function queue(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = ServiceOrderItem::query()
            ->with(['serviceOrder.customer', 'serviceCatalog'])
            ->when(
                $user->hasRole('teknisi'),
                fn ($q) => $q->where('assigned_to', $user->id),
            )
            ->whereNotIn('status', [ItemStatus::Completed->value, ItemStatus::Cancelled->value])
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', $request->input('status')),
            )
            ->orderBy('id')
            ->paginate($request->integer('per_page', 15));

        return ApiResponse::paginated(
            $items,
            fn ($paginator) => WorkItemResource::collection($paginator->items())->resolve(),
        );
    }

    public function assign(AssignItemRequest $request, ServiceOrderItem $item): JsonResponse
    {
        $technician = User::query()->findOrFail((int) $request->input('technician_id'));

        try {
            $updated = AssignItemToTechnician::assign(
                $item,
                $technician,
                assignedBy: $request->user()->id,
            );
        } catch (DomainConflictException $e) {
            return ApiResponse::ok(['message' => $e->getMessage()], 409);
        }

        $updated->load(['serviceOrder.customer', 'serviceCatalog']);

        return ApiResponse::ok(new WorkItemResource($updated));
    }

    public function changeStatus(ChangeItemStatusRequest $request, ServiceOrderItem $item): JsonResponse
    {
        $this->authorizeItemAction($request->user(), $item);

        try {
            $updated = TransitionItemStatus::transition(
                $item,
                ItemStatus::from($request->input('status')),
                reason: $request->input('reason'),
                changedBy: $request->user()->id,
            );
        } catch (DomainConflictException $e) {
            return ApiResponse::ok(['message' => $e->getMessage()], 409);
        }

        $updated->load(['serviceOrder.customer', 'serviceCatalog']);

        return ApiResponse::ok(new WorkItemResource($updated));
    }

    public function addNote(AddItemNoteRequest $request, ServiceOrderItem $item): JsonResponse
    {
        $this->authorizeItemAction($request->user(), $item);

        $updated = AddItemNote::add(
            $item,
            (string) $request->input('note'),
            append: $request->boolean('append'),
        );

        $updated->load(['serviceOrder.customer', 'serviceCatalog']);

        return ApiResponse::ok(new WorkItemResource($updated));
    }

    /**
     * Guard assignee: teknisi hanya boleh mengubah item yang di-assign padanya.
     * Admin/owner (work.*) bebas untuk seluruh item.
     */
    private function authorizeItemAction(User $user, ServiceOrderItem $item): void
    {
        if (! $user->hasRole('teknisi')) {
            return;
        }

        if ((int) $item->assigned_to !== (int) $user->id) {
            throw new AuthorizationException('Item ini tidak di-assign kepada Anda.');
        }
    }
}

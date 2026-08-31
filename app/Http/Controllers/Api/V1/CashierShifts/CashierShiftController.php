<?php

namespace App\Http\Controllers\Api\V1\CashierShifts;

use App\Domain\CashierShifts\Actions\CloseShift;
use App\Domain\CashierShifts\Actions\OpenShift;
use App\Http\Controllers\Controller;
use App\Http\Requests\CashierShifts\CloseShiftRequest;
use App\Http\Requests\CashierShifts\StoreShiftRequest;
use App\Http\Resources\CashierShiftResource;
use App\Models\CashierShift;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashierShiftController extends Controller
{
    public function current(Request $request): JsonResponse
    {
        $shift = CashierShift::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('closed_at')
            ->latest('opened_at')
            ->first();

        return ApiResponse::ok($shift ? new CashierShiftResource($shift) : null);
    }

    public function store(StoreShiftRequest $request): JsonResponse
    {
        $shift = OpenShift::open(
            userId: $request->user()->id,
            branchId: $request->user()->branch_id ?? 1,
            openingBalance: (int) $request->input('opening_balance', 0),
            notes: $request->input('notes'),
        );

        return ApiResponse::created(new CashierShiftResource($shift));
    }

    public function close(CloseShiftRequest $request, CashierShift $shift): JsonResponse
    {
        $shift = CloseShift::close(
            $shift,
            closedBalance: (int) $request->input('closed_balance'),
            notes: $request->input('notes'),
        );

        return ApiResponse::ok(new CashierShiftResource($shift));
    }
}

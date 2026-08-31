<?php

namespace App\Http\Controllers\Api\V1\Services;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $methods = PaymentMethod::query()
            ->when(
                $request->boolean('active_only'),
                fn ($q) => $q->where('active', true),
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return ApiResponse::ok(PaymentMethodResource::collection($methods));
    }
}

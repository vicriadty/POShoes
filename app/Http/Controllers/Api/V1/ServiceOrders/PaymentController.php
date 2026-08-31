<?php

namespace App\Http\Controllers\Api\V1\ServiceOrders;

use App\Domain\Payments\Actions\RecordPayment;
use App\Domain\Payments\Actions\RefundPayment;
use App\Domain\Payments\Actions\VoidPayment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\RefundPaymentRequest;
use App\Http\Requests\Payments\StorePaymentRequest;
use App\Http\Requests\Payments\VoidPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\ServiceOrder;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request, ServiceOrder $order): JsonResponse
    {
        $payments = $order->payments()
            ->with('method')
            ->orderByDesc('id')
            ->get();

        return ApiResponse::ok(PaymentResource::collection($payments));
    }

    public function store(StorePaymentRequest $request, ServiceOrder $order): JsonResponse
    {
        $method = PaymentMethod::query()->findOrFail((int) $request->input('payment_method_id'));

        $payment = RecordPayment::record(
            order: $order,
            method: $method,
            amount: (int) $request->input('amount'),
            receivedBy: $request->user()->id,
            reference: $request->input('reference'),
            receivedAt: $request->date('received_at'),
        );

        $payment->load('method');

        return ApiResponse::created(new PaymentResource($payment));
    }

    public function void(VoidPaymentRequest $request, ServiceOrder $order, Payment $payment): JsonResponse
    {
        if ($payment->service_order_id !== $order->id) {
            abort(404);
        }

        $voided = VoidPayment::void(
            $payment,
            voidedBy: $request->user()->id,
            reason: $request->input('reason'),
        );

        $voided->load('method');

        return ApiResponse::ok(new PaymentResource($voided));
    }

    public function refund(RefundPaymentRequest $request, ServiceOrder $order, Payment $payment): JsonResponse
    {
        if ($payment->service_order_id !== $order->id) {
            abort(404);
        }

        $method = PaymentMethod::query()->findOrFail((int) $request->input('payment_method_id'));

        $refund = RefundPayment::refund(
            sourcePayment: $payment,
            method: $method,
            amount: (int) $request->input('amount'),
            refundedBy: $request->user()->id,
            reference: $request->input('reference'),
        );

        $refund->load('method');

        return ApiResponse::created(new PaymentResource($refund));
    }
}

<?php

namespace App\Http\Controllers\Api\V1\ServiceOrders;

use App\Domain\Invoices\Actions\GenerateInvoice;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\ServiceOrder;
use App\Support\ApiResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    public function show(Request $request, ServiceOrder $order): JsonResponse
    {
        $invoice = GenerateInvoice::for($order);

        return ApiResponse::ok(new InvoiceResource($invoice));
    }

    public function pdf(Request $request, ServiceOrder $order): Response
    {
        $invoice = GenerateInvoice::for($order);

        $order->load(['customer', 'items', 'payments.method', 'branch']);

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'order' => $order,
        ]);

        return $pdf->download('invoice-'.$invoice->invoice_number.'.pdf');
    }

    public function markSent(Request $request, ServiceOrder $order): JsonResponse
    {
        $invoice = GenerateInvoice::for($order);

        if ($invoice->sent_at === null) {
            $invoice->sent_at = now();
            $invoice->save();
        }

        return ApiResponse::ok(new InvoiceResource($invoice));
    }
}

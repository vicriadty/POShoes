<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 24px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #0f766e; padding-bottom: 12px; margin-bottom: 20px; }
        .brand { font-size: 18px; font-weight: bold; color: #0f766e; }
        .brand small { display: block; font-size: 10px; font-weight: normal; color: #64748b; }
        .doc-title { text-align: right; }
        .doc-title h1 { font-size: 16px; margin: 0 0 4px; color: #334155; }
        .doc-title p { margin: 0; font-size: 10px; color: #64748b; }
        .grid { display: table; width: 100%; margin-bottom: 18px; }
        .grid .col { display: table-cell; vertical-align: top; }
        .col-right { text-align: right; }
        h3 { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin: 0 0 6px; }
        .info-line { margin: 2px 0; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        table.items th, table.items td { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; }
        table.items th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; color: #475569; }
        table.items td { font-size: 11px; }
        table.items td.num, table.items th.num { text-align: right; }
        table.totals { width: 100%; border-collapse: collapse; }
        table.totals td { padding: 4px 8px; font-size: 11px; }
        table.totals td.lbl { text-align: right; color: #64748b; }
        table.totals tr.grand td { font-size: 13px; font-weight: bold; border-top: 2px solid #0f766e; color: #0f766e; }
        table.payments { width: 100%; border-collapse: collapse; margin-top: 18px; }
        table.payments th, table.payments td { border: 1px solid #e2e8f0; padding: 5px 8px; font-size: 10px; text-align: left; }
        table.payments th { background: #f1f5f9; }
        .status-paid { color: #059669; font-weight: bold; }
        .status-open { color: #d97706; font-weight: bold; }
        .footer { margin-top: 28px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            POShoes
            <small>Shoe Care Service</small>
        </div>
        <div class="doc-title">
            <h1>INVOICE</h1>
            <p>{{ $invoice->invoice_number }}</p>
            <p>Diterbitkan: {{ $invoice->issued_at?->format('d M Y H:i') ?? '—' }}</p>
        </div>
    </div>

    <div class="grid">
        <div class="col">
            <h3>Pelanggan</h3>
            <p class="info-line"><strong>{{ $order->customer?->name ?? '—' }}</strong></p>
            @if($order->customer?->phone_wa)
                <p class="info-line">{{ $order->customer->phone_wa }}</p>
            @endif
            @if($order->customer?->address)
                <p class="info-line">{{ $order->customer->address }}</p>
            @endif
        </div>
        <div class="col col-right">
            <h3>Order</h3>
            <p class="info-line">{{ $order->order_number }}</p>
            <p class="info-line">Status: {{ $order->status->value }}</p>
        </div>
    </div>

    <h3>Layanan</h3>
    <table class="items">
        <thead>
            <tr>
                <th>Layanan</th>
                <th class="num">Qty</th>
                <th class="num">Harga</th>
                <th class="num">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->service_name }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="lbl">Subtotal</td>
            <td class="num">{{ number_format($order->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if($order->discount_amount > 0)
            <tr>
                <td class="lbl">Diskon</td>
                <td class="num">-{{ number_format($order->discount_amount, 0, ',', '.') }}</td>
            </tr>
        @endif
        @if($order->tax_amount > 0)
            <tr>
                <td class="lbl">Pajak</td>
                <td class="num">{{ number_format($order->tax_amount, 0, ',', '.') }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td class="lbl">Total</td>
            <td class="num">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="lbl">Sudah dibayar</td>
            <td class="num">Rp {{ number_format($order->paid_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="lbl">Sisa</td>
            <td class="num {{ $order->remaining_amount > 0 ? 'status-open' : 'status-paid' }}">
                Rp {{ number_format($order->remaining_amount, 0, ',', '.') }}
            </td>
        </tr>
    </table>

    @if($order->payments->isNotEmpty())
        <h3>Riwayat Pembayaran</h3>
        <table class="payments">
            <thead>
                <tr>
                    <th>Nomor</th>
                    <th>Metode</th>
                    <th>Tanggal</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_number }}</td>
                        <td>{{ $payment->method?->name ?? '—' }}</td>
                        <td>{{ $payment->received_at?->format('d M Y H:i') }}</td>
                        <td>{{ number_format($payment->amount, 0, ',', '.') }}</td>
                        <td>{{ $payment->isVoided() ? 'Void' : ($payment->amount < 0 ? 'Refund' : 'OK') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Terima kasih atas kepercayaan Anda pada POShoes.
    </div>
</body>
</html>

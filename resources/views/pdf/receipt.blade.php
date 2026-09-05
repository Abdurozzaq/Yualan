<!DOCTYPE html>
<html>
<head>
    <title>Resi Penjualan #{{ $sale->invoice_number }}</title>
    <style>
        @page {
            size: A4;
            margin: 32mm 16mm 24mm 16mm;
        }
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background: #fff;
            color: #111;
            font-size: 15px;
            letter-spacing: 0.01em;
            margin-top: 0.5cm;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 0;
            box-shadow: none;
            padding: 3cm 32px 24px 32px;
            min-height: 1000px;
            position: relative;
        }
        .header-bar {
            width: 100%;
            background: #111;
            color: #fff;
            padding: 18px 32px 12px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
        }
        .brand {
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
        }
        .invoice-title {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 2px;
            border-left: 3px solid #fff;
            padding-left: 18px;
        }
        h1, h2, h3, h4, h5, h6 {
            margin: 0 0 0.5em 0;
            color: #111;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }
        .font-semibold { font-weight: 600; }
        .font-medium { font-weight: 500; }
        .font-extrabold { font-weight: 800; }
        .text-heading-lg { font-size: 1.5rem; font-weight: 800; color: #111; letter-spacing: 1.2px; }
        .text-heading-md { font-size: 1.1rem; font-weight: 700; color: #111; }
        .text-heading-sm { font-size: 1rem; font-weight: 700; color: #111; }
        .text-body { font-size: 15px; color: #111; font-weight: 500; }
        .text-muted { font-size: 13px; color: #555; }
        .mb-1 { margin-bottom: 8px; }
        .mb-2 { margin-bottom: 16px; }
        .mb-3 { margin-bottom: 24px; }
        .mb-4 { margin-bottom: 32px; }
        .mb-6 { margin-bottom: 48px; }
        .mt-6 { margin-top: 48px; }
        .flex-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }
        .flex-item { flex-grow: 1; }
        .flex-item-right { text-align: right; min-width: 120px; }
        .section-divider {
            border-bottom: 1.5px solid #222;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        /* Table Styles */
        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 32px;
            background: #fff;
        }
        .order-table th, .order-table td {
            padding: 10px 8px;
            text-align: left;
            border: 1.2px solid #222;
        }
        .order-table th {
            background: #222;
            color: #fff;
            font-weight: 700;
            font-size: 15px;
            letter-spacing: 0.03em;
        }
        .order-table td {
            font-size: 15px;
            color: #111;
            font-weight: 500;
        }
        .order-table .item-name {
            font-weight: 600;
        }
        .order-table .item-unit {
            color: #555;
            font-size: 13px;
        }
        .summary-table {
            width: 100%;
            margin-top: 8px;
            margin-bottom: 24px;
        }
        .summary-table td {
            padding: 8px 0;
            font-size: 15px;
        }
        .summary-table .label { color: #111; font-weight: 600; }
        .summary-table .value { text-align: right; font-weight: 700; }
        .summary-table .total-row td {
            background: #222;
            color: #fff;
            font-size: 1.1rem;
            font-weight: 800;
            padding-top: 10px;
            padding-bottom: 10px;
        }
        .payment-table {
            width: 100%;
            margin-bottom: 16px;
        }
        .payment-table td {
            padding: 8px 0;
            font-size: 15px;
        }
        .payment-table .label { color: #111; font-weight: 600; }
        .payment-table .value { text-align: right; font-weight: 700; }
        .payment-table .change-row td {
            color: #111;
            font-weight: 800;
        }
        .footer-text {
            margin-top: 40px;
            padding-top: 18px;
            border-top: 1.5px solid #222;
            font-size: 15px;
            color: #111;
            font-weight: 600;
        }
        /* Add spacing to top of each page except first */
        .page-break + .container, .container + .container {
            margin-top: 32mm !important;
        }
        /* Responsive for A4 print and screen */
        @media (max-width: 900px) {
            .container { max-width: 100vw; padding: 18px 4vw; }
        }
        @media (max-width: 600px) {
            .container { padding: 8px 2vw; border-radius: 0; }
            .order-table th, .order-table td, .summary-table td, .payment-table td { font-size: 13px; }
            .text-heading-lg { font-size: 1.1rem; }
        }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
<div class="container">
    <div class="header-bar" sytle="margin-top:-0.5cm;">
        <div class="brand">{{ $tenantName ?? 'Nama Toko' }}</div>
        <div class="invoice-title">RECEIPT</div>
    </div>

    <div class="flex-container section-divider">
        <div style="flex:2;">
            <div class="text-heading-sm mb-1">Tanggal: <span class="text-body">{{ $formattedDate }}</span></div>
            <div class="text-heading-sm mb-1">Kasir: <span class="text-body">{{ $sale->user->name ?? '-' }}</span></div>
            @if($sale->customer)
                <div class="text-heading-sm mb-1">Pelanggan: <span class="text-body">{{ $sale->customer->name }}</span></div>
            @endif
            @if($sale->notes)
                <div class="text-heading-sm mb-1">Catatan: <span class="text-body">{{ $sale->notes }}</span></div>
            @endif
        </div>
        <div style="flex:1;">
            <table style="width:100%; border:none; font-size:14px;">
                <tr><td class="text-heading-sm">Invoice:</td><td class="text-right">{{ $sale->invoice_number }}</td></tr>
                <tr><td class="text-heading-sm">Status:</td><td class="text-right">{{ ucfirst($sale->status) }}</td></tr>
                <tr><td class="text-heading-sm">Metode:</td><td class="text-right">{{ strtoupper($sale->payment_method) }}</td></tr>
            </table>
        </div>
    </div>

    <table class="order-table">
        <thead>
            <tr>
                <th style="width:5%">No</th>
                <th style="width:45%">Produk</th>
                <th style="width:15%">Harga</th>
                <th style="width:10%">Qty</th>
                <th style="width:15%">Subtotal</th>
            </tr>
        </thead>
        <tbody>
        @foreach($sale->saleItems as $i => $item)
            <tr>
                <td>{{ $i+1 }}</td>
                <td class="item-name">{{ $item->product->name }} <span class="item-unit">({{ $item->product->unit ?? 'pcs' }})</span></td>
                <td class="text-right">{{ number_format($item->price, 0, ',', '.') }}</td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="summary-table" cellspacing="0" cellpadding="0">
        <tr>
            <td style="padding: 10px;" class="label">Subtotal</td>
            <td style="padding: 10px;" class="value text-right">{{ number_format($sale->subtotal_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;" class="label">Diskon</td>
            <td style="padding: 10px;" class="value text-right">- {{ number_format($sale->discount_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;" class="label">Pajak</td>
            <td style="padding: 10px;" class="value text-right">+ {{ number_format($sale->tax_amount, 0, ',', '.') }}</td>
        </tr>
        <tr class="total-row">
            <td style="padding: 10px;" class="label">Total</td>
            <td style="padding: 10px;" class="value text-right">{{ number_format($sale->total_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;" class="label">Dibayar</td>
            <td style="padding: 10px;" class="value text-right">{{ number_format($sale->paid_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;" class="label">Kembalian</td>
            <td style="padding: 10px;" class="value text-right">{{ number_format($sale->change_amount, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="footer-text">
        <div>Terima kasih telah berbelanja di {{ $tenantName ?? 'Toko Kami' }}.</div>
        <div style="margin-top:12px; font-size:13px; font-weight:400;">Receipt ini sah tanpa tanda tangan. Simpan struk ini sebagai bukti pembayaran yang sah.</div>
    </div>
</div>
</body>
</html>

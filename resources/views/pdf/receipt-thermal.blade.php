<!DOCTYPE html>
<html>
<head>
    <title>Resi Penjualan #{{ $sale->invoice_number }}</title>
    <style>
        /* Thermal Printer 57mm Styles - Centered Content */
        html, body {
            width: 57mm;
            max-width: 57mm;
            min-width: 57mm;
            margin: 0 auto;
            padding: 0;
            font-family: 'Courier New', Courier, monospace;
            font-size: 6px;
            color: #000;
            background: #fff;
            height: auto;
            page-break-after: avoid;
            page-break-inside: avoid;
        }
        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            min-width: 57mm;
            min-height: 0;
            height: auto;
            page-break-after: avoid;
            page-break-inside: avoid;
        }
        .container {
            width: 100%;
            max-width: 53mm;
            margin: 0 auto;
            padding: 0 2mm;
            box-sizing: border-box;
            page-break-after: avoid;
            page-break-inside: avoid;
        }
    .text-center { text-align: center; width: 100%; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
    .font-small { font-size: 5.5px; }
        .divider {
            border-top: 1px dashed #000;
            margin: 4px 0;
        }
        .item-header, .item-row {
            display: flex;
            flex-direction: row;
            width: 100%;
        }
        .item-header {
            font-weight: bold;
            border-bottom: 1px solid #000;
            margin-bottom: 2px;
        }
        .item-row {
            border-bottom: 1px dotted #aaa;
        }
        .item-name {
            flex: 2 1 0;
            word-break: break-all;
        }
        .item-qty {
            flex: 1 1 0;
            text-align: right;
        }
        .item-price {
            flex: 1 1 0;
            text-align: right;
        }
        .item-subtotal {
            flex: 1 1 0;
            text-align: right;
        }
        .summary-row {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
        }
        .footer-text {
            text-align: center;
            margin-top: 8px;
            font-size: 5.5px;
        }
        .status-badge {
            font-size: 5.5px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-completed { color: #155724; }
        .status-pending { color: #856404; }
        .status-cancelled, .status-failed { color: #721c24; }
    </style>
</head>
<body>
<div class="container">
    <div class="text-center">
        <br><br>
    <div class="font-bold" style="font-size:10px;">{{ $tenantName }}</div>
        @if(!empty($tenantAddress))
            <div class="font-small">{{ $tenantAddress }}</div>
        @endif
        @if(!empty($tenantPhone))
            <div class="font-small">Telp: {{ $tenantPhone }}</div>
        @endif
        @if(!empty($tenantNpwp))
            <div class="font-small">NPWP: {{ $tenantNpwp }}</div>
        @endif
        <div>Terima kasih atas pesanan Anda!</div>
    </div>
    <div class="divider"></div>
    <div>
        <div>INVOICE #{{ $sale->invoice_number }}</div>
        <div>Tanggal: {{ $formattedDate }}</div>
        <div>Kasir: {{ $sale->user->name }}</div>
        @if($sale->customer)
            <div>Pelanggan: {{ $sale->customer->name }}</div>
        @endif
        <div>
            <span class="status-badge @if($sale->status === 'completed') status-completed @elseif($sale->status === 'pending') status-pending @else status-cancelled @endif">
                {{ strtoupper($sale->status) }}
            </span>
        </div>
        @if(!empty($sale->payment_reference))
            <div class="font-small">Ref: {{ $sale->payment_reference }}</div>
        @endif
    </div>
    <div class="divider"></div>
    <div class="item-header" style="font-family: 'Courier New', Courier, monospace;">
    <table style="width:100%;font-size:6px;border-collapse:collapse;">
            <tr style="border-bottom:1px solid #000;">
                <th style="text-align:left;width:40%;">Produk</th>
                <th style="text-align:center;width:10%;">Qty</th>
                <th style="text-align:right;width:25%;">Harga</th>
                <th style="text-align:right;width:25%;">Subtotal</th>
            </tr>
            <tbody>
            @php $totalItems = 0; @endphp
            @foreach($sale->saleItems as $item)
                @php $totalItems += $item->quantity; @endphp
                <tr style="border-bottom:1px dotted #aaa;vertical-align:top;">
                    <td style="word-break:break-all;">
                        {{ Str::limit($item->product->name . ($item->product->unit ? ' ('.$item->product->unit.')' : ''), 24) }}
                        @if(!empty($item->product->sku) || !empty($item->product->barcode))<br>
                            <span class="font-small">
                                @if(!empty($item->product->sku))SKU: {{ $item->product->sku }}@endif
                                @if(!empty($item->product->sku) && !empty($item->product->barcode)) | @endif
                                @if(!empty($item->product->barcode))Barcode: {{ $item->product->barcode }}@endif
                            </span>
                        @endif
                    </td>
                    <td style="text-align:center;vertical-align:top;">{{ $item->quantity }}</td>
                    <td style="text-align:right;vertical-align:top;font-family:'Courier New', Courier, monospace;">{{ number_format($item->price, 0, ',', '.') }}</td>
                    <td style="text-align:right;vertical-align:top;font-family:'Courier New', Courier, monospace;">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="divider"></div>
    <table style="width:100%;font-size:6px;font-family:'Courier New', Courier, monospace;">
        <tr><td style="width:60%;">Subtotal</td><td style="text-align:right;width:40%;">{{ number_format($sale->subtotal_amount, 0, ',', '.') }}</td></tr>
        <tr><td>Diskon</td><td style="text-align:right;">-{{ number_format($sale->discount_amount, 0, ',', '.') }}</td></tr>
        <tr><td>PPN</td><td style="text-align:right;">+{{ number_format($sale->tax_amount, 0, ',', '.') }}</td></tr>
        <tr><td colspan="2"><hr style="border:0;border-top:1px solid #000;margin:2px 0;"></td></tr>
        <tr style="font-weight:bold;"><td>TOTAL</td><td style="text-align:right;">{{ number_format($sale->total_amount, 0, ',', '.') }}</td></tr>
        <tr><td colspan="2"><hr style="border:0;border-top:1px solid #000;margin:2px 0;"></td></tr>
        <tr><td>Metode</td><td style="text-align:right;">{{ strtoupper($sale->payment_method) }}</td></tr>
        <tr><td>Dibayar</td><td style="text-align:right;">{{ number_format($sale->paid_amount, 0, ',', '.') }}</td></tr>
        <tr><td>Kembalian</td><td style="text-align:right;">{{ number_format($sale->change_amount, 0, ',', '.') }}</td></tr>
        <tr><td>Jumlah Item</td><td style="text-align:right;">{{ $totalItems }}</td></tr>
    </table>
    @if($sale->notes)
        <div class="divider"></div>
        <div class="font-small">Catatan: {{ $sale->notes }}</div>
    @endif
    <div class="divider"></div>
    <div class="footer-text">
        Terima kasih telah berbelanja di {{ $tenantName }}!<br>
        @if(!empty($tenantReturnPolicy))
            <span>Kebijakan retur: {{ $tenantReturnPolicy }}</span><br>
        @endif
        @if(!empty($tenantCsPhone))
            <span>CS: {{ $tenantCsPhone }}</span><br>
        @endif
        @if(!empty($tenantPromoText))
            <span>{{ $tenantPromoText }}</span><br>
        @endif
        <span>Struk ini adalah bukti pembayaran yang sah.</span>
    </div>
    @if(!empty($sale->invoice_qr_url))
        <div class="text-center">
            <img src="{{ $sale->invoice_qr_url }}" alt="QR Invoice" style="width:60px;height:60px;margin-top:4px;" />
        </div>
    @endif
</div>
</body>
</html>

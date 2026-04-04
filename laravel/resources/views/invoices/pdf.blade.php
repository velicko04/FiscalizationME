<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Faktura {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1a1a2e;
            background: white;
            padding: 32px;
        }

        /* HEADER */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 32px;
            border-bottom: 3px solid #6366f1;
            padding-bottom: 20px;
        }

        .header-left { display: table-cell; width: 50%; vertical-align: top; }
        .header-right { display: table-cell; width: 50%; vertical-align: top; text-align: right; }

        .company-name { font-size: 20px; font-weight: bold; color: #6366f1; margin-bottom: 4px; }
        .company-info { font-size: 10px; color: #6b7280; line-height: 1.6; }

        .invoice-label { font-size: 28px; font-weight: bold; color: #111827; letter-spacing: -0.5px; }
        .invoice-number { font-size: 13px; color: #6366f1; font-weight: bold; margin-top: 4px; }
        .invoice-meta { margin-top: 12px; font-size: 10px; color: #6b7280; line-height: 1.8; }
        .invoice-meta strong { color: #374151; }

        /* STATUS BADGE */
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 8px;
        }
        .status-fisk { background: #d1fae5; color: #065f46; }
        .status-nefisk { background: #fee2e2; color: #991b1b; }

        /* PARTIES */
        .parties {
            display: table;
            width: 100%;
            margin-bottom: 28px;
            border-collapse: separate;
            border-spacing: 16px 0;
        }

        .party-box {
            display: table-cell;
            width: 50%;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            vertical-align: top;
        }

        .party-label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #9ca3af;
            margin-bottom: 10px;
        }

        .party-name { font-size: 13px; font-weight: bold; color: #111827; margin-bottom: 6px; }
        .party-info { font-size: 10px; color: #6b7280; line-height: 1.7; }

        /* ITEMS TABLE */
        .items-title {
            font-size: 10px; font-weight: bold; text-transform: uppercase;
            letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 8px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table thead tr {
            background: #6366f1;
            color: white;
        }

        .items-table thead th {
            padding: 10px 12px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table thead th:last-child { text-align: right; }
        .items-table thead th.text-right { text-align: right; }

        .items-table tbody tr { border-bottom: 1px solid #f3f4f6; }
        .items-table tbody tr:nth-child(even) { background: #f9fafb; }

        .items-table tbody td {
            padding: 10px 12px;
            font-size: 11px;
            color: #374151;
            vertical-align: middle;
        }

        .items-table tbody td:last-child { text-align: right; font-weight: bold; }
        .items-table tbody td.text-right { text-align: right; }

        .item-name { font-weight: 600; color: #111827; }
        .item-unit { font-size: 9px; color: #9ca3af; margin-top: 2px; }

        /* TOTALS */
        .totals-wrap { display: table; width: 100%; margin-bottom: 28px; }
        .totals-left { display: table-cell; width: 55%; vertical-align: top; }
        .totals-right { display: table-cell; width: 45%; vertical-align: top; }

        .totals-table { width: 100%; }
        .totals-table tr td {
            padding: 6px 12px;
            font-size: 11px;
        }
        .totals-table tr td:first-child { color: #6b7280; }
        .totals-table tr td:last-child { text-align: right; font-weight: 600; color: #111827; }

        .totals-table .total-row { border-top: 2px solid #6366f1; }
        .totals-table .total-row td {
            padding-top: 10px;
            font-size: 14px;
            font-weight: bold;
            color: #6366f1;
        }

        /* PAYMENT INFO */
        .payment-info {
            background: #f5f3ff;
            border: 1px solid #ddd6fe;
            border-radius: 8px;
            padding: 14px 16px;
        }
        .payment-info-title {
            font-size: 10px; font-weight: bold; text-transform: uppercase;
            letter-spacing: 0.5px; color: #6366f1; margin-bottom: 8px;
        }
        .payment-info-row {
            display: table; width: 100%; margin-bottom: 4px;
        }
        .payment-info-label {
            display: table-cell; font-size: 10px; color: #7c3aed; width: 40%;
        }
        .payment-info-value {
            display: table-cell; font-size: 10px; color: #111827; font-weight: 600;
        }

        /* FISCAL INFO */
        .fiscal-section {
            display: table;
            width: 100%;
            margin-bottom: 28px;
            border-collapse: separate;
            border-spacing: 16px 0;
        }

        .fiscal-box {
            display: table-cell;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px 16px;
            vertical-align: top;
        }

        .fiscal-box-title {
            font-size: 9px; font-weight: bold; text-transform: uppercase;
            letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 6px;
        }

        .fiscal-code {
            font-size: 9px; color: #374151; word-break: break-all; line-height: 1.5;
            font-family: monospace;
        }

        /* FOOTER */
        .footer {
            border-top: 1px solid #e5e7eb;
            padding-top: 16px;
            display: table;
            width: 100%;
        }

        .footer-left { display: table-cell; width: 70%; font-size: 9px; color: #9ca3af; line-height: 1.6; }
        .footer-right { display: table-cell; width: 30%; text-align: right; vertical-align: bottom; }

        .qr-placeholder {
            width: 80px; height: 80px;
            border: 2px dashed #d1d5db;
            border-radius: 6px;
            display: inline-block;
            text-align: center;
            padding-top: 24px;
            font-size: 8px;
            color: #9ca3af;
        }

        .divider { border: none; border-top: 1px solid #f3f4f6; margin: 16px 0; }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <div class="header">
        <div class="header-left">
            <div class="company-name">{{ $invoice->company->name }}</div>
            <div class="company-info">
                PIB: {{ $invoice->company->tax_id_number }}<br>
                {{ $invoice->company->address }}, {{ $invoice->company->city }}<br>
                {{ $invoice->company->country }}<br>
                @if($invoice->company->bank_account_number)
                    Žiro račun: {{ $invoice->company->bank_account_number }}
                @endif
            </div>
        </div>
        <div class="header-right">
            <div class="invoice-label">FAKTURA</div>
            <div class="invoice-number">{{ $invoice->invoice_number }}</div>
            <div class="invoice-meta">
                <strong>Datum izdavanja:</strong> {{ $invoice->issued_at->format('d.m.Y') }}<br>
                <strong>Period:</strong> {{ $invoice->tax_period ?? '—' }}<br>
                <strong>Način plaćanja:</strong> {{ $invoice->payment_method_type->value ?? $invoice->getRawOriginal('payment_method_type') }}<br>
                <strong>Operator:</strong> {{ $invoice->user->name }}
            </div>
            @if($invoice->fic)
                <span class="status-badge status-fisk">Fiskalizovan</span>
            @else
                <span class="status-badge status-nefisk">Nije fiskalizovan</span>
            @endif
        </div>
    </div>

    {{-- PRODAVAC / KUPAC --}}
    <div class="parties">
        <div class="party-box">
            <div class="party-label">Prodavac (Izdavalac)</div>
            <div class="party-name">{{ $invoice->company->name }}</div>
            <div class="party-info">
                PIB: {{ $invoice->company->tax_id_number }}<br>
                {{ $invoice->company->address }}<br>
                {{ $invoice->company->city }}, {{ $invoice->company->country }}<br>
                PDV obveznik: {{ $invoice->company->is_issuer_in_vat ? 'Da' : 'Ne' }}
            </div>
        </div>
        <div class="party-box">
            <div class="party-label">Kupac (Primalac)</div>
            <div class="party-name">{{ $invoice->buyer->name }}</div>
            <div class="party-info">
                {{ $invoice->buyer->tax_id_type->value }}: {{ $invoice->buyer->tax_id_number }}<br>
                {{ $invoice->buyer->address }}<br>
                {{ $invoice->buyer->city }}, {{ $invoice->buyer->country }}
            </div>
        </div>
    </div>

    {{-- STAVKE --}}
    <div class="items-title">Stavke fakture</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:5%">#</th>
                <th style="width:35%">Naziv</th>
                <th class="text-right" style="width:10%">Kol.</th>
                <th style="width:8%">Jed.</th>
                <th class="text-right" style="width:12%">Cijena</th>
                <th class="text-right" style="width:10%">PDV %</th>
                <th class="text-right" style="width:10%">PDV</th>
                <th class="text-right" style="width:10%">Ukupno</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
            @php
                $qty = (float)$item->quantity;
                $price = (float)$item->unit_price;
                $vatRate = (float)($item->vatRate->percentage ?? $item->product->vatRate->percentage ?? 0);
                $base = round($qty * $price, 2);
                $vat = round($base * $vatRate / 100, 2);
                $total = round($base + $vat, 2);
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <div class="item-name">{{ $item->product->name }}</div>
                    <div class="item-unit">{{ $item->product->unit ?? 'kom' }}</div>
                </td>
                <td class="text-right">{{ (float)$qty == (int)$qty ? (int)$qty : $qty }}</td>
                <td>{{ $item->product->unit ?? 'kom' }}</td>
                <td class="text-right">{{ number_format($price, 2, ',', '.') }} €</td>
                <td class="text-right">{{ number_format($vatRate, 0) }}%</td>
                <td class="text-right">{{ number_format($vat, 2, ',', '.') }} €</td>
                <td>{{ number_format($total, 2, ',', '.') }} €</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTALI + PLACANJE --}}
    <div class="totals-wrap">
        <div class="totals-left">
            @if($invoice->company->bank_account_number)
            <div class="payment-info">
                <div class="payment-info-title">Instrukcije za plaćanje</div>
                <div class="payment-info-row">
                    <div class="payment-info-label">Žiro račun:</div>
                    <div class="payment-info-value">{{ $invoice->company->bank_account_number }}</div>
                </div>
                <div class="payment-info-row">
                    <div class="payment-info-label">Primalac:</div>
                    <div class="payment-info-value">{{ $invoice->company->name }}</div>
                </div>
                <div class="payment-info-row">
                    <div class="payment-info-label">Poziv na broj:</div>
                    <div class="payment-info-value">{{ $invoice->invoice_number }}</div>
                </div>
            </div>
            @endif
        </div>
        <div class="totals-right">
            <table class="totals-table">
                <tr>
                    <td>Osnov bez PDV-a:</td>
                    <td>{{ number_format($invoice->total_price_without_vat, 2, ',', '.') }} €</td>
                </tr>
                <tr>
                    <td>PDV:</td>
                    <td>{{ number_format($invoice->total_vat_amount, 2, ',', '.') }} €</td>
                </tr>
                <tr class="total-row">
                    <td>UKUPNO ZA UPLATU:</td>
                    <td>{{ number_format($invoice->total_price_to_pay, 2, ',', '.') }} €</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- FISKALNI PODACI --}}
    @if($invoice->iic || $invoice->fic)
    <div class="fiscal-section">
        @if($invoice->iic)
        <div class="fiscal-box">
            <div class="fiscal-box-title">IKOF (IIC)</div>
            <div class="fiscal-code">{{ $invoice->iic }}</div>
        </div>
        @endif
        @if($invoice->fic)
        <div class="fiscal-box">
            <div class="fiscal-box-title">JIKR (FIC)</div>
            <div class="fiscal-code">{{ $invoice->fic }}</div>
        </div>
        @endif
    </div>
    @endif

    {{-- FOOTER --}}
    <div class="footer">
        <div class="footer-left">
            <strong>{{ $invoice->company->name }}</strong><br>
            ENU Kod: {{ $invoice->company->enu_code }} &nbsp;|&nbsp;
            Softver: {{ $invoice->company->software_code }} &nbsp;|&nbsp;
            Poslovni prostor: {{ $invoice->company->business_unit_code }}<br>
            Faktura generisana: {{ now()->format('d.m.Y H:i') }}<br>
            @if($invoice->note)
                Napomena: {{ $invoice->note }}
            @endif
        </div>
        <div class="footer-right">
            <div class="qr-placeholder">QR<br>kod</div>
        </div>
    </div>

</body>
</html>
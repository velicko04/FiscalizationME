<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background-color: #f9fafb;
        color: #111827;
        padding: 24px;
    }

    .invoice-container {
        max-width: 1400px;
        margin: 0 auto;
    }

    .page-header {
        margin-bottom: 24px;
    }

    .page-header h1 {
        font-size: 28px;
        font-weight: 600;
        color: #111827;
        margin-bottom: 12px;
    }

    .contract-info {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        display: flex;
        gap: 32px;
    }

    .contract-info p {
        font-size: 14px;
        color: #6b7280;
    }

    .contract-info strong {
        color: #111827;
        font-weight: 600;
    }

    .filter-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        gap: 16px;
        flex-wrap: wrap;
    }

    .date-filter {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .date-filter select,
    .date-filter input {
        height: 38px;
        padding: 0 14px;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        font-size: 14px;
        background: white;
        color: #374151;
        transition: all 0.2s;
    }

    .date-filter select:hover,
    .date-filter input:hover {
        border-color: #9ca3af;
    }

    .date-filter select:focus,
    .date-filter input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .date-filter button {
        height: 38px;
        padding: 0 20px;
        border-radius: 8px;
        border: none;
        background: #3b82f6;
        color: white;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .date-filter button:hover {
        background: #2563eb;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
    }

    .back-button a {
        text-decoration: none;
    }

    .back-button button {
        height: 38px;
        padding: 0 20px;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        background: white;
        color: #374151;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .back-button button:hover {
        background: #f9fafb;
        border-color: #9ca3af;
        transform: translateY(-1px);
    }

    .table-wrapper {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead {
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
    }

    thead th {
        padding: 16px 20px;
        text-align: left;
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    tbody tr {
        border-bottom: 1px solid #f3f4f6;
        transition: background-color 0.15s;
    }

    tbody tr:hover {
        background-color: #f9fafb;
    }

    tbody tr:last-child {
        border-bottom: none;
    }

    tbody td {
        padding: 16px 20px;
        font-size: 14px;
        color: #374151;
        vertical-align: top;
    }

    tbody td:first-child {
        font-weight: 500;
        color: #111827;
    }

    .product-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .product-list li {
        padding: 6px 0;
        font-size: 13px;
        color: #6b7280;
        line-height: 1.5;
    }

    .product-list li:first-child {
        padding-top: 0;
    }

    .product-list li:last-child {
        padding-bottom: 0;
    }

    .empty-state {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        padding: 60px 20px;
        text-align: center;
    }

    .empty-state p {
        font-size: 16px;
        color: #6b7280;
    }

    @media (max-width: 768px) {
        body {
            padding: 16px;
        }

        .page-header h1 {
            font-size: 24px;
        }

        .contract-info {
            flex-direction: column;
            gap: 12px;
        }

        .filter-container {
            flex-direction: column;
            align-items: stretch;
        }

        .date-filter {
            flex-direction: column;
            align-items: stretch;
        }

        .date-filter select,
        .date-filter input,
        .date-filter button,
        .back-button button {
            width: 100%;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            min-width: 1000px;
        }
    }
</style>

<div class="invoice-container">
    <div class="page-header">
        <h1>Računi za ugovor {{ $contract->contract_number }}</h1>
    </div>

    <div class="contract-info">
        <p><strong>Kompanija:</strong> {{ $contract->company->name }}</p>
        <p><strong>Kupac:</strong> {{ $contract->buyer->name }}</p>
    </div>

    <div class="filter-container">
        {{-- DATE FILTER --}}
        <form method="GET" action="{{ route('contracts.invoices', $contract->id) }}" class="date-filter">
            <select name="range">
                <option value="">All time</option>
                <option value="7" {{ request('range') == 7 ? 'selected' : '' }}>Last 7 days</option>
                <option value="30" {{ request('range') == 30 ? 'selected' : '' }}>Last 30 days</option>
                <option value="90" {{ request('range') == 90 ? 'selected' : '' }}>Last 90 days</option>
            </select>

            <input type="date" name="from" value="{{ request('from') }}" placeholder="From">
            <input type="date" name="to" value="{{ request('to') }}" placeholder="To">

            <button type="submit">Pretraži</button>
        </form>

        <div class="back-button">
            <a href="{{ route('contracts.index') }}">
                <button>⬅ Nazad na ugovore</button>
            </a>
        </div>
    </div>

    @if($invoices->count())
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Broj računa</th>
                    <th>Kompanija</th>
                    <th>Kupac</th>
                    <th>Prodavac</th>
                    <th>Datum izdavanja</th>
                    <th>Ukupno (sa PDV)</th>
                    <th>Ukupno PDV</th>
                    <th>Metoda plaćanja</th>
                    <th>Proizvodi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ $invoice->company->name }}</td>
                    <td>{{ $invoice->buyer->name }}</td>
                    <td>{{ $invoice->user->name }}</td>
                    <td>{{ $invoice->issued_at }}</td>
                    <td>{{ number_format($invoice->total_price_to_pay, 2) }}</td>
                    <td>{{ number_format($invoice->total_vat_amount, 2) }}</td>
                    <td>{{ $invoice->payment_method_type }}</td>
                    <td>
                        <ul class="product-list">
                            @foreach($invoice->items as $item)
                                <li>
                                    {{ $item->product->name }} - 
                                    {{ $item->quantity }} x 
                                    {{ number_format($item->unit_price, 2) }} 
                                    (PDV: {{ $item->product->vatRate ? $item->product->vatRate->percentage : 0 }}%)
                                </li>
                            @endforeach
                        </ul>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="empty-state">
        <p>Nema računa za ovaj ugovor.</p>
    </div>
    @endif
</div>

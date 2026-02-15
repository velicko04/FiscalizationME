<h1>Računi za ugovor {{ $contract->contract_number }}</h1>

<p><strong>Kompanija:</strong> {{ $contract->company->name }}</p>
<p><strong>Kupac:</strong> {{ $contract->buyer->name }}</p>

<style>
    .filter-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 15px 0;
    }

    .date-filter {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom:10px;
        margin-top:16px;
    }

    .date-filter select,
    .date-filter input,
    .date-filter button {
        height: 26px;
        padding: 0 12px;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        font-size: 14px;
    }

    .date-filter button {
        background: #3b82f6;
        color: white;
        border: none;
        cursor: pointer;
    }

    .date-filter button:hover {
        background: #2563eb;
    }

    .back-button button {
        height: 34px;
        padding: 0 15px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        background: #6b7280;
        color: white;
    }

    .back-button button:hover {
        opacity: 0.9;
    }
</style>


<div class="filter-container">

    {{-- DATE FILTER --}}
    <form method="GET" action="{{ route('contracts.invoices', $contract->id) }}" class="date-filter">

        <select name="range">
            <option value="">All time</option>
            <option value="7" {{ request('range') == 7 ? 'selected' : '' }}>Last 7 days</option>
            <option value="30" {{ request('range') == 30 ? 'selected' : '' }}>Last 30 days</option>
            <option value="90" {{ request('range') == 90 ? 'selected' : '' }}>Last 90 days</option>
        </select>

        <input type="date" name="from" value="{{ request('from') }}">
        <input type="date" name="to" value="{{ request('to') }}">

        <button type="submit">Pretraži</button>
    </form>

    <div class="back-button">
        <a href="{{ route('contracts.index') }}">
            <button>⬅ Nazad na ugovore</button>
        </a>
    </div>

</div>


@if($invoices->count())
<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">
    <thead style="background:#f0f0f0;">
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
                <ul>
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
@else
    <p>Nema računa za ovaj ugovor.</p>
@endif

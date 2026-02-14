<h1>Ugovori</h1>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
    <!-- Filter lijevo -->
    <form method="GET" action="{{ route('contracts.index') }}">
        <select name="status" onchange="this.form.submit()">
            <option value="">All</option>
            <option value="active" {{ $status == 'active' ? 'selected' : '' }}>Active</option>
            <option value="expired" {{ $status == 'expired' ? 'selected' : '' }}>Expired</option>
            <option value="paused" {{ $status == 'paused' ? 'selected' : '' }}>Paused</option>
        </select>
    </form>

    <!-- Dugmad desno -->
    <div>
        <button onclick="window.location='{{ route('contracts.create') }}'">Dodaj novi ugovor</button>
        <button onclick="window.location='{{ route('invoices.index') }}'" style="margin-left: 10px;">Prikaži sve račune</button>
    </div>
</div>



<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">
    <thead style="background-color: #f0f0f0;">
        <tr>
            <th>Broj ugovora</th>
            <th>Kompanija</th>
            <th>Kupac</th>
            <th>Početak</th>
            <th>Kraj</th>
            <th>Status</th>
            <th>Proizvodi</th>
            <th>Ukupno (sa PDV)</th>
            <th>Računi</th>
        </tr>
    </thead>
    <tbody>
        @foreach($contracts as $contract)
        <tr>
            <td>{{ $contract->contract_number }}</td>
            <td>{{ $contract->company->name }}</td>
            <td>{{ $contract->buyer->name }}</td>
            <td>{{ $contract->start_date }}</td>
            <td>{{ $contract->end_date }}</td>
            <td>{{ ucfirst($contract->status) }}</td>
            <td>
                <ul>
                    @foreach($contract->items as $item)
                        <li>{{ $item->product->name }} - {{ $item->quantity }} x {{ number_format($item->unit_price, 2) }} (PDV: {{ $item->product->vatRate->percentage }}%)</li>
                    @endforeach
                </ul>
            </td>
            <td>{{ number_format($contract->total_amount, 2) }}</td>
            <td>
                <a href="{{ route('contracts.invoices', $contract->id) }}">
                    Pogledaj račune
                </a>

            </td>
        </tr>
        @endforeach
    </tbody>
</table>


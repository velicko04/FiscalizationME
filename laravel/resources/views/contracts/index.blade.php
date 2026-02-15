<h1>Ugovori</h1>

<style>
    .filter-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .left-filters {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .tabs {
        display: flex;
        align-items: center;
        gap: 1px;
    }

    .filter-tab {
        padding: 8px 18px;
        border-radius: 4px;
        text-decoration: none;
        background: #e5e7eb;
        color: #333;
        font-weight: 500;
        font-size: 14px;
        display: flex;
        align-items: center;
        height: 12px;
    }

    .filter-tab:hover {
        background: #d1d5db;
    }

    .active-tab {
        background: #3b82f6;
        color: #fff;
    }

    .date-filter {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 14px;
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

    .right-buttons button {
        height: 34px;
        padding: 0 15px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        background: #111827;
        color: white;
    }

    .right-buttons button:last-child {
        margin-left: 10px;
        background: #6b7280;
    }

    .right-buttons button:hover {
        opacity: 0.9;
    }
</style>


<div class="filter-container">

    <div class="left-filters">

        {{-- STATUS TABS --}}
        <div class="tabs">
            @php $currentStatus = request('status'); @endphp

            <a href="{{ route('contracts.index', ['status' => null]) }}"
               class="filter-tab {{ !$currentStatus ? 'active-tab' : '' }}">
                All
            </a>

            <a href="{{ route('contracts.index', ['status' => 'active']) }}"
               class="filter-tab {{ $currentStatus == 'active' ? 'active-tab' : '' }}">
                Active
            </a>

            <a href="{{ route('contracts.index', ['status' => 'expired']) }}"
               class="filter-tab {{ $currentStatus == 'expired' ? 'active-tab' : '' }}">
                Expired
            </a>

            <a href="{{ route('contracts.index', ['status' => 'paused']) }}"
               class="filter-tab {{ $currentStatus == 'paused' ? 'active-tab' : '' }}">
                Paused
            </a>
        </div>

        {{-- DATE FILTER --}}
        <form method="GET" action="{{ route('contracts.index') }}" class="date-filter">
            <input type="hidden" name="status" value="{{ request('status') }}">

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

    </div>

    <div class="right-buttons">
        <button onclick="window.location='{{ route('contracts.create') }}'">
            Dodaj novi ugovor
        </button>

        <button onclick="window.location='{{ route('invoices.index') }}'">
            Prikaži sve račune
        </button>
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
            <th>Akcije</th>
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
                        <li>
                            {{ $item->product->name }} - 
                            {{ $item->quantity }} x 
                            {{ number_format($item->unit_price, 2) }} 
                            (PDV: {{ $item->product->vatRate->percentage }}%)
                        </li>
                    @endforeach
                </ul>
            </td>
            <td>{{ number_format($contract->total_amount, 2) }}</td>
            <td>
                <a href="{{ route('contracts.invoices', $contract->id) }}">
                    Pogledaj račune
                </a>
            </td>
            <td>
                <a href="{{ route('contracts.edit', $contract->id) }}">
                ✏️ Edit
                </a>
            </td>

        </tr>
        @endforeach
    </tbody>
</table>

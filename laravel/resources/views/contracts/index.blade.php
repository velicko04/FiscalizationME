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

    .contracts-container {
        max-width: 1400px;
        margin: 0 auto;
    }

    .page-header {
        margin-bottom: 32px;
    }

    .page-header h1 {
        font-size: 28px;
        font-weight: 600;
        color: #111827;
    }

    .filter-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        gap: 16px;
        flex-wrap: wrap;
    }

    .left-filters {
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }

    .tabs {
        display: flex;
        gap: 4px;
        background: #f3f4f6;
        padding: 4px;
        border-radius: 10px;
    }

    .filter-tab {
        padding: 8px 20px;
        border-radius: 7px;
        text-decoration: none;
        background: transparent;
        color: #6b7280;
        font-weight: 500;
        font-size: 14px;
        transition: all 0.2s;
    }

    .filter-tab:hover {
        color: #374151;
        background: #e5e7eb;
    }

    .active-tab {
        background: white;
        color: #111827;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .date-filter {
        display: flex;
        align-items: center;
        gap: 12px;
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

    .right-buttons {
        display: flex;
        gap: 12px;
    }

    .right-buttons button {
        height: 38px;
        padding: 0 20px;
        border-radius: 8px;
        border: none;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .right-buttons button:first-child {
        background: #3b82f6;
        color: white;
    }

    .right-buttons button:first-child:hover {
        background: #2563eb;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
    }

    .right-buttons button:last-child {
        background: white;
        color: #374151;
        border: 1px solid #d1d5db;
    }

    .right-buttons button:last-child:hover {
        background: #f9fafb;
        border-color: #9ca3af;
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

    .action-link {
        color: #3b82f6;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
    }

    .action-link:hover {
        color: #2563eb;
        text-decoration: underline;
    }

    @media (max-width: 768px) {
        body {
            padding: 16px;
        }

        .page-header h1 {
            font-size: 24px;
        }

        .filter-container {
            flex-direction: column;
            align-items: stretch;
        }

        .left-filters {
            flex-direction: column;
            align-items: stretch;
        }

        .tabs {
            flex-wrap: wrap;
        }

        .date-filter {
            flex-direction: column;
            align-items: stretch;
        }

        .date-filter select,
        .date-filter input,
        .date-filter button {
            width: 100%;
        }

        .right-buttons {
            flex-direction: column;
        }

        .right-buttons button {
            width: 100%;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            min-width: 1200px;
        }
    }
</style>

<div class="contracts-container">
    <div class="page-header">
        <h1>Ugovori</h1>
    </div>

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

                <input type="date" name="from" value="{{ request('from') }}" placeholder="From">
                <input type="date" name="to" value="{{ request('to') }}" placeholder="To">

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

    <div class="table-wrapper">
        <table>
            <thead>
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
                        <ul class="product-list">
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
                        <a href="{{ route('contracts.invoices', $contract->id) }}" class="action-link">
                            Pogledaj račune
                        </a>
                    </td>
                    <td>
                        <a href="{{ route('contracts.edit', $contract->id) }}" class="action-link">
                            ✏️ Edit
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

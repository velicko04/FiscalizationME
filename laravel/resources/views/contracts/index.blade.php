<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ugovori</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .contracts-container {
            max-width: 1600px;
            margin: 0 auto;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            font-size: 42px;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .controls-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 32px;
            margin-bottom: 32px;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .filter-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
        }
        
        .left-filters {
            display: flex;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
            flex: 1;
        }
        
        .tabs {
            display: flex;
            gap: 8px;
            background: #f3f4f6;
            padding: 6px;
            border-radius: 14px;
        }
        
        .filter-tab {
            padding: 10px 24px;
            border-radius: 10px;
            text-decoration: none;
            background: transparent;
            color: #6b7280;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .filter-tab:hover {
            color: #374151;
            background: #e5e7eb;
        }
        
        .active-tab {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102,126,234,0.4);
        }
        
        .date-filter {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .date-filter select,
        .date-filter input {
            height: 44px;
            padding: 0 16px;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            font-size: 14px;
            background: #f9fafb;
            color: #374151;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .date-filter select:hover,
        .date-filter input:hover {
            border-color: #d1d5db;
            background: white;
        }
        
        .date-filter select:focus,
        .date-filter input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102,126,234,0.1);
        }
        
        .date-filter button {
            height: 44px;
            padding: 0 24px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .date-filter button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102,126,234,0.5);
        }
        
        .right-buttons {
            display: flex;
            gap: 12px;
        }
        
        .right-buttons button {
            height: 44px;
            padding: 0 24px;
            border-radius: 12px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            white-space: nowrap;
        }
        
        .right-buttons button:first-child {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(16,185,129,0.3);
        }
        
        .right-buttons button:first-child:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16,185,129,0.4);
        }
        
        .right-buttons button:last-child {
            background: white;
            color: #374151;
            border: 2px solid #e5e7eb;
        }
        
        .right-buttons button:last-child:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            transform: translateY(-2px);
        }
        
        .table-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            animation: slideUp 0.6s ease;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        thead th {
            padding: 20px 24px;
            text-align: left;
            font-size: 13px;
            font-weight: 700;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.3s ease;
        }
        
        tbody tr:hover {
            background: linear-gradient(90deg, rgba(102,126,234,0.05) 0%, rgba(118,75,162,0.05) 100%);
        }
        
        tbody tr:last-child {
            border-bottom: none;
        }
        
        tbody td {
            padding: 20px 24px;
            font-size: 14px;
            color: #374151;
            vertical-align: top;
        }
        
        tbody td:first-child {
            font-weight: 700;
            color: #1f2937;
            font-size: 15px;
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
            line-height: 1.6;
        }
        
        .product-list li:first-child {
            padding-top: 0;
        }
        
        .product-list li:last-child {
            padding-bottom: 0;
        }
        
        .action-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .action-link:hover {
            color: #764ba2;
            transform: translateX(4px);
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-expired {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-paused {
            background: #fef3c7;
            color: #92400e;
        }
        
        @media (max-width: 1024px) {
            body { padding: 20px 16px; }
            .page-header h1 { font-size: 32px; }
            .controls-card { padding: 24px; }
            .filter-container { flex-direction: column; align-items: stretch; }
            .left-filters { flex-direction: column; align-items: stretch; }
            .tabs { flex-wrap: wrap; }
            .date-filter { flex-wrap: wrap; }
            .date-filter select, .date-filter input, .date-filter button { flex: 1; min-width: 120px; }
            .right-buttons { flex-direction: column; }
            .right-buttons button { width: 100%; }
            .table-card { overflow-x: auto; }
            table { min-width: 1200px; }
        }
    </style>
</head>
<body>
    <div class="contracts-container">
        <div class="page-header">
            <h1>Ugovori</h1>
        </div>

        <div class="controls-card">
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

                        <button type="submit">🔍 Pretraži</button>
                    </form>
                </div>

                <div class="right-buttons">
                    <button onclick="window.location='{{ route('contracts.create') }}'">
                        ✨ Dodaj novi ugovor
                    </button>

                    <button onclick="window.location='{{ route('invoices.index') }}'">
                        📄 Prikaži sve račune
                    </button>
                </div>
            </div>
        </div>

        <div class="table-card">
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
                        <td>
                            <span class="status-badge status-{{ $contract->status }}">
                                {{ ucfirst($contract->status) }}
                            </span>
                        </td>
                        <td>
                            <ul class="product-list">
                                @foreach($contract->items as $item)
                                    <li>
                                        {{ $item->product->name }} - 
                                        {{ $item->quantity }} x 
                                        {{ number_format($item->unit_price, 2) }} 
                                        (PDV: {{ $item->product->vatRate->percentage ?? 0 }}%)
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td><strong>{{ number_format($contract->total_amount, 2) }}</strong></td>
                        <td>
                            @if($contract->invoices_count > 0)
                            <a href="{{ route('contracts.invoices', $contract->id) }}" class="action-link">
                            📄 Pogledaj račune ({{ $contract->invoices_count }})
                            </a>
                            @else
                            <span style="color:#9ca3af;">Nema računa</span>
                            @endif
                        </td>

                        <td>
                            @if($contract->invoices_count == 0)
                            <a href="{{ route('contracts.edit', $contract->id) }}" class="action-link">
                                ✏️ Edit
                            </a>
                            @else
                            <span style="color:#9ca3af; cursor:not-allowed;">
                                
                            </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

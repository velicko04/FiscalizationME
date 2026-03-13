<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contracts - FiscalizationME</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f9fafb;
            color: #111827;
        }
        
        .page-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px;
        }
        
        .page-header {
            margin-bottom: 24px;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }
        
        .page-subtitle {
            font-size: 14px;
            color: #6b7280;
        }
        
        .tabs-container {
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 24px;
        }
        
        .tabs {
            display: flex;
            gap: 32px;
        }
        
        .tab {
            padding: 12px 0;
            font-size: 14px;
            font-weight: 500;
            color: #6b7280;
            text-decoration: none;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }
        
        .tab:hover {
            color: #111827;
        }
        
        .tab.active {
            color: #6366f1;
            border-bottom-color: #6366f1;
        }
        
        .controls-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            gap: 16px;
            flex-wrap: wrap;
        }
        
        .filters-left {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-chip {
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: white;
            font-size: 13px;
            font-weight: 500;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-chip:hover {
            border-color: #d1d5db;
            background: #f9fafb;
        }
        
        .filter-chip.active {
            background: #6366f1;
            color: white;
            border-color: #6366f1;
        }
        
        .filter-chip .count {
            background: rgba(0,0,0,0.1);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .filter-chip.active .count {
            background: rgba(255,255,255,0.2);
        }
        
        .date-filters {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .date-filters select,
        .date-filters input {
            height: 36px;
            padding: 0 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 13px;
            background: white;
            color: #374151;
        }
        
        .date-filters select:focus,
        .date-filters input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }
        
        .actions-right {
            display: flex;
            gap: 12px;
        }
        
        .btn {
            height: 36px;
            padding: 0 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #6366f1;
            color: white;
        }
        
        .btn-primary:hover {
            background: #4f46e5;
        }
        
        .btn-secondary {
            background: white;
            color: #374151;
            border: 1px solid #e5e7eb;
        }
        
        .btn-secondary:hover {
            background: #f9fafb;
        }
        
        .table-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
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
            padding: 12px 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.2s;
        }
        
        tbody tr:hover {
            background: #f9fafb;
        }
        
        tbody tr:last-child {
            border-bottom: none;
        }
        
        tbody td {
            padding: 16px;
            font-size: 14px;
            color: #374151;
            vertical-align: top;
        }
        
        tbody td:first-child {
            font-weight: 600;
            color: #111827;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
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
        
        .product-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .product-list li {
            padding: 4px 0;
            font-size: 13px;
            color: #6b7280;
        }
        
        .action-link {
            color: #6366f1;
            text-decoration: none;
            font-weight: 500;
            font-size: 13px;
        }
        
        .action-link:hover {
            text-decoration: underline;
        }
        
        
        @media (max-width: 1024px) {
            .page-container {
                padding: 16px;
            }
            
            .controls-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filters-left,
            .actions-right {
                width: 100%;
            }
            
            .table-card {
                overflow-x: auto;
            }
            
            table {
                min-width: 1000px;
            }
        }
    </style>
</head>
<body>
    @include('partials.admin-navbar')
    
    <div class="page-container">
        <div class="page-header">
            <h1 class="page-title">Contracts</h1>
            <p class="page-subtitle">Manage all your contracts and agreements</p>
        </div>
        
        <div class="tabs-container">
            <div class="tabs">
                <a href="#" class="tab active">All Contracts</a>
            </div>
        </div>
        
        <div class="controls-bar">
            <div class="filters-left">
                @php 
                    $currentStatus = request('status');
                    $allCount = $contracts->count();
                @endphp
                
                <a href="{{ route('contracts.index', ['status' => null]) }}" 
                   class="filter-chip {{ !$currentStatus ? 'active' : '' }}">
                    All <span class="count">{{ $allCount }}</span>
                </a>
                
                <a href="{{ route('contracts.index', ['status' => 'active']) }}" 
                   class="filter-chip {{ $currentStatus == 'active' ? 'active' : '' }}">
                    Active
                </a>
                
                <a href="{{ route('contracts.index', ['status' => 'expired']) }}" 
                   class="filter-chip {{ $currentStatus == 'expired' ? 'active' : '' }}">
                    Expired
                </a>
                
                <a href="{{ route('contracts.index', ['status' => 'paused']) }}" 
                   class="filter-chip {{ $currentStatus == 'paused' ? 'active' : '' }}">
                    Paused
                </a>
                
                <form method="GET" action="{{ route('contracts.index') }}" class="date-filters">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    
                    <select name="range">
                        <option value="">All time</option>
                        <option value="7" {{ request('range') == 7 ? 'selected' : '' }}>Last 7 days</option>
                        <option value="30" {{ request('range') == 30 ? 'selected' : '' }}>Last 30 days</option>
                        <option value="90" {{ request('range') == 90 ? 'selected' : '' }}>Last 90 days</option>
                    </select>
                    
                    <input type="date" name="from" value="{{ request('from') }}">
                    <input type="date" name="to" value="{{ request('to') }}">
                    
                    <button type="submit" class="btn btn-secondary">Filter</button>
                </form>
            </div>
            
            <div class="actions-right">
                <button onclick="window.location='{{ route('contracts.create') }}'" class="btn btn-primary">
                    + Add Contract
                </button>
            </div>
        </div>
        
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Contract Number</th>
                        <th>Company</th>
                        <th>Buyer</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Products</th>
                        <th>Total (with VAT)</th>
                        <th>Invoices</th>
                        <th>Actions</th>
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
                                        (VAT: {{ $item->product->vatRate->percentage ?? 0 }}%)
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td><strong>{{ number_format($contract->total_amount, 2) }}</strong></td>
                        <td>
                            @if($contract->invoices_count > 0)
                                <a href="{{ route('contracts.invoices', $contract->id) }}" class="action-link">
                                    View ({{ $contract->invoices_count }})
                                </a>
                            @else
                                <span style="color:#9ca3af;">No invoices</span>
                            @endif
                        </td>
                        <td>
                            @if($contract->invoices_count == 0)
                                <a href="{{ route('contracts.edit', $contract->id) }}" class="action-link">
                                    Edit
                                </a>
                            @else
                                <span style="color:#9ca3af;">—</span>
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Invoices - FiscalizationME</title>
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
            margin-bottom: 16px;
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
        
        .info-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 24px;
            display: flex;
            gap: 48px;
        }
        
        .info-item {
            font-size: 14px;
            color: #6b7280;
        }
        
        .info-item strong {
            color: #111827;
            font-weight: 600;
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
        
        .date-filters {
            display: flex;
            gap: 8px;
            align-items: center;
            flex: 1;
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
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
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
        
        .empty-state {
            padding: 80px 20px;
            text-align: center;
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .empty-state p {
            font-size: 16px;
            color: #6b7280;
        }
        
        @media (max-width: 1024px) {
            .page-container {
                padding: 16px;
            }
            
            .info-card {
                flex-direction: column;
                gap: 12px;
            }
            
            .controls-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .date-filters {
                flex-direction: column;
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
            <h1 class="page-title">Invoices for Contract {{ $contract->contract_number }}</h1>
            <p class="page-subtitle">View all invoices generated from this contract</p>
        </div>
        
        <div class="info-card">
            <div class="info-item">
                <strong>Company:</strong> {{ $contract->company->name }}
            </div>
            <div class="info-item">
                <strong>Buyer:</strong> {{ $contract->buyer->name }}
            </div>
        </div>
        
        <div class="tabs-container">
            <div class="tabs">
                <a href="#" class="tab active">All Invoices</a>
            </div>
        </div>
        
        <div class="controls-bar">
            <form method="GET" action="{{ route('contracts.invoices', $contract->id) }}" class="date-filters">
                <select name="range">
                    <option value="">All time</option>
                    <option value="7" {{ request('range') == 7 ? 'selected' : '' }}>Last 7 days</option>
                    <option value="30" {{ request('range') == 30 ? 'selected' : '' }}>Last 30 days</option>
                    <option value="90" {{ request('range') == 90 ? 'selected' : '' }}>Last 90 days</option>
                </select>
                
                <input type="date" name="from" value="{{ request('from') }}" placeholder="From">
                <input type="date" name="to" value="{{ request('to') }}" placeholder="To">
                
                <button type="submit" class="btn btn-secondary">Filter</button>
            </form>
        </div>
        
        @if($invoices->count())
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Invoice Number</th>
                        <th>Company</th>
                        <th>Buyer</th>
                        <th>Seller</th>
                        <th>Issue Date</th>
                        <th>Total (with VAT)</th>
                        <th>Total VAT</th>
                        <th>Payment Method</th>
                        <th>Products</th>
                        <th>Action</th>
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
                        <td><strong>{{ number_format($invoice->total_price_to_pay, 2) }}</strong></td>
                        <td>{{ number_format($invoice->total_vat_amount, 2) }}</td>
                        <td>{{ $invoice->payment_method_type }}</td>
                        <td>
                            <ul class="product-list">
                                @foreach($invoice->items as $item)
                                    <li>
                                        {{ $item->product->name }} - 
                                        {{ $item->quantity }} x 
                                        {{ number_format($item->unit_price, 2) }} 
                                        (VAT: {{ $item->product->vatRate ? $item->product->vatRate->percentage : 0 }}%)
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td>
                            <button onclick="fiskalizuj({{ $invoice->id }})" class="btn btn-success">
                                Fiscalize
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="table-card">
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <p>No invoices found for this contract.</p>
            </div>
        </div>
        @endif
    </div>

    <script>
    function fiskalizuj(invoiceId) {
        fetch('/invoice/' + invoiceId + '/fiskalizuj', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({})
        })
        .then(async response => {
            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON:', text);
                alert('Invalid response from server. Check console for details.');
                return;
            }

            console.log('Fiscalization response:', data);
            alert(`Status: ${data.status}\nTax authority response:\n${data.body}`);
        })
        .catch(err => {
            console.error('Fetch error:', err);
            alert('Error sending request: ' + err);
        });
    }
    </script>
</body>
</html>

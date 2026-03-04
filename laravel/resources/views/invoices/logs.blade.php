<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiscal Logs</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .logs-container { max-width: 1600px; margin: 0 auto; }
        
        .page-header { text-align: center; margin-bottom: 30px; }
        
        .page-header h1 {
            font-size: 42px;
            font-weight: 700;
            color: white;
        }

        .filter-card {
            background: white;
            padding: 32px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            align-items: end;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
        }

        .filter-group input {
            height: 44px;
            padding: 0 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            min-width: 250px;
            font-size: 14px;
            background: #f9fafb;
            color: #1f2937;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .filter-group input:hover {
            border-color: #d1d5db;
            background: white;
        }

        .filter-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102,126,234,0.1);
        }

        .btn {
            height: 44px;
            padding: 0 24px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102,126,234,0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102,126,234,0.5);
        }

        .btn-secondary {
            background: white;
            color: #374151;
            border: 2px solid #e5e7eb;
        }

        .btn-secondary:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            transform: translateY(-2px);
        }
        
        .results-count {
            color: white;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .table-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            animation: slideUp 0.6s ease;
        }
        
        table { width: 100%; border-collapse: collapse; }
        
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
        }
        
        tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: 0.3s;
        }
        
        tbody tr:hover {
            background: rgba(102,126,234,0.05);
        }
        
        tbody td {
            padding: 20px 24px;
            font-size: 14px;
            vertical-align: top;
        }
        
        .invoice-text {
            font-weight: 700;
            color: #1f2937;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .status-success { background: #d1fae5; color: #065f46; }
        .status-error { background: #fee2e2; color: #991b1b; }
        .status-pending { background: #fef3c7; color: #92400e; }
        
        .error-message { color: #dc2626; font-size: 13px; }
        .date-text { color: #6b7280; font-size: 13px; }

        .no-results {
            padding: 40px;
            text-align: center;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="logs-container">
        
        <div class="page-header">
            <h1>📊 Fiscal Logs</h1>
        </div>

        <form method="GET" action="{{ route('invoices.logs') }}" class="filter-card">
            <div class="filter-group">
                <label>Invoice Number</label>
                <input type="text"
                       name="invoice_number"
                       placeholder="e.g. INV-001"
                       value="{{ request('invoice_number') }}">
            </div>

            <button type="submit" class="btn btn-primary">
                Filter
            </button>

            <a href="{{ route('invoices.logs') }}" class="btn btn-secondary">
                Reset
            </a>
        </form>

        <div class="results-count">
            Total logs: {{ $logs->count() }}
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Invoice Number</th>
                        <th>Status</th>
                        <th>Error</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>
                                <span class="invoice-text">
                                    {{ $log->invoice->invoice_number ?? 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-{{ strtolower($log->status) }}">
                                    {{ $log->status }}
                                </span>
                            </td>
                            <td>
                                <span class="error-message">
                                    {{ $log->error_message ?? '-' }}
                                </span>
                            </td>
                            <td>
                                <span class="date-text">
                                    {{ $log->created_at }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="no-results">
                                No logs found for given filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>
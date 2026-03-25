<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiscal Logs - FiscalizationME</title>
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
        
        .filter-form {
            display: flex;
            gap: 8px;
            align-items: center;
            flex: 1;
        }
        
        .filter-form input {
            height: 36px;
            padding: 0 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 13px;
            background: white;
            color: #374151;
            min-width: 250px;
        }
        
        .filter-form input:focus {
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
            text-decoration: none;
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
        
        .status-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-error {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .error-message {
            color: #dc2626;
            font-size: 13px;
            max-width: 400px;
        }
        
        .date-text {
            color: #6b7280;
            font-size: 13px;
        }
        
        .no-results {
            padding: 60px 20px;
            text-align: center;
            color: #6b7280 !important;
            font-weight: normal !important;
        }
        
        @media (max-width: 1024px) {
            .page-container {
                padding: 16px;
            }
            
            .controls-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-form {
                flex-direction: column;
            }
            
            .filter-form input {
                width: 100%;
            }
            
            .table-card {
                overflow-x: auto;
            }
            
            table {
                min-width: 800px;
            }
        }
    </style>
</head>
<body>
    @include('partials.admin-navbar')
    
    <div class="page-container">
        <div class="page-header">
            <h1 class="page-title">Fiscal Logs</h1>
            <p class="page-subtitle">Monitor fiscalization status and errors</p>
        </div>
        
        <div class="tabs-container">
            <div class="tabs">
                <a href="#" class="tab active">All Logs</a>
            </div>
        </div>
        
        <div class="controls-bar">
            <input type="text" id="log-search"
                placeholder="Search by invoice number..."
                style="height:36px; padding:0 12px; border:1px solid #e5e7eb; border-radius:8px; font-size:13px; background:white; color:#374151; width:250px;">
        </div>
        
        
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Invoice Number</th>
                        <th>Status</th>
                        <th>Message</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody id="logs-tbody">
                    @forelse($logs as $log)
                        <tr>
                            <td>
                                {{ $log->invoice->invoice_number ?? 'N/A' }}
                            </td>
                            <td>
                                <span class="status-badge status-{{ strtolower($log->status) }}">
                                    {{ $log->status }}
                                </span>
                            </td>
                            <td>
                                <span class="error-message">
                                    {{ $log->error_message ?? '—' }}
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
                            <td colspan="4" class="no-results" >
                                No logs found matching your search.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
    document.getElementById('log-search').addEventListener('input', function() {
        var query = this.value.toLowerCase().trim();
        var rows = document.querySelectorAll('#logs-tbody tr');
        var visible = 0;

        rows.forEach(function(row) {
            var text = row.textContent.toLowerCase();
            if (text.indexOf(query) !== -1) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });
    });
    </script>

</body>
</html>

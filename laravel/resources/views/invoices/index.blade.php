<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices - FiscalizationME</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f9fafb; color: #111827;
        }
        
        .page-container { max-width: 1400px; margin: 0 auto; padding: 32px; }
        .page-header { margin-bottom: 24px; }
        .page-title { font-size: 28px; font-weight: 700; color: #111827; margin-bottom: 8px; }
        .page-subtitle { font-size: 14px; color: #6b7280; }
        
        .tabs-container { border-bottom: 1px solid #e5e7eb; margin-bottom: 24px; }
        .tabs { display: flex; gap: 32px; }
        .tab {
            padding: 12px 0; font-size: 14px; font-weight: 500; color: #6b7280;
            text-decoration: none; border-bottom: 2px solid transparent; transition: all 0.2s;
        }
        .tab:hover { color: #111827; }
        .tab.active { color: #6366f1; border-bottom-color: #6366f1; }
        
        .controls-bar {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 24px; gap: 16px; flex-wrap: wrap;
        }
        .date-filters { display: flex; gap: 8px; align-items: center; flex: 1; }
        .date-filters select, .date-filters input {
            height: 36px; padding: 0 12px; border: 1px solid #e5e7eb;
            border-radius: 8px; font-size: 13px; background: white; color: #374151;
        }
        .date-filters select:focus, .date-filters input:focus {
            outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }
        
        .btn {
            height: 32px; padding: 0 12px; border-radius: 8px; font-size: 12px;
            font-weight: 500; cursor: pointer; transition: all 0.2s; border: none;
            display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;
        }
        .btn-secondary { background: white; color: #374151; border: 1px solid #e5e7eb; height: 36px; }
        .btn-secondary:hover { background: #f9fafb; }
        .btn-outline { background: white; color: #6366f1; border: 1px solid #6366f1; }
        .btn-outline:hover { background: #f5f3ff; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-logs { background: white; color: #6b7280; border: 1px solid #e5e7eb; }
        .btn-logs:hover { background: #f9fafb; color: #111827; }
        
        .table-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
        thead th {
            padding: 12px 12px; text-align: left; font-size: 11px;
            font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;
        }
        thead th.th-center { text-align: center; }
        tbody tr { border-bottom: 1px solid #f3f4f6; transition: background 0.2s; }
        tbody tr:hover { background: #f9fafb; }
        tbody tr:last-child { border-bottom: none; }
        tbody td { padding: 12px; font-size: 13px; color: #374151; vertical-align: middle; }
        tbody td:first-child { font-weight: 600; color: #111827; font-size: 11px; word-break: break-all; line-height: 1.5; }

        /* Column widths */
        col.col-number { width: 16%; }
        col.col-company { width: 9%; }
        col.col-buyer { width: 7%; }
        col.col-seller { width: 7%; }
        col.col-date { width: 7%; }
        col.col-total { width: 6%; }
        col.col-vat { width: 5%; }
        col.col-payment { width: 6%; }
        col.col-products { width: 15%; }
        col.col-attempts { width: 4%; }
        col.col-status { width: 8%; }
        col.col-action { width: 10%; }

        .product-list { list-style: none; padding: 0; margin: 0; }
        .product-list li {
            padding: 2px 0; font-size: 11px; color: #6b7280;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }

        .badge {
            display: inline-flex; align-items: center; padding: 2px 8px;
            border-radius: 999px; font-size: 11px; font-weight: 500;
        }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-gray { background: #f3f4f6; color: #6b7280; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-red { background: #fee2e2; color: #991b1b; }

        .attempts-badge {
            display: inline-flex; align-items: center; justify-content: center;
            width: 26px; height: 26px; border-radius: 999px; font-size: 12px;
            font-weight: 600; background: #f3f4f6; color: #374151;
        }
        .attempts-badge.has-errors { background: #fee2e2; color: #991b1b; }
        .attempts-badge.success { background: #d1fae5; color: #065f46; }

        .action-col { display: flex; flex-direction: column; gap: 4px; align-items: flex-start; }

        .empty-state { padding: 80px 20px; text-align: center; }
        .empty-state-icon { font-size: 48px; margin-bottom: 16px; opacity: 0.5; }
        .empty-state p { font-size: 16px; color: #6b7280; }

        /* Logs Modal */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.5);
            z-index: 1000; display: none; align-items: center; justify-content: center;
        }
        .modal-overlay.show { display: flex; }
        .modal-logs {
            background: white; border-radius: 12px; padding: 32px; width: 100%;
            max-width: 800px; max-height: 80vh; overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2); position: relative;
        }
        .modal-title { font-size: 16px; font-weight: 700; color: #111827; margin-bottom: 16px; }
        .modal-close {
            position: absolute; top: 12px; right: 16px; background: none;
            border: none; font-size: 20px; color: #9ca3af; cursor: pointer;
        }
        .modal-close:hover { color: #374151; }
        .logs-table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .logs-table thead { background: #f9fafb; }
        .logs-table thead th {
            padding: 10px 12px; text-align: left; font-size: 11px;
            font-weight: 600; color: #6b7280; text-transform: uppercase;
            border-bottom: 1px solid #e5e7eb;
        }
        .logs-table tbody tr { border-bottom: 1px solid #f3f4f6; }
        .logs-table tbody tr:last-child { border-bottom: none; }
        .logs-table tbody td { padding: 12px; font-size: 13px; color: #374151; vertical-align: top; }
        .log-message { font-size: 12px; color: #6b7280; max-width: 400px; word-break: break-word; }
        .loading-logs { text-align: center; padding: 32px; color: #6b7280; }

        @media (max-width: 1024px) {
            .page-container { padding: 16px; }
            .controls-bar { flex-direction: column; align-items: stretch; }
            .date-filters { flex-direction: column; }
            .table-card { overflow-x: auto; }
            table { min-width: 1100px; table-layout: auto; }
        }
    </style>
</head>
<body>
    @include('partials.admin-navbar')

    {{-- LOGS MODAL --}}
    <div class="modal-overlay" id="logs-modal">
        <div class="modal-logs">
            <button class="modal-close" onclick="closeLogsModal()">✕</button>
            <h2 class="modal-title" id="logs-modal-title">Logovi fiskalizacije</h2>
            <div id="logs-modal-content">
                <div class="loading-logs">Učitavanje...</div>
            </div>
        </div>
    </div>
    
    <div class="page-container">
        <div class="page-header">
            <h1 class="page-title">Invoices</h1>
            <p class="page-subtitle">View and manage all invoices</p>
        </div>
        
        <div class="tabs-container">
            <div class="tabs">
                <a href="#" class="tab active">All Invoices</a>
            </div>
        </div>
        
        <div class="controls-bar">
            <form method="GET" action="{{ route('invoices.index') }}" class="date-filters">
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
                <colgroup>
                    <col class="col-number">
                    <col class="col-company">
                    <col class="col-buyer">
                    <col class="col-seller">
                    <col class="col-date">
                    <col class="col-total">
                    <col class="col-vat">
                    <col class="col-payment">
                    <col class="col-products">
                    <col class="col-attempts">
                    <col class="col-status">
                    <col class="col-action">
                </colgroup>
                <thead>
                    <tr>
                        <th>Invoice Number</th>
                        <th>Company</th>
                        <th>Buyer</th>
                        <th>Seller</th>
                        <th>Issue Date</th>
                        <th>Total</th>
                        <th>PDV</th>
                        <th>Payment</th>
                        <th>Products</th>
                        <th class="th-center">Try</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                    @php
                        $logsCount = $invoice->fiscalLogs->count();
                        $successLogs = $invoice->fiscalLogs->where('status', 'SUCCESS')->count();
                        $errorLogs = $invoice->fiscalLogs->where('status', 'ERROR')->count();
                    @endphp
                    <tr>
                        <td>{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->company->name }}</td>
                        <td>{{ $invoice->buyer->name }}</td>
                        <td>{{ $invoice->user->name }}</td>
                        <td style="font-size:12px;">{{ $invoice->issued_at->format('Y-m-d') }}</td>
                        <td><strong>{{ number_format($invoice->total_price_to_pay, 2) }}</strong></td>
                        <td>{{ number_format($invoice->total_vat_amount, 2) }}</td>
                        <td style="font-size:12px;">{{ $invoice->payment_method_type }}</td>
                        <td>
                            <ul class="product-list">
                                @foreach($invoice->items as $item)
                                    <li title="{{ $item->product->name }} - {{ $item->quantity }} x {{ number_format($item->unit_price, 2) }} (VAT: {{ $item->product->vatRate ? $item->product->vatRate->percentage : 0 }}%)">
                                        {{ $item->product->name }} × {{ (float)$item->quantity == (int)$item->quantity ? (int)$item->quantity : $item->quantity }}
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td style="text-align:center;">
                            @if($logsCount > 0)
                                <span class="attempts-badge {{ $successLogs > 0 ? 'success' : 'has-errors' }}"
                                      title="{{ $errorLogs }} greška, {{ $successLogs }} uspješno">
                                    {{ $logsCount }}
                                </span>
                            @else
                                <span class="attempts-badge">0</span>
                            @endif
                        </td>
                        <td>
                            @if($invoice->invoice_type->value === 'CORRECTIVE')
                                <span class="badge badge-yellow">Storno</span>
                            @elseif($invoice->fic)
                                <span class="badge badge-green">✓ Fisk.</span>
                            @else
                                <span class="badge badge-gray">Nije fisk.</span>
                            @endif
                        </td>
                        <td>
                            <div class="action-col">
                                @if($logsCount > 0)
                                    <button onclick="showLogs({{ $invoice->id }}, '{{ $invoice->invoice_number }}')" class="btn btn-logs">Logs</button>
                                @endif
                            </div>
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
                <p>No invoices found.</p>
            </div>
        </div>
        @endif
    </div>

    <script>
    function showLogs(invoiceId, invoiceNumber) {
        document.getElementById('logs-modal-title').textContent = 'Logovi: ' + invoiceNumber;
        document.getElementById('logs-modal-content').innerHTML = '<div class="loading-logs">Učitavanje...</div>';
        document.getElementById('logs-modal').classList.add('show');

        fetch('/invoice/' + invoiceId + '/logs', { headers: { 'Accept': 'application/json' } })
        .then(res => res.json())
        .then(logs => {
            if (logs.length === 0) {
                document.getElementById('logs-modal-content').innerHTML = '<p style="text-align:center;color:#6b7280;padding:32px;">Nema logova za ovaj račun.</p>';
                return;
            }
            var html = '<table class="logs-table"><thead><tr><th>#</th><th>Status</th><th>Poruka</th><th>Datum</th></tr></thead><tbody>';
            logs.forEach(function(log, index) {
                var message = log.error_message || '—';
                if (message.length > 200) message = message.substring(0, 200) + '...';
                html += '<tr>';
                html += '<td style="font-weight:600;">' + (index + 1) + '</td>';
                html += '<td><span class="badge ' + (log.status === 'SUCCESS' ? 'badge-green' : 'badge-red') + '">' + log.status + '</span></td>';
                html += '<td><div class="log-message">' + message + '</div></td>';
                html += '<td style="color:#6b7280;font-size:12px;white-space:nowrap;">' + log.created_at + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            document.getElementById('logs-modal-content').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('logs-modal-content').innerHTML = '<p style="text-align:center;color:#dc2626;padding:32px;">Greška pri učitavanju logova.</p>';
        });
    }

    function closeLogsModal() {
        document.getElementById('logs-modal').classList.remove('show');
    }

    document.getElementById('logs-modal').addEventListener('click', function(e) {
        if (e.target === this) closeLogsModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeLogsModal();
    });
    </script>
</body>
</html>
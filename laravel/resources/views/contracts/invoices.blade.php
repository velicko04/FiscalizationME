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
        
        .page-container { max-width: 1400px; margin: 0 auto; padding: 32px; }
        .page-header { margin-bottom: 16px; }
        .page-title { font-size: 28px; font-weight: 700; color: #111827; margin-bottom: 8px; }
        .page-subtitle { font-size: 14px; color: #6b7280; }
        
        .info-card {
            background: white; border: 1px solid #e5e7eb; border-radius: 12px;
            padding: 20px 24px; margin-bottom: 24px; display: flex; gap: 48px;
        }
        .info-item { font-size: 14px; color: #6b7280; }
        .info-item strong { color: #111827; font-weight: 600; }
        
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
        
        .btn {
            height: 32px; padding: 0 12px; border-radius: 8px; font-size: 12px;
            font-weight: 500; cursor: pointer; transition: all 0.2s; border: none;
            display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;
        }
        .btn-primary { background: #6366f1; color: white; }
        .btn-primary:hover { background: #4f46e5; }
        .btn-secondary { background: white; color: #374151; border: 1px solid #e5e7eb; }
        .btn-secondary:hover { background: #f9fafb; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-outline { background: white; color: #6366f1; border: 1px solid #6366f1; }
        .btn-outline:hover { background: #f5f3ff; }
        .btn-logs { background: white; color: #6b7280; border: 1px solid #e5e7eb; }
        .btn-logs:hover { background: #f9fafb; color: #111827; }
        .btn-filter { height: 36px; }
        
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
        tbody td:first-child { font-weight: 600; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 0; }

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
        col.col-status { width: 7%; }
        col.col-action { width: 9%; }
        
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

        .action-col { display: flex; flex-direction: column; gap: 6px; align-items: flex-start; }

        .empty-state { padding: 80px 20px; text-align: center; }
        .empty-state-icon { font-size: 48px; margin-bottom: 16px; opacity: 0.5; }
        .empty-state p { font-size: 16px; color: #6b7280; }

        /* Modals */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.5);
            z-index: 1000; display: none; align-items: center; justify-content: center;
        }
        .modal-overlay.show { display: flex; }
        .modal-qr {
            background: white; border-radius: 12px; padding: 32px; width: 100%;
            max-width: 320px; box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            position: relative; text-align: center;
        }
        .modal-logs {
            background: white; border-radius: 12px; padding: 32px; width: 100%;
            max-width: 800px; max-height: 80vh; overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2); position: relative;
        }
        .modal-title { font-size: 16px; font-weight: 700; color: #111827; margin-bottom: 16px; }
        .modal-subtitle { font-size: 12px; color: #6b7280; margin-bottom: 20px; }
        .modal-close {
            position: absolute; top: 12px; right: 16px; background: none;
            border: none; font-size: 20px; color: #9ca3af; cursor: pointer;
        }
        .modal-close:hover { color: #374151; }
        .qr-image { width: 200px; height: 200px; margin: 0 auto 16px; display: block; }
        .fic-code {
            font-size: 11px; color: #6b7280; word-break: break-all;
            background: #f9fafb; padding: 8px; border-radius: 6px; margin-top: 12px;
        }
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
            .info-card { flex-direction: column; gap: 12px; }
            .controls-bar { flex-direction: column; align-items: stretch; }
            .date-filters { flex-direction: column; }
            .table-card { overflow-x: auto; }
            table { min-width: 1100px; table-layout: auto; }
        }
    </style>
</head>
<body>
    @include('partials.admin-navbar')

    {{-- QR KOD MODAL --}}
    <div class="modal-overlay" id="qr-modal">
        <div class="modal-qr">
            <button class="modal-close" onclick="closeQrModal()">✕</button>
            <h2 class="modal-title">QR Kod Fakture</h2>
            <p class="modal-subtitle">Skenirajte za provjeru na portalu poreske</p>
            <img id="qr-image" class="qr-image" src="" alt="QR Kod">
            <div class="fic-code" id="fic-display"></div>
        </div>
    </div>

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
            <h1 class="page-title">Invoices for Contract {{ $contract->contract_number }}</h1>
            <p class="page-subtitle">View all invoices generated from this contract</p>
        </div>
        
        <div class="info-card">
            <div class="info-item"><strong>Company:</strong> {{ $contract->company->name }}</div>
            <div class="info-item"><strong>Buyer:</strong> {{ $contract->buyer->name }}</div>
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
                <button type="submit" class="btn btn-secondary btn-filter">Filter</button>
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
                        <td style="font-size:12px;">{{ $invoice->invoice_number }}</td>
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
                                    <li title="{{ $item->product->name }} - {{ $item->quantity + 0 }} x {{ number_format($item->unit_price, 2) }} (VAT: {{ $item->product->vatRate ? $item->product->vatRate->percentage : 0 }}%)">
                                        {{ $item->product->name }} × {{ $item->quantity + 0 }}
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
                                @if($invoice->fic)
                                    <button onclick="showQr({{ $invoice->id }}, '{{ $invoice->fic }}')" class="btn btn-outline">QR</button>
                                    @if($invoice->invoice_type->value !== 'CORRECTIVE' && !$invoice->correctiveInvoices->count())
                                        <button onclick="storno({{ $invoice->id }}, this)" class="btn" style="background:#ef4444;color:white;">Storno</button>
                                    @elseif($invoice->correctiveInvoices->count())
                                        <span class="badge badge-gray">Stornirano</span>
                                    @endif
                                @elseif($invoice->invoice_type->value !== 'CORRECTIVE')
                                    <button onclick="fiskalizuj({{ $invoice->id }}, this)" class="btn btn-success">Fiskalizuj</button>
                                @else
                                    <button onclick="fiskalizuj({{ $invoice->id }}, this)" class="btn btn-success">Fisk. storno</button>
                                @endif

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
                <p>No invoices found for this contract.</p>
            </div>
        </div>
        @endif
    </div>

    <script>
    function fiskalizuj(invoiceId, btn) {
        btn.textContent = 'Slanje...';
        btn.disabled = true;
        fetch('/invoice/' + invoiceId + '/fiskalizuj', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({})
        })
        .then(async response => {
            const text = await response.text();
            let data;
            try { data = JSON.parse(text); } catch (e) {
                alert('Invalid response from server.');
                btn.textContent = 'Fiskalizuj';
                btn.disabled = false;
                return;
            }
            if (data.status === 200) {
                window.location.reload();
            } else {
                alert('Greška: ' + data.body);
                btn.textContent = 'Fiskalizuj';
                btn.disabled = false;
            }
        })
        .catch(err => { alert('Greška: ' + err); btn.textContent = 'Fiskalizuj'; btn.disabled = false; });
    }

    function showQr(invoiceId, fic) {
        document.getElementById('qr-image').src = '/invoice/' + invoiceId + '/qrcode';
        document.getElementById('fic-display').textContent = 'FIC: ' + fic;
        document.getElementById('qr-modal').classList.add('show');
    }

    function closeQrModal() {
        document.getElementById('qr-modal').classList.remove('show');
        document.getElementById('qr-image').src = '';
    }

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

    function storno(invoiceId, btn) {
        if (!confirm('Jeste li sigurni da želite stornirati ovaj račun?')) return;
        btn.textContent = 'Slanje...';
        btn.disabled = true;
        fetch('/invoice/' + invoiceId + '/storno', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({})
        })
        .then(async response => {
            const data = await response.json();
            if (data.status === 200) {
                alert(data.body);
                window.location.reload();
            } else {
                alert('Greška: ' + data.body);
                btn.textContent = 'Storno';
                btn.disabled = false;
            }
        })
        .catch(err => { alert('Greška: ' + err); btn.textContent = 'Storno'; btn.disabled = false; });
    }

    document.getElementById('qr-modal').addEventListener('click', function(e) { if (e.target === this) closeQrModal(); });
    document.getElementById('logs-modal').addEventListener('click', function(e) { if (e.target === this) closeLogsModal(); });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') { closeQrModal(); closeLogsModal(); } });
    </script>
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Računi za ugovor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .invoice-container {
            max-width: 1600px;
            margin: 0 auto;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .page-header h1 {
            font-size: 38px;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 16px;
        }
        
        .contract-info-card {
            background: white;
            border-radius: 16px;
            padding: 24px 32px;
            margin-bottom: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            gap: 48px;
            justify-content: center;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .contract-info-card p {
            font-size: 15px;
            color: #6b7280;
        }
        
        .contract-info-card strong {
            color: #1f2937;
            font-weight: 700;
        }
        
        .controls-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 32px;
            margin-bottom: 32px;
            animation: slideUp 0.6s ease;
        }
        
        .filter-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
        }
        
        .date-filter {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            flex: 1;
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
        
        .back-button a {
            text-decoration: none;
        }
        
        .back-button button {
            height: 44px;
            padding: 0 24px;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            background: white;
            color: #374151;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .back-button button:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            transform: translateY(-2px);
        }
        
        .table-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            animation: slideUp 0.7s ease;
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
        
        .empty-state {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 80px 40px;
            text-align: center;
            animation: slideUp 0.7s ease;
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .empty-state p {
            font-size: 18px;
            color: #6b7280;
            font-weight: 500;
        }
        
        @media (max-width: 1024px) {
            body { padding: 20px 16px; }
            .page-header h1 { font-size: 28px; }
            .contract-info-card { flex-direction: column; gap: 16px; text-align: center; }
            .controls-card { padding: 24px; }
            .filter-container { flex-direction: column; align-items: stretch; }
            .date-filter { flex-direction: column; align-items: stretch; }
            .date-filter select, .date-filter input, .date-filter button, .back-button button { width: 100%; }
            .table-card { overflow-x: auto; }
            table { min-width: 1000px; }
            .empty-state { padding: 60px 20px; }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="page-header">
            <h1>📄 Računi za ugovor {{ $contract->contract_number }}</h1>
        </div>

        <div class="contract-info-card">
            <p><strong>Kompanija:</strong> {{ $contract->company->name }}</p>
            <p><strong>Kupac:</strong> {{ $contract->buyer->name }}</p>
        </div>

        <div class="controls-card">
            <div class="filter-container">
                {{-- DATE FILTER --}}
                <form method="GET" action="{{ route('contracts.invoices', $contract->id) }}" class="date-filter">
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

                <div class="back-button">
                    <a href="{{ route('contracts.index') }}">
                        <button>⬅ Nazad na ugovore</button>
                    </a>
                </div>
            </div>
        </div>

        @if($invoices->count())
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Broj računa</th>
                        <th>Kompanija</th>
                        <th>Kupac</th>
                        <th>Prodavac</th>
                        <th>Datum izdavanja</th>
                        <th>Ukupno (sa PDV)</th>
                        <th>Ukupno PDV</th>
                        <th>Metoda plaćanja</th>
                        <th>Proizvodi</th>
                        <th>XML</th>
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
                                        (PDV: {{ $item->product->vatRate ? $item->product->vatRate->percentage : 0 }}%)
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td>
                            <button onclick="fiskalizuj({{ $invoice->id }})" style="padding:6px 12px;border-radius:8px;border:none;background:#667eea;color:white;cursor:pointer;">
                                Fiskalizuj i pošalji
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="empty-state">
            <div class="empty-state-icon">📭</div>
            <p>Nema računa za ovaj ugovor.</p>
        </div>
        @endif
    </div>
</body>
</html>


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
        const text = await response.text(); // uzimamo raw text
        let data;
        try {
            data = JSON.parse(text); // pokušaj parse JSON
        } catch (e) {
            console.error('Nije validan JSON:', text);
            alert('Nevalidan odgovor sa servera. Pogledaj konzolu za detalje.');
            return;
        }

        console.log('Fiskalizacija response:', data); // log u konzolu
        alert(`Status: ${data.status}\nOdgovor poreske:\n${data.body}`);
    })
    .catch(err => {
        console.error('Fetch greška:', err);
        alert('Greška prilikom slanja: ' + err);
    });
}
</script>
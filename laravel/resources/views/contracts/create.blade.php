<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novi Ugovor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .form-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            font-size: 36px;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .form-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 48px;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-section {
            margin-bottom: 40px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .section-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        label {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
        }
        
        input, select {
            height: 48px;
            padding: 0 16px;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            font-size: 15px;
            background: #f9fafb;
            color: #1f2937;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        input:hover, select:hover {
            border-color: #d1d5db;
            background: white;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102,126,234,0.1);
        }
        
        .items-autocomplete {
            position: relative;
        }
        
        .suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #667eea;
            border-radius: 12px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 10;
            margin-top: 4px;
            box-shadow: 0 10px 30px rgba(102,126,234,0.2);
        }
        
        .suggestions div {
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .suggestions div:hover {
            background: linear-gradient(90deg, rgba(102,126,234,0.1) 0%, rgba(118,75,162,0.1) 100%);
        }
        
        .suggestions div:first-child {
            font-weight: 700;
            color: #667eea;
        }
        
        .item-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 16px;
            margin-bottom: 16px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .item-row:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102,126,234,0.15);
        }
        
        .item-row span {
            display: flex;
            align-items: center;
            font-size: 14px;
            color: #374151;
            font-weight: 500;
        }
        
        .btn {
            height: 48px;
            padding: 0 28px;
            border-radius: 12px;
            border: none;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
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
        
        .btn-add {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            width: 100%;
            margin-top: 16px;
            margin-bottom: 16px;
            box-shadow: 0 4px 12px rgba(16,185,129,0.3);
        }
        
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16,185,129,0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            width: 48px;
            height: 48px;
            padding: 0;
            box-shadow: 0 2px 8px rgba(239,68,68,0.3);
        }
        
        .btn-danger:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(239,68,68,0.4);
        }
        
        .form-actions {
            display: flex;
            gap: 16px;
            margin-top: 48px;
            padding-top: 32px;
            border-top: 2px solid #f3f4f6;
        }
        
        .form-actions .btn-primary {
            flex: 1;
            height: 56px;
            font-size: 16px;
        }
        
        .form-actions a {
            text-decoration: none;
        }
        
        @media (max-width: 768px) {
            body { padding: 20px 16px; }
            .form-card { padding: 28px 20px; }
            .page-header h1 { font-size: 28px; }
            .form-grid { grid-template-columns: 1fr; }
            .item-row { grid-template-columns: 1fr; padding: 16px; }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="page-header">
            <h1>✨ Novi Ugovor</h1>
        </div>

        <div class="form-card">
            <form method="POST" action="{{ route('contracts.store') }}">
                @csrf

                {{-- Osnovni podaci --}}
                <div class="form-section">
                    <h3 class="section-title">Osnovne informacije</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Broj ugovora:</label>
                            <input type="text" name="contract_number" required>
                        </div>

                        <div class="form-group">
                            <label>Kompanija:</label>
                            <select name="company_id" required>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Kupac:</label>
                            <select name="buyer_id" required>
                                @foreach($buyers as $buyer)
                                    <option value="{{ $buyer->id }}">{{ $buyer->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Status:</label>
                            <select name="status">
                                <option value="active">Active</option>
                                <option value="paused">Paused</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Početak:</label>
                            <input type="date" name="start_date" required>
                        </div>

                        <div class="form-group">
                            <label>Kraj:</label>
                            <input type="date" name="end_date" required>
                        </div>

                        <div class="form-group">
                            <label>Billing frequency:</label>
                            <select name="billing_frequency">
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Dan izdavanja:</label>
                            <input type="number" name="issue_day" min="1" max="31" required>
                        </div>
                    </div>
                </div>

                {{-- Stavke ugovora --}}
                <div class="form-section">
                    <h3 class="section-title">Stavke ugovora</h3>

                    <div class="form-group items-autocomplete">
                        <label>Naziv proizvoda:</label>
                        <input type="text" id="product-search" placeholder="Pretraži...">
                        <div id="suggestions" class="suggestions" style="display:none;"></div>
                    </div>

                    <div class="form-group" style="margin-top: 10px;">
                        <label>Količina:</label>
                        <input type="number" id="product-quantity" step="0.01" value="1">
                    </div>

                    <div class="form-group" style="margin-top: 10px;">
                        <label>Cijena:</label>
                        <input type="number" id="product-price" step="0.01" value="0">
                    </div>

                    <button type="button" class="btn btn-add" onclick="addItem()">+ Dodaj stavku</button>

                    <div id="items-container"></div>
                </div>

                {{-- Submit --}}
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Sačuvaj ugovor</button>
                    <a href="{{ route('contracts.index') }}">
                        <button type="button" class="btn btn-secondary">⬅ Nazad</button>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
    let products = @json($products); 
    const searchInput = document.getElementById('product-search');
    const suggestions = document.getElementById('suggestions');

    // --- Autocomplete ---
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        suggestions.innerHTML = '';
        if (!query) return suggestions.style.display='none';

        const filtered = products.filter(p => p.name.toLowerCase().includes(query));

        if(filtered.length === 0){
            const div = document.createElement('div');
            div.textContent = `Dodaj novi proizvod: "${searchInput.value}"`;
            div.style.fontWeight = 'bold';
            div.addEventListener('click', async () => {
                const res = await fetch("{{ route('products.ajaxStore') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        name: searchInput.value,
                        price: parseFloat(document.getElementById('product-price').value),
                        vat_rate_id: 1,
                        unit: 'kom'
                    })
                });
                const data = await res.json();
                if(data.success){
                    products.push(data.product);
                    selectProduct(data.product);
                    suggestions.style.display = 'none';
                }
            });
            suggestions.appendChild(div);
        } else {
            filtered.forEach(p => {
                const div = document.createElement('div');
                div.textContent = `${p.name} (PDV ${p.vatRate.percentage}%)`;
                div.dataset.id = p.id;
                div.dataset.price = p.price;
                div.dataset.vat = p.vatRate.percentage;
                div.dataset.name = p.name;
                div.addEventListener('click', () => selectProduct(p));
                suggestions.appendChild(div);
            });
        }
        suggestions.style.display = 'block';
    });

    function selectProduct(product) {
        searchInput.value = product.name;
        document.getElementById('product-price').value = product.price;
        searchInput.dataset.vat = product.vatRate.percentage;
    }

    // --- Dodavanje stavke ---
    function addItem() {
        const name = searchInput.value.trim();
        const quantity = parseFloat(document.getElementById('product-quantity').value) || 1;
        const price = parseFloat(document.getElementById('product-price').value) || 0;
        const vatPercentage = parseFloat(searchInput.dataset.vat) || 0;

        if (!name) return alert('Unesite naziv proizvoda');

        const totalPrice = quantity * price;
        const totalPriceWithVat = totalPrice + (totalPrice * vatPercentage / 100);

        const container = document.getElementById('items-container');
        const row = document.createElement('div');
        row.classList.add('item-row');

        row.innerHTML = `
            <span>${name}</span>
            <span>${quantity}</span>
            <span>${price.toFixed(2)}</span>
            <span>${totalPriceWithVat.toFixed(2)} (sa PDV)</span>
            <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">✕</button>
        `;

        container.appendChild(row);

        searchInput.value = '';
        document.getElementById('product-quantity').value = 1;
        document.getElementById('product-price').value = 0;
        suggestions.style.display = 'none';
    }

    // --- Submit sa JSON-om ---
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e){
        const container = document.getElementById('items-container');
        const rows = container.querySelectorAll('.item-row');
        if(rows.length === 0){
            e.preventDefault();
            alert('Dodajte bar jednu stavku!');
            return;
        }

        const itemsData = Array.from(rows).map(row => ({
            name: row.querySelector('span:nth-child(1)').textContent,
            quantity: parseFloat(row.querySelector('span:nth-child(2)').textContent),
            price: parseFloat(row.querySelector('span:nth-child(3)').textContent)
        }));

        let hiddenInput = document.querySelector('input[name="items_data"]');
        if(!hiddenInput){
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'items_data';
            form.appendChild(hiddenInput);
        }
        hiddenInput.value = JSON.stringify(itemsData);
    });

    // --- Dropdown handling ---
    document.addEventListener('click', function(e) {
        if (!suggestions.contains(e.target) && e.target !== searchInput) suggestions.style.display = 'none';
    });
    searchInput.addEventListener('blur', function() {
        setTimeout(() => { suggestions.style.display = 'none'; }, 150);
    });
    searchInput.addEventListener('keydown', function(e) {
        if(e.key === 'Escape') suggestions.style.display = 'none';
    });
    </script>
</body>
</html>

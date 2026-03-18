<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contract - FiscalizationME</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f9fafb;
            color: #111827;
        }
        
        .page-container {
            max-width: 1000px;
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
        
        .form-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 32px;
        }
        
        .form-section {
            margin-bottom: 32px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        input, select {
            height: 40px;
            padding: 0 12px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            font-size: 14px;
            background: white;
            color: #111827;
            transition: all 0.2s;
        }
        
        input:hover, select:hover {
            border-color: #d1d5db;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
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
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 10;
            margin-top: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .suggestions div {
            padding: 10px 12px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 13px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .suggestions div:last-child {
            border-bottom: none;
        }
        
        .suggestions div:hover {
            background: #f9fafb;
        }
        
        .suggestions div:first-child {
            font-weight: 600;
            color: #6366f1;
        }
        
        .add-item-row {
            display: grid;
            grid-template-columns: 1fr 100px 130px auto;
            gap: 10px;
            align-items: end;
            padding: 16px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            margin-bottom: 16px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        .items-table thead th {
            padding: 8px 12px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table tbody tr {
            border-bottom: 1px solid #f3f4f6;
        }

        .items-table tbody tr:last-child {
            border-bottom: none;
        }

        .items-table tbody td {
            padding: 10px 12px;
            font-size: 13px;
            color: #374151;
        }

        .items-table tbody td:first-child {
            font-weight: 500;
            color: #111827;
        }

        .items-table-wrap {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
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
            justify-content: center;
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
        
        .btn-success {
            background: #10b981;
            color: white;
            height: 40px;
            white-space: nowrap;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-danger {
            background: transparent;
            color: #9ca3af;
            width: 28px;
            height: 28px;
            padding: 0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .btn-danger:hover {
            background: #fee2e2;
            color: #ef4444;
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }
        
        .form-actions .btn-primary {
            flex: 1;
            height: 40px;
        }
        
        @media (max-width: 768px) {
            .page-container {
                padding: 16px;
            }
            
            .form-card {
                padding: 20px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .item-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    @include('partials.admin-navbar')
    
    <div class="page-container">
        <div class="page-header">
            <h1 class="page-title">New Contract</h1>
            <p class="page-subtitle">Create a new contract with buyer and products</p>
        </div>

        <div class="form-card">
            <form method="POST" action="{{ route('contracts.store') }}">
                @csrf

                <div class="form-section">
                    <h3 class="section-title">Basic Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Contract Number</label>
                            <input type="text" name="contract_number" required>
                        </div>

                        <div class="form-group">
                            <label>Company</label>
                            <select name="company_id" required>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Buyer</label>
                            <select name="buyer_id" required>
                                @foreach($buyers as $buyer)
                                    <option value="{{ $buyer->id }}">{{ $buyer->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="active">Active</option>
                                <option value="paused">Paused</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" required>
                        </div>

                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" required>
                        </div>

                        <div class="form-group">
                            <label>Billing Frequency</label>
                            <select name="billing_frequency">
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Issue Day</label>
                            <input type="number" name="issue_day" min="1" max="31" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Contract Items</h3>

                    <div class="add-item-row">
                        <div class="form-group items-autocomplete" style="margin:0;">
                            <label>Product Name</label>
                            <input type="text" id="product-search" placeholder="Search products...">
                            <div id="suggestions" class="suggestions" style="display:none;"></div>
                        </div>

                        <div class="form-group" style="margin:0;">
                            <label>Quantity</label>
                            <input type="number" id="product-quantity" step="0.01" value="1">
                        </div>

                        <div class="form-group" style="margin:0;">
                            <label>Price</label>
                            <input type="number" id="product-price" step="0.01" value="0">
                        </div>

                        <div style="padding-bottom: 0;">
                            <button type="button" class="btn btn-success" onclick="addItem()" style="margin-top: 22px;">+ Add</button>
                        </div>
                    </div>

                    <div id="items-container-wrap" style="display:none;" class="items-table-wrap">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Total (with VAT)</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="items-container"></tbody>
                        </table>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Contract</button>
                    <a href="{{ route('contracts.index') }}" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
    let products = @json($products); 
    const searchInput = document.getElementById('product-search');
    const suggestions = document.getElementById('suggestions');

    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        suggestions.innerHTML = '';
        if (!query) return suggestions.style.display='none';

        const filtered = products.filter(p => p.name.toLowerCase().includes(query));

        if(filtered.length === 0){
            const div = document.createElement('div');
            div.textContent = `Add new product: "${searchInput.value}"`;
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
                div.textContent = `${p.name} (VAT ${p.vatRate.percentage}%)`;
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

    function addItem() {
        const name = searchInput.value.trim();
        const quantity = parseFloat(document.getElementById('product-quantity').value) || 1;
        const price = parseFloat(document.getElementById('product-price').value) || 0;
        const vatPercentage = parseFloat(searchInput.dataset.vat) || 0;

        if (!name) return alert('Enter product name');

        const totalPrice = quantity * price;
        const totalPriceWithVat = totalPrice + (totalPrice * vatPercentage / 100);

        const container = document.getElementById('items-container');
        const wrap = document.getElementById('items-container-wrap');
        const row = document.createElement('tr');
        row.classList.add('item-row');

        row.innerHTML = `
            <td><span>${name}</span></td>
            <td><span>${quantity}</span></td>
            <td><span>${price.toFixed(2)}</span></td>
            <td><span>${totalPriceWithVat.toFixed(2)} (with VAT)</span></td>
            <td><button type="button" class="btn btn-danger" onclick="this.closest('tr').remove(); updateTableVisibility()">✕</button></td>
        `;

        container.appendChild(row);
        wrap.style.display = 'block';

        searchInput.value = '';
        document.getElementById('product-quantity').value = 1;
        document.getElementById('product-price').value = 0;
        suggestions.style.display = 'none';
    }

    function updateTableVisibility() {
        const container = document.getElementById('items-container');
        const wrap = document.getElementById('items-container-wrap');
        wrap.style.display = container.querySelectorAll('tr').length > 0 ? 'block' : 'none';
    }

    const form = document.querySelector('form');
    form.addEventListener('submit', function(e){
        const container = document.getElementById('items-container');
        const rows = container.querySelectorAll('tr.item-row');
        if(rows.length === 0){
            e.preventDefault();
            alert('Add at least one item!');
            return;
        }

        const itemsData = Array.from(rows).map(row => ({
            name: row.querySelector('td:nth-child(1) span').textContent,
            quantity: parseFloat(row.querySelector('td:nth-child(2) span').textContent),
            price: parseFloat(row.querySelector('td:nth-child(3) span').textContent)
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

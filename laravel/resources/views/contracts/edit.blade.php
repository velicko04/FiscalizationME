<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Contract - FiscalizationME</title>
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
        
        .page-header { margin-bottom: 24px; }
        
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
        
        .form-section { margin-bottom: 32px; }
        
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
        
        .form-group { display: flex; flex-direction: column; }
        
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
        
        input:hover, select:hover { border-color: #d1d5db; }
        
        input:focus, select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }

        .field-locked {
            background: #f9fafb !important;
            color: #6b7280 !important;
            cursor: not-allowed;
        }

        .items-autocomplete { position: relative; }
        
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
        .suggestions div:last-child { border-bottom: none; }
        .suggestions div:hover { background: #f9fafb; }
        
        .add-item-row {
            display: grid;
            grid-template-columns: 1fr 80px 110px 130px auto;
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

        .items-table tbody tr { border-bottom: 1px solid #f3f4f6; }
        .items-table tbody tr:last-child { border-bottom: none; }

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
        
        .btn-primary { background: #6366f1; color: white; }
        .btn-primary:hover { background: #4f46e5; }
        .btn-secondary { background: white; color: #374151; border: 1px solid #e5e7eb; text-decoration: none; }
        .btn-secondary:hover { background: #f9fafb; }
        .btn-success { background: #10b981; color: white; height: 40px; white-space: nowrap; }
        .btn-success:hover { background: #059669; }
        .btn-danger {
            background: transparent;
            color: #9ca3af;
            width: 28px;
            height: 28px;
            padding: 0;
            border-radius: 6px;
            font-size: 14px;
        }
        .btn-danger:hover { background: #fee2e2; color: #ef4444; }
        
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }
        .form-actions .btn-primary { flex: 1; height: 40px; }

        @media (max-width: 768px) {
            .page-container { padding: 16px; }
            .form-card { padding: 20px; }
            .form-grid { grid-template-columns: 1fr; }
            .add-item-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    @include('partials.admin-navbar')

    <div class="page-container">
        <div class="page-header">
            <h1 class="page-title">Edit Contract {{ $contract->contract_number }}</h1>
            <p class="page-subtitle">Update contract details and products</p>
        </div>

        <div class="form-card">

            @if ($errors->any())
            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; margin-bottom: 24px; color: #dc2626;">
                <strong>Greške:</strong>
                <ul style="margin-top: 8px; padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form id="contract-form" method="POST" action="{{ route('contracts.update', $contract->id) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="items_data" id="items_data_input" value="">

                {{-- Zaključana polja kao hidden --}}
                <input type="hidden" name="company_id" value="{{ $contract->company_id }}">
                <input type="hidden" name="buyer_id" value="{{ $contract->buyer_id }}">
                <input type="hidden" name="start_date" value="{{ \Carbon\Carbon::parse($contract->start_date)->format('Y-m-d') }}">
                <input type="hidden" name="end_date" value="{{ \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') }}">

                <div class="form-section">
                    <h3 class="section-title">Basic Information</h3>
                    <div class="form-grid">

                        <div class="form-group">
                            <label>Contract Number</label>
                            <input type="text" value="{{ $contract->contract_number }}" disabled class="field-locked">
                        </div>

                        <div class="form-group">
                            <label>Company</label>
                            <input type="text" value="{{ $contract->company->name }}" disabled class="field-locked">
                        </div>

                        <div class="form-group">
                            <label>Buyer</label>
                            <input type="text" value="{{ $contract->buyer->name }}" disabled class="field-locked">
                        </div>

                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="active" {{ $contract->status == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="paused" {{ $contract->status == 'paused' ? 'selected' : '' }}>Paused</option>
                                <option value="expired" {{ $contract->status == 'expired' ? 'selected' : '' }}>Expired</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" value="{{ \Carbon\Carbon::parse($contract->start_date)->format('Y-m-d') }}" disabled class="field-locked">
                        </div>

                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" value="{{ \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') }}" disabled class="field-locked">
                        </div>

                        <div class="form-group">
                            <label>Billing Frequency</label>
                            <select name="billing_frequency">
                                <option value="monthly" {{ $contract->billing_frequency == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="quarterly" {{ $contract->billing_frequency == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                <option value="yearly" {{ $contract->billing_frequency == 'yearly' ? 'selected' : '' }}>Yearly</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Issue Day</label>
                            <input type="number" name="issue_day" min="1" max="31" value="{{ $contract->issue_day }}" required>
                        </div>
                        <div class="form-group">
                            <label>Type of Invoice</label>
                            <select name="default_type_of_invoice">
                                <option value="NONCASH" {{ $contract->default_type_of_invoice == 'NONCASH' ? 'selected' : '' }}>NONCASH</option>
                                <option value="CASH" {{ $contract->default_type_of_invoice == 'CASH' ? 'selected' : '' }}>CASH</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Payment Method</label>
                            <select name="default_payment_method">
                                <option value="ACCOUNT" {{ $contract->default_payment_method == 'ACCOUNT' ? 'selected' : '' }}>ACCOUNT</option>
                                <option value="CARD" {{ $contract->default_payment_method == 'CARD' ? 'selected' : '' }}>CARD</option>
                                <option value="BANKNOTE" {{ $contract->default_payment_method == 'BANKNOTE' ? 'selected' : '' }}>BANKNOTE</option>
                                <option value="OTHER" {{ $contract->default_payment_method == 'OTHER' ? 'selected' : '' }}>OTHER</option>
                                <option value="VOUCHER" {{ $contract->default_payment_method == 'VOUCHER' ? 'selected' : '' }}>VOUCHER</option>
                                <option value="COMPENSATION" {{ $contract->default_payment_method == 'COMPENSATION' ? 'selected' : '' }}>COMPENSATION</option>
                            </select>
                        </div>

                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Contract Items</h3>

                    <div class="add-item-row">
                        <div class="form-group items-autocomplete" style="margin:0;">
                            <label>Product Name</label>
                            <input type="text" id="product-search" placeholder="Search products..." autocomplete="off">
                            <div id="suggestions" class="suggestions" style="display:none;"></div>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label>Qty</label>
                            <input type="number" id="product-quantity" step="1" value="1" min="1">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label>Price</label>
                            <input type="number" id="product-price" step="0.01" value="0" min="0">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label>VAT Stopa</label>
                            <select id="product-vat-rate">
                                <option value="1" data-percentage="21">Standard 21%</option>
                                <option value="2" data-percentage="7">Reduced 7%</option>
                                <option value="3" data-percentage="0">Exempt 0%</option>
                            </select>
                        </div>
                        <div style="padding-bottom: 0;">
                            <button type="button" id="add-item-btn" class="btn btn-success" style="margin-top: 22px;">+ Add</button>
                        </div>
                    </div>

                    <div id="items-container-wrap" class="items-table-wrap">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>VAT</th>
                                    <th>Total (with VAT)</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="items-container">
                                @foreach($contract->items as $item)
                                <tr class="item-row"
                                    data-name="{{ $item->product->name }}"
                                    data-quantity="{{ $item->quantity }}"
                                    data-price="{{ $item->unit_price }}"
                                    data-vat-rate-id="{{ $item->vat_rate_id }}">
                                    <td><span>{{ $item->product->name }}</span></td>
                                    <td><span>{{ $item->quantity }}</span></td>
                                    <td><span>{{ number_format($item->unit_price, 2) }}</span></td>
                                    <td><span>{{ $item->product->vatRate->percentage ?? 0 }}%</span></td>
                                    <td><span>{{ number_format($item->quantity * $item->unit_price * (1 + ($item->product->vatRate->percentage ?? 0) / 100), 2) }}</span></td>
                                    <td><button type="button" class="btn btn-danger" onclick="removeItem(this)">✕</button></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('contracts.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    var products = @json($products);
    var selectedProduct = null;

    var searchInput = document.getElementById('product-search');
    var suggestionsBox = document.getElementById('suggestions');
    var itemsContainer = document.getElementById('items-container');
    var itemsWrap = document.getElementById('items-container-wrap');
    var contractForm = document.getElementById('contract-form');
    var itemsDataInput = document.getElementById('items_data_input');
    var addItemBtn = document.getElementById('add-item-btn');
    var vatSelect = document.getElementById('product-vat-rate');

    updateTableVisibility();

    searchInput.addEventListener('input', function() {
        var query = this.value.toLowerCase().trim();
        suggestionsBox.innerHTML = '';
        selectedProduct = null;

        if (!query) { suggestionsBox.style.display = 'none'; return; }

        var filtered = products.filter(function(p) {
            return p.name.toLowerCase().indexOf(query) !== -1;
        });

        if (filtered.length === 0) {
            var div = document.createElement('div');
            div.textContent = '+ Dodaj novi: "' + searchInput.value + '"';
            div.style.fontWeight = 'bold';
            div.style.color = '#6366f1';
            div.addEventListener('mousedown', function(e) {
                e.preventDefault();
                var newName = searchInput.value.trim();
                var newPrice = parseFloat(document.getElementById('product-price').value) || 0;
                var selectedVatOption = vatSelect.options[vatSelect.selectedIndex];
                var newVatRateId = parseInt(selectedVatOption.value);
                fetch("{{ route('products.ajaxStore') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ name: newName, price: newPrice, vat_rate_id: newVatRateId, unit: 'kom' })
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) { products.push(data.product); selectProduct(data.product); }
                });
                suggestionsBox.style.display = 'none';
            });
            suggestionsBox.appendChild(div);
        } else {
            filtered.forEach(function(p) {
                var div = document.createElement('div');
                div.textContent = p.name + ' (VAT ' + p.vatRate.percentage + '%)';
                div.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    selectProduct(p);
                    suggestionsBox.style.display = 'none';
                });
                suggestionsBox.appendChild(div);
            });
        }

        suggestionsBox.style.display = 'block';
    });

    function selectProduct(product) {
        selectedProduct = product;
        searchInput.value = product.name;
        document.getElementById('product-price').value = parseFloat(product.price).toFixed(2);
        for (var i = 0; i < vatSelect.options.length; i++) {
            if (parseInt(vatSelect.options[i].value) === parseInt(product.vat_rate_id)) {
                vatSelect.selectedIndex = i;
                break;
            }
        }
    }

    addItemBtn.addEventListener('click', function() {
        var name = searchInput.value.trim();
        var quantity = parseFloat(document.getElementById('product-quantity').value) || 1;
        var price = parseFloat(document.getElementById('product-price').value) || 0;

        if (!name) { alert('Unesite naziv proizvoda.'); return; }

        var selectedVatOption = vatSelect.options[vatSelect.selectedIndex];
        var vatPercentage = selectedProduct ? parseFloat(selectedProduct.vatRate.percentage) : parseFloat(selectedVatOption.dataset.percentage);
        var vatRateId = selectedProduct ? (parseInt(selectedProduct.vat_rate_id) || 1) : parseInt(selectedVatOption.value);
        var totalPriceWithVat = (quantity * price) * (1 + vatPercentage / 100);

        var row = document.createElement('tr');
        row.classList.add('item-row');
        row.dataset.name = name;
        row.dataset.quantity = quantity;
        row.dataset.price = price;
        row.dataset.vatRateId = vatRateId;

        row.innerHTML =
            '<td><span>' + name + '</span></td>' +
            '<td><span>' + quantity + '</span></td>' +
            '<td><span>' + price.toFixed(2) + '</span></td>' +
            '<td><span>' + vatPercentage + '%</span></td>' +
            '<td><span>' + totalPriceWithVat.toFixed(2) + '</span></td>' +
            '<td><button type="button" class="btn btn-danger" onclick="removeItem(this)">✕</button></td>';

        itemsContainer.appendChild(row);
        updateTableVisibility();

        searchInput.value = '';
        document.getElementById('product-quantity').value = 1;
        document.getElementById('product-price').value = 0;
        vatSelect.selectedIndex = 0;
        selectedProduct = null;
        suggestionsBox.style.display = 'none';
    });

    function removeItem(btn) {
        btn.closest('tr').remove();
        updateTableVisibility();
    }

    function updateTableVisibility() {
        itemsWrap.style.display = itemsContainer.querySelectorAll('tr.item-row').length > 0 ? 'block' : 'none';
    }

    contractForm.addEventListener('submit', function(e) {
        var rows = itemsContainer.querySelectorAll('tr.item-row');
        if (rows.length === 0) {
            e.preventDefault();
            alert('Dodajte barem jedan proizvod.');
            return;
        }

        var itemsData = [];
        rows.forEach(function(row) {
            itemsData.push({
                name: row.dataset.name,
                quantity: parseFloat(row.dataset.quantity),
                price: parseFloat(row.dataset.price),
                vat_rate_id: parseInt(row.dataset.vatRateId) || 1
            });
        });

        itemsDataInput.value = JSON.stringify(itemsData);
    });

    searchInput.addEventListener('blur', function() {
        setTimeout(function() { suggestionsBox.style.display = 'none'; }, 200);
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') suggestionsBox.style.display = 'none';
    });
    </script>
</body>
</html>
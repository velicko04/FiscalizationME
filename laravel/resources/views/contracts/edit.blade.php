<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Ugovor</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        
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
        
        .product-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 16px;
            margin-bottom: 16px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            align-items: end;
            transition: all 0.3s ease;
        }
        
        .product-row:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102,126,234,0.15);
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
        
        .form-actions .btn {
            flex: 1;
            height: 56px;
            font-size: 16px;
        }
        
        .form-actions a {
            text-decoration: none;
            flex: 1;
        }
        
        @media (max-width: 768px) {
            body { padding: 20px 16px; }
            .form-card { padding: 28px 20px; }
            .page-header h1 { font-size: 28px; }
            .form-grid { grid-template-columns: 1fr; }
            .product-row { grid-template-columns: 1fr; padding: 16px; }
            .form-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="page-header">
            <h1>✏️ Edit ugovor {{ $contract->contract_number }}</h1>
        </div>

        <div class="form-card">
            <form method="POST" action="{{ route('contracts.update', $contract->id) }}">
                @csrf
                @method('PUT')

                <div class="form-section">
                    <h3 class="section-title">Osnovne informacije</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Status:</label>
                            <select name="status">
                                <option value="active" {{ $contract->status == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="expired" {{ $contract->status == 'expired' ? 'selected' : '' }}>Expired</option>
                                <option value="paused" {{ $contract->status == 'paused' ? 'selected' : '' }}>Paused</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Billing frequency:</label>
                            <select name="billing_frequency">
                                <option value="monthly" {{ $contract->billing_frequency == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="quarterly" {{ $contract->billing_frequency == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                <option value="yearly" {{ $contract->billing_frequency == 'yearly' ? 'selected' : '' }}>Yearly</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Start date:</label>
                            <input type="date" name="start_date" value="{{ $contract->start_date }}">
                        </div>

                        <div class="form-group">
                            <label>End date:</label>
                            <input type="date" name="end_date" value="{{ $contract->end_date }}">
                        </div>

                        <div class="form-group">
                            <label>Issue day:</label>
                            <input type="number" name="issue_day" value="{{ $contract->issue_day }}" min="1" max="31">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Proizvodi</h3>

                    <div id="products-wrapper">
                        @foreach($contract->items as $item)
                        <div class="product-row">
                            <select name="products[]" class="product-select">
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}"
                                            data-price="{{ $product->price }}"
                                            {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>

                            <input type="number" step="0.01" name="quantities[]" value="{{ $item->quantity }}">
                            <input type="number" step="0.01" name="prices[]" value="{{ $item->unit_price }}" class="price-input">
                            <button type="button" class="btn btn-danger remove-product">✕</button>
                        </div>
                        @endforeach
                    </div>

                    <button type="button" id="add-product-btn" class="btn btn-add">+ Dodaj proizvod</button>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Sačuvaj izmjene</button>
                    <a href="{{ route('contracts.index') }}">
                        <button type="button" class="btn btn-secondary">⬅ Nazad</button>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        const wrapper = document.getElementById('products-wrapper');
        const addBtn = document.getElementById('add-product-btn');

        function attachPriceListener(selectElement) {
            selectElement.addEventListener('change', function () {
                const selectedOption = this.options[this.selectedIndex];
                const price = selectedOption.getAttribute('data-price');
                const row = this.closest('.product-row');
                row.querySelector('.price-input').value = price;
            });
        }

        document.querySelectorAll('.product-select').forEach(select => {
            attachPriceListener(select);
        });

        addBtn.addEventListener('click', function () {
            const row = document.createElement('div');
            row.classList.add('product-row');

            row.innerHTML = `
                <select name="products[]" class="product-select">
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" data-price="{{ $product->price }}">
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>

                <input type="number" step="0.01" name="quantities[]" value="1">
                <input type="number" step="0.01" name="prices[]" value="0" class="price-input">
                <button type="button" class="btn btn-danger remove-product">✕</button>
            `;

            wrapper.appendChild(row);

            attachPriceListener(row.querySelector('.product-select'));
        });

        wrapper.addEventListener('click', function(e) {
            if(e.target.classList.contains('remove-product')) {
                e.target.closest('.product-row').remove();
            }
        });
    });
    </script>
</body>
</html>

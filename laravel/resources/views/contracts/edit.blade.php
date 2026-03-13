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
        
        .product-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 12px;
            margin-bottom: 12px;
            padding: 16px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            align-items: end;
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
            width: 100%;
            margin-top: 12px;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
            width: 36px;
            height: 36px;
            padding: 0;
        }
        
        .btn-danger:hover {
            background: #dc2626;
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
            
            .product-row {
                grid-template-columns: 1fr;
            }
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
            <form method="POST" action="{{ route('contracts.update', $contract->id) }}">
                @csrf
                @method('PUT')

                <div class="form-section">
                    <h3 class="section-title">Basic Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="active" {{ $contract->status == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="expired" {{ $contract->status == 'expired' ? 'selected' : '' }}>Expired</option>
                                <option value="paused" {{ $contract->status == 'paused' ? 'selected' : '' }}>Paused</option>
                            </select>
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
                            <label>Start Date</label>
                            <input type="date" name="start_date" value="{{ $contract->start_date }}">
                        </div>

                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" value="{{ $contract->end_date }}">
                        </div>

                        <div class="form-group">
                            <label>Issue Day</label>
                            <input type="number" name="issue_day" value="{{ $contract->issue_day }}" min="1" max="31">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Products</h3>

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

                    <button type="button" id="add-product-btn" class="btn btn-success">+ Add Product</button>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('contracts.index') }}" class="btn btn-secondary">
                        Cancel
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

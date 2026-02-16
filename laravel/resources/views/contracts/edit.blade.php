<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background-color: #f9fafb;
        color: #111827;
        padding: 24px;
    }

    .form-container {
        max-width: 900px;
        margin: 0 auto;
    }

    .page-header {
        margin-bottom: 32px;
    }

    .page-header h1 {
        font-size: 28px;
        font-weight: 600;
        color: #111827;
    }

    .form-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        padding: 32px;
    }

    .form-section {
        margin-bottom: 32px;
    }

    .form-section:last-child {
        margin-bottom: 0;
    }

    .section-title {
        font-size: 18px;
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
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        margin-bottom: 8px;
    }

    input[type="text"],
    input[type="date"],
    input[type="number"],
    select {
        height: 42px;
        padding: 0 14px;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        font-size: 14px;
        background: white;
        color: #374151;
        transition: all 0.2s;
    }

    input:focus,
    select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .product-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr auto;
        gap: 12px;
        margin-bottom: 12px;
        align-items: end;
    }

    .product-row select,
    .product-row input {
        height: 42px;
    }

    .btn {
        height: 42px;
        padding: 0 20px;
        border-radius: 8px;
        border: none;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-primary {
        background: #3b82f6;
        color: white;
    }

    .btn-primary:hover {
        background: #2563eb;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
    }

    .btn-secondary {
        background: white;
        color: #374151;
        border: 1px solid #d1d5db;
    }

    .btn-secondary:hover {
        background: #f9fafb;
        border-color: #9ca3af;
    }

    .btn-danger {
        background: #ef4444;
        color: white;
        width: 42px;
        padding: 0;
    }

    .btn-danger:hover {
        background: #dc2626;
    }

    .btn-add {
        margin-top: 12px;
        background: #10b981;
        color: white;
    }

    .btn-add:hover {
        background: #059669;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid #e5e7eb;
    }

    @media (max-width: 768px) {
        body {
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

        .product-row button {
            width: 100%;
        }
    }
</style>

<div class="form-container">
    <div class="page-header">
        <h1>Edit ugovor {{ $contract->contract_number }}</h1>
    </div>

    <div class="form-card">
        <form method="POST" action="{{ route('contracts.update', $contract->id) }}">
            @csrf
            @method('PUT')

            <div class="form-section">
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
                    @foreach($contract->items as $index => $item)
                    <div class="product-row">
                        <select name="products[]">
                            @foreach($products as $product)
                                <option value="{{ $product->id }}"
                                    {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>

                        <input type="number" step="0.01" name="quantities[]" value="{{ $item->quantity }}" placeholder="Quantity">

                        <input type="number" step="0.01" name="prices[]" value="{{ $item->unit_price }}" placeholder="Unit price">

                        <button type="button" class="btn btn-danger remove-product">✕</button>
                    </div>
                    @endforeach
                </div>

                <button type="button" id="add-product-btn" class="btn btn-add">+ Dodaj proizvod</button>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Sačuvaj izmjene</button>
                <a href="{{ route('contracts.index') }}">
                    <button type="button" class="btn btn-secondary">⬅ Nazad</button>
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const addBtn = document.getElementById('add-product-btn');
    const wrapper = document.getElementById('products-wrapper');

    addBtn.addEventListener('click', function () {
        const row = document.createElement('div');
        row.classList.add('product-row');

        row.innerHTML = `
            <select name="products[]">
                @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>

            <input type="number" step="0.01" name="quantities[]" value="1" placeholder="Quantity">
            <input type="number" step="0.01" name="prices[]" value="0" placeholder="Unit price">
            <button type="button" class="btn btn-danger remove-product">✕</button>
        `;
        wrapper.appendChild(row);
    });

    wrapper.addEventListener('click', function(e) {
        if(e.target.classList.contains('remove-product')) {
            e.target.closest('.product-row').remove();
        }
    });
});
</script>

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

    .form-group.full-width {
        grid-column: 1 / -1;
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

    .item-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr auto;
        gap: 12px;
        margin-bottom: 12px;
        align-items: end;
    }

    .item-row select,
    .item-row input {
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
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #d1d5db;
    }

    .btn-secondary:hover {
        background: #e5e7eb;
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

        .item-row {
            grid-template-columns: 1fr;
        }

        .item-row button {
            width: 100%;
        }
    }
</style>

<div class="form-container">
    <div class="page-header">
        <h1>Novi Ugovor</h1>
    </div>

    <div class="form-card">
        <form method="POST" action="{{ route('contracts.store') }}">
            @csrf

            <div class="form-section">
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

            <div class="form-section">
                <h3 class="section-title">Stavke ugovora</h3>

                <div id="items-container">
                    <div class="item-row">
                        <select name="items[0][product_id]" required>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">
                                    {{ $product->name }} (PDV {{ $product->vatRate->percentage }}%)
                                </option>
                            @endforeach
                        </select>

                        <input type="number" step="0.01" name="items[0][quantity]" placeholder="Količina" required>

                        <input type="number" step="0.01" name="items[0][unit_price]" placeholder="Cijena" required>

                        <button type="button" class="btn btn-danger" onclick="removeItem(this)">✕</button>
                    </div>
                </div>

                <button type="button" class="btn btn-add" onclick="addItem()">+ Dodaj stavku</button>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Sačuvaj ugovor</button>
            </div>
        </form>
    </div>
</div>


<script>
let itemIndex = 1;

function addItem() {
    const container = document.getElementById('items-container');

    const newItem = document.createElement('div');
    newItem.classList.add('item-row');

    newItem.innerHTML = `
        <select name="items[${itemIndex}][product_id]" required>
            @foreach($products as $product)
                <option value="{{ $product->id }}">
                    {{ $product->name }} (PDV {{ $product->vatRate->percentage }}%)
                </option>
            @endforeach
        </select>

        <input type="number" step="0.01" name="items[${itemIndex}][quantity]" placeholder="Količina" required>

        <input type="number" step="0.01" name="items[${itemIndex}][unit_price]" placeholder="Cijena" required>

        <button type="button" class="btn btn-danger" onclick="removeItem(this)">✕</button>
    `;

    container.appendChild(newItem);
    itemIndex++;
}

function removeItem(button) {
    button.parentElement.remove();
}
</script>

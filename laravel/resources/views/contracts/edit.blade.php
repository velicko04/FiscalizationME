<h1>Edit ugovor {{ $contract->contract_number }}</h1>

<form method="POST" action="{{ route('contracts.update', $contract->id) }}">
    @csrf
    @method('PUT')

    <label>Status:</label>
    <select name="status">
        <option value="active" {{ $contract->status == 'active' ? 'selected' : '' }}>Active</option>
        <option value="expired" {{ $contract->status == 'expired' ? 'selected' : '' }}>Expired</option>
        <option value="paused" {{ $contract->status == 'paused' ? 'selected' : '' }}>Paused</option>
    </select>

    <br><br>

    <label>Start date:</label>
    <input type="date" name="start_date" value="{{ $contract->start_date }}">

    <br><br>

    <label>End date:</label>
    <input type="date" name="end_date" value="{{ $contract->end_date }}">

    <br><br>

    <label>Billing frequency:</label>
    <select name="billing_frequency">
        <option value="monthly" {{ $contract->billing_frequency == 'monthly' ? 'selected' : '' }}>Monthly</option>
        <option value="quarterly" {{ $contract->billing_frequency == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
        <option value="yearly" {{ $contract->billing_frequency == 'yearly' ? 'selected' : '' }}>Yearly</option>
    </select>

    <br><br>

    <label>Issue day:</label>
    <input type="number" name="issue_day" value="{{ $contract->issue_day }}" min="1" max="31">

    <hr>

    <h3>Proizvodi</h3>

    <div id="products-wrapper">
        @foreach($contract->items as $index => $item)
        <div class="product-row" style="margin-bottom:10px;">
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

            <button type="button" class="remove-product">X</button>
        </div>
        @endforeach
    </div>

    <button type="button" id="add-product-btn" style="margin-top:10px;">Dodaj proizvod</button>

    <br><br>

    <button type="submit">Sačuvaj izmjene</button>
</form>

<br>

<a href="{{ route('contracts.index') }}">
    <button>⬅ Nazad</button>
</a>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const addBtn = document.getElementById('add-product-btn');
    const wrapper = document.getElementById('products-wrapper');

    addBtn.addEventListener('click', function () {
        const row = document.createElement('div');
        row.classList.add('product-row');
        row.style.marginBottom = '10px';

        row.innerHTML = `
            <select name="products[]">
                @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>

            <input type="number" step="0.01" name="quantities[]" value="1" placeholder="Quantity">
            <input type="number" step="0.01" name="prices[]" value="0" placeholder="Unit price">
            <button type="button" class="remove-product">Ukloni</button>
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

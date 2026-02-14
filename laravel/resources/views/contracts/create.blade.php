<h1>Novi Ugovor</h1>

<form method="POST" action="{{ route('contracts.store') }}">
    @csrf

    <label>Broj ugovora:</label>
    <input type="text" name="contract_number" required>
    <br><br>

    <label>Kompanija:</label>
    <select name="company_id" required>
        @foreach($companies as $company)
            <option value="{{ $company->id }}">{{ $company->name }}</option>
        @endforeach
    </select>
    <br><br>

    <label>Kupac:</label>
    <select name="buyer_id" required>
        @foreach($buyers as $buyer)
            <option value="{{ $buyer->id }}">{{ $buyer->name }}</option>
        @endforeach
    </select>
    <br><br>

    <label>Početak:</label>
    <input type="date" name="start_date" required>
    <br><br>

    <label>Kraj:</label>
    <input type="date" name="end_date" required>
    <br><br>

    <label>Billing frequency:</label>
    <select name="billing_frequency">
        <option value="monthly">Monthly</option>
        <option value="quarterly">Quarterly</option>
        <option value="yearly">Yearly</option>
    </select>
    <br><br>

    <label>Dan izdavanja:</label>
    <input type="number" name="issue_day" min="1" max="31" required>
    <br><br>

    <label>Status:</label>
    <select name="status">
        <option value="active">Active</option>
        <option value="paused">Paused</option>
        <option value="expired">Expired</option>
    </select>
    <br><br>

    <h3>Stavke ugovora</h3>

    <div id="items-container">
        <div class="item-row" style="margin-bottom:10px;">
            <select name="items[0][product_id]" required>
                @foreach($products as $product)
                    <option value="{{ $product->id }}">
                        {{ $product->name }} (PDV {{ $product->vatRate->percentage }}%)
                    </option>
                @endforeach
            </select>

            <input type="number" step="0.01" name="items[0][quantity]" placeholder="Količina" required>

            <input type="number" step="0.01" name="items[0][unit_price]" placeholder="Cijena" required>

            <button type="button" onclick="removeItem(this)">X</button>
        </div>
    </div>

    <button type="button" onclick="addItem()">+ Dodaj stavku</button>

    <br><br>
    <button type="submit">Sačuvaj ugovor</button>
</form>


<script>
let itemIndex = 1;

function addItem() {
    const container = document.getElementById('items-container');

    const newItem = document.createElement('div');
    newItem.classList.add('item-row');
    newItem.style.marginBottom = "10px";

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

        <button type="button" onclick="removeItem(this)">X</button>
    `;

    container.appendChild(newItem);
    itemIndex++;
}

function removeItem(button) {
    button.parentElement.remove();
}
</script>

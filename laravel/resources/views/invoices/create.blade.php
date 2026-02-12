<h1>Dodaj novi račun</h1>

@if(session('success'))
    <p style="color:green">{{ session('success') }}</p>
@endif

<form action="{{ route('invoices.store') }}" method="POST">
    @csrf

    <label>Broj računa:</label>
    <input type="text" name="invoice_number" required><br><br>

    <label>Kompanija:</label>
    <select name="company_id" required>
        @foreach($companies as $company)
            <option value="{{ $company->id }}">{{ $company->name }}</option>
        @endforeach
    </select><br><br>

    <label>Kupac:</label>
    <select name="buyer_id" required>
        @foreach($buyers as $buyer)
            <option value="{{ $buyer->id }}">{{ $buyer->name }}</option>
        @endforeach
    </select><br><br>

    <label>Prodavac:</label>
    <select name="user_id" required>
        @foreach($users as $user)
            <option value="{{ $user->id }}">{{ $user->name }}</option>
        @endforeach
    </select><br><br>

    <label>Datum izdavanja:</label>
    <input type="date" name="issued_at" required><br><br>

    <label>Metoda plaćanja:</label>
    <select name="payment_method_type" required>
        <option value="CASH">CASH</option>
        <option value="CARD">CARD</option>
    </select><br><br>

    <h3>Stavke računa</h3>
    <div id="items">
        <div class="item" style="margin-bottom:10px;">
            <select name="items[0][product_id]" required>
                @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>
            <input type="number" name="items[0][quantity]" placeholder="Količina" step="0.01" required>
            <input type="number" name="items[0][unit_price]" placeholder="Cena po jedinici" step="0.01" required>
        </div>
    </div>

    <button type="button" onclick="addItem()">Dodaj još stavku</button><br><br>

    <button type="submit">Sačuvaj račun</button>
</form>

<script>
let itemIndex = 1;

function addItem() {
    const div = document.createElement('div');
    div.classList.add('item');
    div.style.marginBottom = '10px';
    div.innerHTML = `
        <select name="items[${itemIndex}][product_id]" required>
            @foreach($products as $product)
                <option value="{{ $product->id }}">{{ $product->name }}</option>
            @endforeach
        </select>
        <input type="number" name="items[${itemIndex}][quantity]" placeholder="Količina" step="0.01" required>
        <input type="number" name="items[${itemIndex}][unit_price]" placeholder="Cena po jedinici" step="0.01" required>
    `;
    document.getElementById('items').appendChild(div);
    itemIndex++;
}
</script>

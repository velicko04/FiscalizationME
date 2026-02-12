<h1>Računi</h1>

<a href="{{ route('invoices.create') }}" 
   style="display:inline-block; padding:8px 15px; margin-bottom:10px; background-color: #32323357; color:white; text-decoration:none; border-radius:4px;">
    Dodaj novi račun
</a>



<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr>
            <th>Broj računa</th>
            <th>Kompanija</th>
            <th>Kupac</th>
            <th>Prodavac</th>
            <th>Datum izdavanja</th>
            <th>Ukupno (sa PDV)</th>
            <th>Ukupno PDV</th>
            <th>Metoda plaćanja</th>
            <th>Proizvodi</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoices as $invoice)
        <tr>
            <td>
                <a href="#">{{ $invoice->invoice_number }}</a>
            </td>
            <td>{{ $invoice->company->name }}</td>
            <td>{{ $invoice->buyer->name }}</td>
            <td>{{ $invoice->user->name }}</td>
            <td>{{ $invoice->issued_at }}</td>
            <td>{{ number_format($invoice->total_price_to_pay, 2) }}</td>
            <td>{{ number_format($invoice->total_vat_amount, 2) }}</td>
            <td>{{ $invoice->payment_method_type }}</td>
            <td>
                <ul>
                    @foreach($invoice->items as $item)
                        <li>{{ $item->product->name }} - {{ $item->quantity }} x {{ number_format($item->unit_price, 2) }} 
                            (VAT: {{ $item->product->vatRate ? $item->product->vatRate->percentage : 0 }}%)
                        </li>
                    @endforeach
                </ul>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

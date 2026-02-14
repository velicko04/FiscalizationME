<h1>Računi za ugovor {{ $contract->contract_number }}</h1>

<p><strong>Kompanija:</strong> {{ $contract->company->name }}</p>
<p><strong>Kupac:</strong> {{ $contract->buyer->name }}</p>

<br>

@if($invoices->count())
<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">
    <thead style="background:#f0f0f0;">
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
               {{ $invoice->invoice_number }}
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
                        <li>
                            {{ $item->product->name }} - {{ $item->quantity }} x {{ number_format($item->unit_price, 2) }} 
                            (PDV: {{ $item->product->vatRate ? $item->product->vatRate->percentage : 0 }}%)
                        </li>
                    @endforeach
                </ul>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
    <p>Nema računa za ovaj ugovor.</p>
@endif

<a href="{{ route('contracts.index') }}">
    <button style="margin-top: 15px;">⬅ Nazad na ugovore</button>
</a>


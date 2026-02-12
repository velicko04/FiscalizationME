

<h1>Detalji ugovora: {{ $contract->contract_number }}</h1>

<p><strong>Kompanija:</strong> {{ $contract->company->name }}</p>
<p><strong>Kupac:</strong> {{ $contract->buyer->name }}</p>
<p><strong>Početak:</strong> {{ $contract->start_date }}</p>
<p><strong>Kraj:</strong> {{ $contract->end_date }}</p>
<p><strong>Status:</strong> {{ $contract->status }}</p>
<p><strong>Billing frequency:</strong> {{ $contract->billing_frequency }}</p>

<h2>Proizvodi</h2>
<table border="1" cellpadding="5">
    <tr>
        <th>Proizvod</th>
        <th>Količina</th>
        <th>Cijena po jedinici</th>
        <th>VAT</th>
    </tr>
    @foreach($contract->items as $item)
    <tr>
        <td>{{ $item->product->name }}</td>
        <td>{{ $item->quantity }}</td>
        <td>{{ $item->unit_price }}</td>
        <td>{{ $item->product->vatRate->percentage }}%</td>
    </tr>
    @endforeach
</table>

<a href="{{ route('contracts.index') }}">Nazad na listu ugovora</a>


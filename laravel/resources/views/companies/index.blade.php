<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Companies - FiscalizationME</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f9fafb;
            color: #111827;
        }
        .page-container { max-width: 1400px; margin: 0 auto; padding: 32px; }
        .page-header { margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start; }
        .page-title { font-size: 28px; font-weight: 700; color: #111827; margin-bottom: 8px; }
        .page-subtitle { font-size: 14px; color: #6b7280; }
        .btn {
            height: 36px; padding: 0 16px; border-radius: 8px; font-size: 13px;
            font-weight: 500; cursor: pointer; transition: all 0.2s; border: none;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            text-decoration: none;
        }
        .btn-primary { background: #6366f1; color: white; }
        .btn-primary:hover { background: #4f46e5; }
        .btn-secondary { background: white; color: #374151; border: 1px solid #e5e7eb; }
        .btn-secondary:hover { background: #f9fafb; }
        .table-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
        thead th {
            padding: 12px 16px; text-align: left; font-size: 12px;
            font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;
        }
        tbody tr { border-bottom: 1px solid #f3f4f6; transition: background 0.2s; }
        tbody tr:hover { background: #f9fafb; }
        tbody tr:last-child { border-bottom: none; }
        tbody td { padding: 16px; font-size: 14px; color: #374151; vertical-align: middle; }
        tbody td:first-child { font-weight: 600; color: #111827; }
        .badge {
            display: inline-flex; align-items: center; padding: 2px 10px;
            border-radius: 999px; font-size: 12px; font-weight: 500;
        }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-gray { background: #f3f4f6; color: #6b7280; }
        .alert-success {
            background: #d1fae5; border: 1px solid #6ee7b7; border-radius: 8px;
            padding: 12px 16px; color: #065f46; font-size: 14px; margin-bottom: 24px;
        }
        .empty-state { padding: 80px 20px; text-align: center; }
        .empty-state-icon { font-size: 48px; margin-bottom: 16px; opacity: 0.5; }
        .empty-state p { font-size: 16px; color: #6b7280; }
    </style>
</head>
<body>
    @include('partials.admin-navbar')

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Companies</h1>
                <p class="page-subtitle">Manage all your companies</p>
            </div>
            <a href="{{ route('companies.create') }}" class="btn btn-primary">+ Add Company</a>
        </div>

        @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
        @endif
        <div style="margin-bottom: 16px;">
            <input type="text" id="company-search" placeholder="Search companies..." 
            style="height: 40px; padding: 0 12px; border-radius: 8px; border: 1px solid #e5e7eb; 
            font-size: 14px; width: 320px; background: white;">
        </div>
        @if($companies->count())
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Tax ID Type</th>
                        <th>Tax ID Number</th>
                        <th>City</th>
                        <th>ENU Code</th>
                        <th>Software Code</th>
                        <th>VAT</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($companies as $company)
                    <tr>
                        <td>{{ $company->name }}</td>
                        <td>{{ $company->tax_id_type->value }}</td>
                        <td>{{ $company->tax_id_number }}</td>
                        <td>{{ $company->city }}</td>
                        <td>{{ $company->enu_code }}</td>
                        <td>{{ $company->software_code }}</td>
                        <td>
                            @if($company->is_issuer_in_vat)
                                <span class="badge badge-green">Yes</span>
                            @else
                                <span class="badge badge-gray">No</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('companies.edit', $company->id) }}" class="btn btn-secondary">Edit</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="table-card">
            <div class="empty-state">
                <div class="empty-state-icon">🏢</div>
                <p>No companies found.</p>
            </div>
        </div>
        @endif
    </div>

    <script>
    document.getElementById('company-search').addEventListener('input', function() {
    var query = this.value.toLowerCase();
    var rows = document.querySelectorAll('tbody tr');
    rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        row.style.display = text.indexOf(query) !== -1 ? '' : 'none';
    });
});
</script>
</body>
</html>
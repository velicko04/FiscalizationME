<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Company - FiscalizationME</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f9fafb; color: #111827;
        }
        .page-container { max-width: 1000px; margin: 0 auto; padding: 32px; }
        .page-header { margin-bottom: 24px; }
        .page-title { font-size: 28px; font-weight: 700; color: #111827; margin-bottom: 8px; }
        .page-subtitle { font-size: 14px; color: #6b7280; }
        .form-card { background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 32px; margin-bottom: 24px; }
        .form-section { margin-bottom: 32px; }
        .section-title {
            font-size: 16px; font-weight: 600; color: #111827;
            margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb;
            display: flex; justify-content: space-between; align-items: center;
        }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .form-group { display: flex; flex-direction: column; }
        label { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; }
        input, select {
            height: 40px; padding: 0 12px; border-radius: 8px; border: 1px solid #e5e7eb;
            font-size: 14px; background: white; color: #111827; transition: all 0.2s;
        }
        input:hover, select:hover { border-color: #d1d5db; }
        input:focus, select:focus {
            outline: none; border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }
        .checkbox-group { display: flex; align-items: center; gap: 10px; height: 40px; }
        .checkbox-group input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: #6366f1; }
        .checkbox-group label { margin: 0; font-weight: 500; cursor: pointer; }
        .form-actions {
            display: flex; gap: 12px; margin-top: 32px;
            padding-top: 24px; border-top: 1px solid #e5e7eb;
        }
        .btn {
            height: 40px; padding: 0 16px; border-radius: 8px; font-size: 13px;
            font-weight: 500; cursor: pointer; transition: all 0.2s; border: none;
            display: inline-flex; align-items: center; justify-content: center;
            gap: 8px; text-decoration: none;
        }
        .btn-primary { background: #6366f1; color: white; flex: 1; }
        .btn-primary:hover { background: #4f46e5; }
        .btn-secondary { background: white; color: #374151; border: 1px solid #e5e7eb; }
        .btn-secondary:hover { background: #f9fafb; }
        .btn-success { background: #10b981; color: white; height: 36px; padding: 0 14px; font-size: 13px; }
        .btn-success:hover { background: #059669; }
        .btn-danger-sm {
            background: transparent; color: #ef4444; border: 1px solid #fecaca;
            height: 32px; padding: 0 10px; font-size: 12px; border-radius: 6px;
            cursor: pointer; display: inline-flex; align-items: center;
        }
        .btn-danger-sm:hover { background: #fef2f2; }
        .hint { font-size: 11px; color: #9ca3af; margin-top: 4px; }

        /* Operators table */
        .operators-table-wrap { border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; margin-top: 16px; }
        .operators-table { width: 100%; border-collapse: collapse; }
        .operators-table thead { background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
        .operators-table thead th {
            padding: 10px 12px; text-align: left; font-size: 11px;
            font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;
        }
        .operators-table tbody tr { border-bottom: 1px solid #f3f4f6; }
        .operators-table tbody tr:last-child { border-bottom: none; }
        .operators-table tbody td { padding: 10px 12px; font-size: 13px; color: #374151; vertical-align: middle; }

        /* Add operator inline form */
        .add-operator-form {
            background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px;
            padding: 16px; margin-top: 16px; display: none;
        }
        .add-operator-form.show { display: block; }
        .operator-form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 12px; }

        .empty-operators {
            padding: 24px; text-align: center; color: #9ca3af; font-size: 13px;
            border: 1px dashed #e5e7eb; border-radius: 10px; margin-top: 16px;
        }

        @media (max-width: 768px) {
            .page-container { padding: 16px; }
            .form-card { padding: 20px; }
            .form-grid { grid-template-columns: 1fr; }
            .operator-form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    @include('partials.admin-navbar')

    <div class="page-container">
        <div class="page-header">
            <h1 class="page-title">New Company</h1>
            <p class="page-subtitle">Add a new company to the system</p>
        </div>

        @if ($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; margin-bottom: 24px; color: #dc2626;">
            <strong>Greške:</strong>
            <ul style="margin-top: 8px; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form id="company-form" method="POST" action="{{ route('companies.store') }}">
            @csrf
            {{-- Operators se čuvaju kao JSON --}}
            <input type="hidden" name="operators_data" id="operators_data" value="[]">

            <div class="form-card">
                <div class="form-section">
                    <h3 class="section-title">Basic Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Company Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="npr. Tech Solutions DOO" required>
                        </div>
                        <div class="form-group">
                            <label>Tax ID Type</label>
                            <select name="tax_id_type" required>
                                <option value="TIN" {{ old('tax_id_type') == 'TIN' ? 'selected' : '' }}>TIN</option>
                                <option value="ID" {{ old('tax_id_type') == 'ID' ? 'selected' : '' }}>ID</option>
                                <option value="PASS" {{ old('tax_id_type') == 'PASS' ? 'selected' : '' }}>PASS</option>
                                <option value="VAT" {{ old('tax_id_type') == 'VAT' ? 'selected' : '' }}>VAT</option>
                                <option value="TAX" {{ old('tax_id_type') == 'TAX' ? 'selected' : '' }}>TAX</option>
                                <option value="SOC" {{ old('tax_id_type') == 'SOC' ? 'selected' : '' }}>SOC</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tax ID Number (PIB)</label>
                            <input type="text" name="tax_id_number" value="{{ old('tax_id_number') }}" placeholder="npr. 12345678" required>
                        </div>
                        <div class="form-group">
                            <label>Country</label>
                            <input type="text" name="country" value="{{ old('country', 'Montenegro') }}" required>
                        </div>
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" value="{{ old('city') }}" placeholder="npr. Podgorica" required>
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" name="address" value="{{ old('address') }}" placeholder="npr. Bulevar Svetog Petra 12" required>
                        </div>
                        <div class="form-group">
                            <label>Bank Account Number</label>
                            <input type="text" name="bank_account_number" value="{{ old('bank_account_number') }}" placeholder="npr. 540-123456-78">
                        </div>
                        <div class="form-group" style="justify-content: flex-end;">
                            <label>VAT Issuer</label>
                            <div class="checkbox-group">
                                <input type="checkbox" name="is_issuer_in_vat" id="is_issuer_in_vat" {{ old('is_issuer_in_vat') ? 'checked' : '' }}>
                                <label for="is_issuer_in_vat">Is issuer in VAT</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Fiscalization Codes</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>ENU Code (TCR Code)</label>
                            <input type="text" name="enu_code" value="{{ old('enu_code') }}" placeholder="npr. xb131ap287" required>
                            <span class="hint">Format: 2 slova + 3 broja + 2 slova + 3 broja</span>
                        </div>
                        <div class="form-group">
                            <label>Business Unit Code</label>
                            <input type="text" name="business_unit_code" value="{{ old('business_unit_code') }}" placeholder="npr. ab123ab123" required>
                            <span class="hint">Format: 2 slova + 3 broja + 2 slova + 3 broja</span>
                        </div>
                        <div class="form-group">
                            <label>Software Code</label>
                            <input type="text" name="software_code" value="{{ old('software_code') }}" placeholder="npr. sw001aa001" required>
                            <span class="hint">Format: 2 slova + 3 broja + 2 slova + 3 broja</span>
                        </div>
                    </div>
                </div>

                <div class="form-section" style="margin-bottom: 0;">
                    <h3 class="section-title">
                        Operators
                        <button type="button" class="btn-success btn" onclick="toggleAddOperator()">+ Add Operator</button>
                    </h3>

                    {{-- Forma za dodavanje operatora --}}
                    <div class="add-operator-form" id="add-operator-form">
                        <div class="operator-form-grid">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" id="op-name" placeholder="npr. Andrija Velickovic">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" id="op-email" placeholder="email@example.com">
                            </div>
                            <div class="form-group">
                                <label>Operator Code</label>
                                <input type="text" id="op-code" placeholder="npr. ab134ab287">
                            </div>
                            <div class="form-group">
                                <label>Role</label>
                                <select id="op-role">
                                    <option value="admin">Admin</option>
                                    <option value="user">User</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="op-active" checked>
                                    <label for="op-active">Active</label>
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" class="btn btn-success" style="flex: none;" onclick="addOperator()">Dodaj</button>
                            <button type="button" class="btn btn-secondary" style="flex: none;" onclick="toggleAddOperator()">Odustani</button>
                        </div>
                    </div>

                    {{-- Lista operatora --}}
                    <div id="operators-list">
                        <div class="empty-operators" id="empty-operators">
                            Nema dodanih operatora. Dodajte barem jednog operatora.
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 0;">
                <button type="submit" class="btn btn-primary">Save Company</button>
                <a href="{{ route('companies.index') }}" class="btn btn-secondary">Cancel</a>
            </div>

        </form>
    </div>

    <script>
    var operators = [];

    function toggleAddOperator() {
        var form = document.getElementById('add-operator-form');
        form.classList.toggle('show');
        if (form.classList.contains('show')) {
            document.getElementById('op-name').focus();
        }
    }

    function addOperator() {
        var name = document.getElementById('op-name').value.trim();
        var email = document.getElementById('op-email').value.trim();
        var code = document.getElementById('op-code').value.trim();
        var role = document.getElementById('op-role').value;
        var active = document.getElementById('op-active').checked;

        if (!name || !email || !code) {
            alert('Molimo popunite ime, email i operator code.');
            return;
        }

        operators.push({ name: name, email: email, operator_code: code, role: role, is_active: active });
        document.getElementById('operators_data').value = JSON.stringify(operators);

        renderOperators();

        // Reset forme
        document.getElementById('op-name').value = '';
        document.getElementById('op-email').value = '';
        document.getElementById('op-code').value = '';
        document.getElementById('op-role').selectedIndex = 0;
        document.getElementById('op-active').checked = true;
        document.getElementById('add-operator-form').classList.remove('show');
    }

    function removeOperator(index) {
        operators.splice(index, 1);
        document.getElementById('operators_data').value = JSON.stringify(operators);
        renderOperators();
    }

    function renderOperators() {
        var list = document.getElementById('operators-list');
        var empty = document.getElementById('empty-operators');

        if (operators.length === 0) {
            list.innerHTML = '<div class="empty-operators" id="empty-operators">Nema dodanih operatora. Dodajte barem jednog operatora.</div>';
            return;
        }

        var html = '<div class="operators-table-wrap"><table class="operators-table"><thead><tr><th>Name</th><th>Email</th><th>Operator Code</th><th>Role</th><th>Status</th><th></th></tr></thead><tbody>';

        operators.forEach(function(op, index) {
            html += '<tr>';
            html += '<td style="font-weight:600;color:#111827;">' + op.name + '</td>';
            html += '<td>' + op.email + '</td>';
            html += '<td>' + op.operator_code + '</td>';
            html += '<td>' + op.role + '</td>';
            html += '<td>' + (op.is_active ? '<span style="background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:500;">Active</span>' : '<span style="background:#f3f4f6;color:#6b7280;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:500;">Inactive</span>') + '</td>';
            html += '<td><button type="button" class="btn-danger-sm" onclick="removeOperator(' + index + ')">Ukloni</button></td>';
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        list.innerHTML = html;
    }
    </script>
</body>
</html>
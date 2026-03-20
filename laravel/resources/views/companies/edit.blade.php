<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Company - FiscalizationME</title>
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
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: #ef4444; color: white; height: 36px; padding: 0 12px; }
        .btn-danger:hover { background: #dc2626; }
        .btn-sm { height: 32px; padding: 0 12px; font-size: 12px; }
        .btn-outline-primary { background: white; color: #6366f1; border: 1px solid #6366f1; }
        .btn-outline-primary:hover { background: #f5f3ff; }
        .hint { font-size: 11px; color: #9ca3af; margin-top: 4px; }
        .alert-success {
            background: #d1fae5; border: 1px solid #6ee7b7; border-radius: 8px;
            padding: 12px 16px; color: #065f46; font-size: 14px; margin-bottom: 24px;
        }

        /* Operators Table */
        .operators-table { width: 100%; border-collapse: collapse; }
        .operators-table thead { background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
        .operators-table thead th {
            padding: 10px 12px; text-align: left; font-size: 11px;
            font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;
        }
        .operators-table tbody tr { border-bottom: 1px solid #f3f4f6; transition: background 0.2s; }
        .operators-table tbody tr:hover { background: #f9fafb; }
        .operators-table tbody tr:last-child { border-bottom: none; }
        .operators-table tbody td { padding: 12px; font-size: 13px; color: #374151; vertical-align: middle; }
        .operators-table-wrap { border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; margin-bottom: 24px; }
        .badge { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 500; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-gray { background: #f3f4f6; color: #6b7280; }

        /* Modal */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.5);
            z-index: 1000; display: none; align-items: center; justify-content: center;
        }
        .modal-overlay.show { display: flex; }
        .modal {
            background: white; border-radius: 12px; padding: 32px; width: 100%;
            max-width: 520px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); position: relative;
        }
        .modal-title { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 24px; }
        .modal-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px; }
        .modal-actions { display: flex; gap: 12px; justify-content: flex-end; }
        .modal-close {
            position: absolute; top: 16px; right: 16px; background: none;
            border: none; font-size: 20px; color: #9ca3af; cursor: pointer;
        }
        .modal-close:hover { color: #374151; }

        @media (max-width: 768px) {
            .page-container { padding: 16px; }
            .form-card { padding: 20px; }
            .form-grid { grid-template-columns: 1fr; }
            .modal-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    @include('partials.admin-navbar')

    {{-- MODAL ZA DODAVANJE OPERATORA --}}
    <div class="modal-overlay" id="add-operator-modal">
        <div class="modal">
            <button class="modal-close" onclick="closeAddModal()">✕</button>
            <h2 class="modal-title">Add Operator</h2>
            <form method="POST" action="{{ route('operators.store', $company->id) }}">
                @csrf
                <div class="modal-grid">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" placeholder="npr. Andrija Velickovic" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="email@example.com" required>
                    </div>
                    <div class="form-group">
                        <label>Operator Code</label>
                        <input type="text" name="operator_code" placeholder="npr. ab134ab287" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role">
                            <option value="admin">Admin</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <div class="checkbox-group">
                            <input type="checkbox" name="is_active" id="add-is-active" checked>
                            <label for="add-is-active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Operator</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL ZA EDITOVANJE OPERATORA --}}
    <div class="modal-overlay" id="edit-operator-modal">
        <div class="modal">
            <button class="modal-close" onclick="closeEditModal()">✕</button>
            <h2 class="modal-title">Edit Operator</h2>
            <form method="POST" id="edit-operator-form">
                @csrf
                @method('PUT')
                <div class="modal-grid">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" id="edit-name" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="edit-email" required>
                    </div>
                    <div class="form-group">
                        <label>Operator Code</label>
                        <input type="text" name="operator_code" id="edit-operator-code" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" id="edit-role">
                            <option value="admin">Admin</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <div class="checkbox-group">
                            <input type="checkbox" name="is_active" id="edit-is-active">
                            <label for="edit-is-active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex: none;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div class="page-container">
        <div class="page-header">
            <h1 class="page-title">Edit Company</h1>
            <p class="page-subtitle">Update company details</p>
        </div>

        @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
        @endif

        <div class="form-card">

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

            <form id="company-form" method="POST" action="{{ route('companies.update', $company->id) }}">
                @csrf
                @method('PUT')

                <div class="form-section">
                    <h3 class="section-title">Basic Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Company Name</label>
                            <input type="text" name="name" value="{{ old('name', $company->name) }}" required>
                        </div>
                        <div class="form-group">
                            <label>Tax ID Type</label>
                            <select name="tax_id_type" required>
                                @foreach(['TIN','ID','PASS','VAT','TAX','SOC'] as $type)
                                <option value="{{ $type }}" {{ $company->tax_id_type->value == $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tax ID Number (PIB)</label>
                            <input type="text" name="tax_id_number" value="{{ old('tax_id_number', $company->tax_id_number) }}" required>
                        </div>
                        <div class="form-group">
                            <label>Country</label>
                            <input type="text" name="country" value="{{ old('country', $company->country) }}" required>
                        </div>
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" value="{{ old('city', $company->city) }}" required>
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" name="address" value="{{ old('address', $company->address) }}" required>
                        </div>
                        <div class="form-group">
                            <label>Bank Account Number</label>
                            <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $company->bank_account_number) }}">
                        </div>
                        <div class="form-group" style="justify-content: flex-end;">
                            <label>VAT Issuer</label>
                            <div class="checkbox-group">
                                <input type="checkbox" name="is_issuer_in_vat" id="is_issuer_in_vat" {{ $company->is_issuer_in_vat ? 'checked' : '' }}>
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
                            <input type="text" name="enu_code" value="{{ old('enu_code', $company->enu_code) }}" required>
                            <span class="hint">Format: 2 slova + 3 broja + 2 slova + 3 broja (npr. xb131ap287)</span>
                        </div>
                        <div class="form-group">
                            <label>Business Unit Code</label>
                            <input type="text" name="business_unit_code" value="{{ old('business_unit_code', $company->business_unit_code) }}" required>
                            <span class="hint">Format: 2 slova + 3 broja + 2 slova + 3 broja</span>
                        </div>
                        <div class="form-group">
                            <label>Software Code</label>
                            <input type="text" name="software_code" value="{{ old('software_code', $company->software_code) }}" required>
                            <span class="hint">Format: 2 slova + 3 broja + 2 slova + 3 broja</span>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- OPERATORS SEKCIJA --}}
        <div class="form-card">
            <div class="form-section" style="margin-bottom: 0;">
                <h3 class="section-title">
                    Operators
                    <button type="button" class="btn btn-success btn-sm" onclick="openAddModal()">+ Add Operator</button>
                </h3>

                @if($company->users->count())
                <div class="operators-table-wrap">
                    <table class="operators-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Operator Code</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($company->users as $operator)
                            <tr>
                                <td style="font-weight: 600; color: #111827;">{{ $operator->name }}</td>
                                <td>{{ $operator->email }}</td>
                                <td>{{ $operator->operator_code }}</td>
                                <td>{{ $operator->role }}</td>
                                <td>
                                    @if($operator->is_active)
                                        <span class="badge badge-green">Active</span>
                                    @else
                                        <span class="badge badge-gray">Inactive</span>
                                    @endif
                                </td>
                                <td style="display: flex; gap: 8px;">
                                    <button type="button" class="btn btn-secondary btn-sm"
                                        onclick="openEditModal(
                                            {{ $operator->id }},
                                            '{{ addslashes($operator->name) }}',
                                            '{{ addslashes($operator->email) }}',
                                            '{{ addslashes($operator->operator_code) }}',
                                            '{{ $operator->role }}',
                                            {{ $operator->is_active ? 'true' : 'false' }}
                                        )">Edit</button>
                                    <form method="POST" action="{{ route('operators.destroy', $operator->id) }}" onsubmit="return confirm('Obrisati operatora?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div style="padding: 24px; text-align: center; color: #9ca3af; font-size: 13px; border: 1px dashed #e5e7eb; border-radius: 10px; margin-top: 16px;">
                    Nema dodanih operatora. Dodajte barem jednog operatora.
                </div>
                @endif
            </div>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 0;">
            <button type="submit" form="company-form" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('companies.index') }}" class="btn btn-secondary">Cancel</a>
        </div>

    </div>

    <script>
    function openAddModal() {
        document.getElementById('add-operator-modal').classList.add('show');
    }

    function closeAddModal() {
        document.getElementById('add-operator-modal').classList.remove('show');
    }

    function openEditModal(id, name, email, operatorCode, role, isActive) {
        document.getElementById('edit-operator-form').action = '/operators/' + id;
        document.getElementById('edit-name').value = name;
        document.getElementById('edit-email').value = email;
        document.getElementById('edit-operator-code').value = operatorCode;
        document.getElementById('edit-role').value = role;
        document.getElementById('edit-is-active').checked = isActive;
        document.getElementById('edit-operator-modal').classList.add('show');
    }

    function closeEditModal() {
        document.getElementById('edit-operator-modal').classList.remove('show');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAddModal();
            closeEditModal();
        }
    });

    document.getElementById('add-operator-modal').addEventListener('click', function(e) {
        if (e.target === this) closeAddModal();
    });

    document.getElementById('edit-operator-modal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });
    </script>
</body>
</html>
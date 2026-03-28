<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - FiscalizationME</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f9fafb; color: #111827;
        }
        .page-container { max-width: 700px; margin: 0 auto; padding: 32px; }
        .page-header { margin-bottom: 32px; }
        .page-title { font-size: 28px; font-weight: 700; color: #111827; margin-bottom: 8px; }
        .page-subtitle { font-size: 14px; color: #6b7280; }

        .form-card { background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 32px; margin-bottom: 24px; }
        .section-title {
            font-size: 16px; font-weight: 600; color: #111827;
            margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb;
        }
        .form-group { display: flex; flex-direction: column; margin-bottom: 20px; }
        .form-group:last-child { margin-bottom: 0; }
        label { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; }
        input {
            height: 40px; padding: 0 12px; border-radius: 8px; border: 1px solid #e5e7eb;
            font-size: 14px; background: white; color: #111827; transition: all 0.2s;
        }
        input:hover { border-color: #d1d5db; }
        input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        input:disabled { background: #f9fafb; color: #6b7280; cursor: not-allowed; }

        .alert-success {
            background: #d1fae5; border: 1px solid #6ee7b7; border-radius: 8px;
            padding: 12px 16px; color: #065f46; font-size: 14px; margin-bottom: 24px;
        }
        .alert-error {
            background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px;
            padding: 12px 16px; color: #dc2626; font-size: 14px; margin-bottom: 24px;
        }

        .btn {
            height: 40px; padding: 0 20px; border-radius: 8px; font-size: 14px;
            font-weight: 500; cursor: pointer; transition: all 0.2s; border: none;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-primary { background: #6366f1; color: white; }
        .btn-primary:hover { background: #4f46e5; }

        .user-info { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
        .user-avatar {
            width: 64px; height: 64px; border-radius: 12px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; font-weight: 700; color: white; flex-shrink: 0;
        }
        .user-details { flex: 1; }
        .user-name { font-size: 18px; font-weight: 700; color: #111827; }
        .user-email { font-size: 14px; color: #6b7280; margin-top: 4px; }
        .user-role {
            display: inline-flex; align-items: center; padding: 2px 10px;
            border-radius: 999px; font-size: 12px; font-weight: 500;
            background: #d1fae5; color: #065f46; margin-top: 6px;
        }
    </style>
</head>
<body>
    @include('partials.admin-navbar')

    <div class="page-container">
        <div class="page-header">
            <h1 class="page-title">Settings</h1>
            <p class="page-subtitle">Manage your account settings</p>
        </div>

        @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
        <div class="alert-error">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
        @endif

        {{-- PROFILE INFO --}}
        <div class="form-card">
            <h3 class="section-title">Profile</h3>

            <div class="user-info">
                <div class="user-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                <div class="user-details">
                    <div class="user-name">{{ $user->name }}</div>
                    <div class="user-email">{{ $user->email }}</div>
                    <span class="user-role">{{ ucfirst($user->role) }}</span>
                </div>
            </div>

            <div class="form-group">
                <label>Name</label>
                <input type="text" value="{{ $user->name }}" disabled>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" value="{{ $user->email }}" disabled>
            </div>
        </div>

        {{-- PROMJENA LOZINKE --}}
        <div class="form-card">
            <h3 class="section-title">Change Password</h3>

            <form method="POST" action="{{ route('settings.password') }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" required minlength="8">
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="password_confirmation" required>
                </div>

                <button type="submit" class="btn btn-primary">Save Password</button>
            </form>
        </div>
    </div>
</body>
</html>
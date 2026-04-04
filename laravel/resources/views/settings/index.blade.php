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

        .page-container { max-width: 1400px; margin: 0 auto; padding: 32px; }
        .page-header { margin-bottom: 32px; }
        .page-title { font-size: 28px; font-weight: 700; color: #111827; margin-bottom: 8px; }
        .page-subtitle { font-size: 14px; color: #6b7280; }

        .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }

        .settings-card { background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }

        .card-header {
            padding: 24px 28px 20px;
            border-bottom: 1px solid #f3f4f6;
        }
        .card-header-title { font-size: 16px; font-weight: 600; color: #111827; margin-bottom: 4px; }
        .card-header-sub { font-size: 13px; color: #9ca3af; }

        .card-body { padding: 28px; }

        .form-group { display: flex; flex-direction: column; margin-bottom: 20px; }
        .form-group:last-child { margin-bottom: 0; }

        label { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; }

        input[type="text"], input[type="email"], input[type="password"] {
            height: 42px; padding: 0 14px; border-radius: 8px; border: 1px solid #e5e7eb;
            font-size: 14px; background: white; color: #111827; transition: all 0.2s;
        }
        input:hover { border-color: #d1d5db; }
        input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        input:disabled { background: #f9fafb; color: #9ca3af; cursor: not-allowed; border-color: #f3f4f6; }

        .card-footer {
            padding: 16px 28px;
            background: #f9fafb;
            border-top: 1px solid #f3f4f6;
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-footer-hint { font-size: 12px; color: #9ca3af; }

        .btn {
            height: 40px; padding: 0 24px; border-radius: 8px; font-size: 13px;
            font-weight: 600; cursor: pointer; transition: all 0.2s; border: none;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-primary { background: #6366f1; color: white; }
        .btn-primary:hover { background: #4f46e5; }

        /* PROFILE BANNER */
        .profile-banner {
            background: #f9fafb;
            border-bottom: 1px solid #f3f4f6;
            padding: 28px;
            display: flex; align-items: center; gap: 20px;
        }
        .user-avatar {
            width: 72px; height: 72px; border-radius: 16px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 30px; font-weight: 700; color: white; flex-shrink: 0;
            border: none;
        }
        .user-name { font-size: 20px; font-weight: 700; color: #111827; }
        .user-email { font-size: 13px; color: #6b7280; margin-top: 4px; }
        .user-role {
            display: inline-flex; align-items: center; padding: 3px 10px;
            border-radius: 999px; font-size: 11px; font-weight: 600;
            background: #ede9fe; color: #6366f1; margin-top: 8px;
            border: none;
        }

        .password-hint { font-size: 11px; color: #9ca3af; margin-top: 5px; }

        /* ALERTS */
        .alert {
            border-radius: 10px; padding: 14px 16px; font-size: 13px;
            display: flex; align-items: flex-start; gap: 10px; margin-bottom: 24px;
        }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }

        @media (max-width: 900px) {
            .settings-grid { grid-template-columns: 1fr; }
            .page-container { padding: 20px 16px; }
        }
    </style>
</head>
<body>
    @include('partials.admin-navbar')

    <div class="page-container">
        <div class="page-header">
            <h1 class="page-title">Settings</h1>
            <p class="page-subtitle">Manage your account and preferences</p>
        </div>

        @if(session('success'))
        <div class="alert alert-success">
            <span>✓</span>
            <span>{{ session('success') }}</span>
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-error">
            <span>⚠</span>
            <div>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="settings-grid">

            {{-- PROFILE CARD --}}
            <div class="settings-card">
                <div class="profile-banner">
                    <div class="user-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                    <div>
                        <div class="user-name">{{ $user->name }}</div>
                        <div class="user-email">{{ $user->email }}</div>
                        <span class="user-role">{{ ucfirst($user->role) }}</span>
                    </div>
                </div>
                <div class="card-header">
                    <div class="card-header-title">Profile Information</div>
                    <div class="card-header-sub">Your account details — contact administrator to update</div>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" value="{{ $user->name }}" disabled>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" value="{{ $user->email }}" disabled>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" value="{{ ucfirst($user->role) }}" disabled>
                    </div>
                </div>
            </div>

            {{-- PASSWORD CARD --}}
            <div class="settings-card">
                <div class="card-header">
                    <div class="card-header-title">Change Password</div>
                    <div class="card-header-sub">Keep your account secure with a strong password</div>
                </div>
                <form method="POST" action="{{ route('settings.password') }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required placeholder="Enter current password">
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="password" required minlength="8" placeholder="Min. 8 characters">
                            <span class="password-hint">At least 8 characters</span>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="password_confirmation" required placeholder="Repeat new password">
                        </div>
                    </div>
                    <div class="card-footer">
                        <span class="card-footer-hint">Use a strong password you don't use elsewhere.</span>
                        <button type="submit" class="btn btn-primary">Save Password</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</body>
</html>
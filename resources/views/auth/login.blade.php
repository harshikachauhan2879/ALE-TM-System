@extends('layouts.app')

@section('auth_content')
<!-- Background Ambient Glows -->
<div class="glow-blob blob-1"></div>
<div class="glow-blob blob-2"></div>

<div class="auth-card-two-col glass-panel">
    <!-- Left Column: Banner -->
    <div class="auth-banner">
        <div class="auth-banner-content">
            <div>
                <div class="auth-logo">ALE-TM System</div>
                <div class="auth-subtitle" style="margin-bottom: 0;">Enterprise Task Management</div>
            </div>
            
            <div class="auth-banner-features">
                <div class="feature-item">
                    <span class="feature-icon">⚡</span>
                    <div class="feature-text">
                        <h4>Structured Workflows</h4>
                        <p>Transition tasks seamlessly from pending to approved, in progress, qa review, and completed.</p>
                    </div>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">👥</span>
                    <div class="feature-text">
                        <h4>Multi-User Assignment</h4>
                        <p>Assign tasks to multiple team members and manage role-based permissions efficiently.</p>
                    </div>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">📁</span>
                    <div class="feature-text">
                        <h4>Versioned Files</h4>
                        <p>Upload attachments up to 10MB with automated version tracking on name collision.</p>
                    </div>
                </div>
            </div>
            
            <div class="auth-banner-footer">
                <span>© 2026 ALE-TM System</span>
                <span>• v1.4.0</span>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Form -->
    <div class="auth-form-side">
        <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 10px; letter-spacing: -0.02em;">Welcome Back</h2>
        <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 30px;">Sign in to access your dashboard</p>
        
        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <div class="auth-input-wrapper">
                    <span class="auth-input-icon">✉️</span>
                    <input type="email" name="email" id="email" class="form-control" placeholder="name@company.com" required value="{{ old('email') }}">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="auth-input-wrapper">
                    <span class="auth-input-icon">🔒</span>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>
            
            <div class="form-group" style="display: flex; align-items: center; gap: 8px; margin-bottom: 25px;">
                <input type="checkbox" name="remember" id="remember" style="cursor: pointer; width: 16px; height: 16px; accent-color: var(--primary);">
                <label for="remember" style="font-size: 0.85rem; color: var(--text-secondary); cursor: pointer; user-select: none;">Remember this device</label>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 20px;">
                🔑 Sign In
            </button>
        </form>
        
        <div style="margin-top: 10px; font-size: 0.9rem; color: var(--text-secondary); text-align: center;">
            Don't have an account? <a href="{{ route('register') }}" style="color: var(--primary); text-decoration: none; font-weight: 600;">Register Here</a>
        </div>

        <!-- Quick Credentials Helper (for Testing) -->
        <div class="glass-panel" style="margin-top: 35px; padding: 15px; border-radius: 12px; background: rgba(255,255,255,0.02); text-align: left;">
            <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--accent); margin-bottom: 10px; letter-spacing: 0.05em; text-align: center;">⚡ Quick Testing Accounts</div>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
                <button onclick="fillAuth('superadmin@task.com')" class="btn btn-secondary" style="font-size: 0.7rem; padding: 6px;">Super Admin</button>
                <button onclick="fillAuth('admin@task.com')" class="btn btn-secondary" style="font-size: 0.7rem; padding: 6px;">Admin</button>
                <button onclick="fillAuth('teamlead@task.com')" class="btn btn-secondary" style="font-size: 0.7rem; padding: 6px;">Team Lead</button>
                <button onclick="fillAuth('employee1@task.com')" class="btn btn-secondary" style="font-size: 0.7rem; padding: 6px;">Employee</button>
                <button onclick="fillAuth('client@task.com')" class="btn btn-secondary" style="font-size: 0.7rem; padding: 6px;">Client</button>
                <button onclick="fillAuth('inactive@task.com')" class="btn btn-secondary" style="font-size: 0.7rem; padding: 6px; border-color: rgba(239, 68, 68, 0.2); color: #f87171;">Inactive</button>
            </div>
            <div style="font-size: 0.65rem; color: var(--text-secondary); text-align: center; margin-top: 10px;">Password for all accounts is: <strong>password</strong></div>
        </div>
    </div>

<script>
    function fillAuth(email) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = 'password';
    }
</script>
@endsection

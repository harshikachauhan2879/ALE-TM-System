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
                    <span class="feature-icon">🔒</span>
                    <div class="feature-text">
                        <h4>Secure Access Control</h4>
                        <p>State-of-the-art authentication with custom roles to restrict unauthorized actions.</p>
                    </div>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">🔑</span>
                    <div class="feature-text">
                        <h4>API Token Access</h4>
                        <p>Generate secure personal access tokens to query tasks programmatically via REST APIs.</p>
                    </div>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">📋</span>
                    <div class="feature-text">
                        <h4>Kanban Interface</h4>
                        <p>Organize, filter, and track tasks via a premium glassmorphic Kanban board layout.</p>
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
        <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 10px; letter-spacing: -0.02em;">Create Account</h2>
        <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 30px;">Get started with our enterprise platform</p>
        
        <form action="{{ route('register') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label class="form-label" for="name">Full Name</label>
                <div class="auth-input-wrapper">
                    <span class="auth-input-icon">👤</span>
                    <input type="text" name="name" id="name" class="form-control" placeholder="John Doe" required value="{{ old('name') }}">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <div class="auth-input-wrapper">
                    <span class="auth-input-icon">✉️</span>
                    <input type="email" name="email" id="email" class="form-control" placeholder="name@company.com" required value="{{ old('email') }}">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="role">Corporate Role</label>
                <div class="auth-input-wrapper">
                    <span class="auth-input-icon">💼</span>
                    <select name="role" id="role" class="form-control" style="background: rgba(15,23,42,0.6);" required>
                        <option value="employee" {{ old('role') === 'employee' ? 'selected' : '' }}>Employee</option>
                        <option value="team_lead" {{ old('role') === 'team_lead' ? 'selected' : '' }}>Team Lead</option>
                        <option value="client" {{ old('role') === 'client' ? 'selected' : '' }}>Client Partner</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="auth-input-wrapper">
                    <span class="auth-input-icon">🔒</span>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirmation">Confirm Password</label>
                <div class="auth-input-wrapper">
                    <span class="auth-input-icon">🔒</span>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="••••••••" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-accent" style="width: 100%; margin-bottom: 20px;">
                📝 Register Account
            </button>
        </form>
        
        <div style="margin-top: 10px; font-size: 0.9rem; color: var(--text-secondary); text-align: center;">
            Already have an account? <a href="{{ route('login') }}" style="color: var(--accent); text-decoration: none; font-weight: 600;">Sign In</a>
        </div>
    </div>
@endsection

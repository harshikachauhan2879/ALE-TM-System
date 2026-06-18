@extends('layouts.app')

@section('title', 'API Credentials')

@section('content')
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
    <!-- Token Generator -->
    <div class="glass-panel" style="padding: 30px;">
        <h3 style="font-weight: 700; margin-bottom: 20px;">🔑 Generate API Access Token</h3>
        <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5; margin-bottom: 25px;">
            Create a Personal Access Token to authenticate REST API requests. Keep this token secure; it will only be displayed once.
        </p>

        @if(session('success_token'))
            <div class="alert alert-success" style="word-break: break-all; margin-bottom: 25px; padding: 20px; border: 1px solid #10b981;">
                <strong style="display: block; margin-bottom: 8px;">⚠️ Copy Your Token Now:</strong>
                <code style="font-family: monospace; font-size: 1.1rem; background: rgba(0,0,0,0.3); padding: 8px; border-radius: 4px; display: block; border: 1px solid rgba(255,255,255,0.1);">{{ session('success_token') }}</code>
            </div>
        @endif

        <form action="{{ route('tokens.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label" for="token_name">Token Name</label>
                <input type="text" name="token_name" id="token_name" class="form-control" placeholder="e.g., Postman Testing, Mobile App" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Create Token</button>
        </form>
    </div>

    <!-- Active Tokens List -->
    <div class="glass-panel" style="padding: 30px;">
        <h3 style="font-weight: 700; margin-bottom: 20px;">📋 Active API Tokens</h3>
        
        @if($tokens->isEmpty())
            <p style="color: var(--text-secondary); text-align: center; padding: 20px 0; font-size: 0.95rem;">No active API tokens found.</p>
        @else
            <div class="file-list">
                @foreach($tokens as $t)
                    <div class="file-item" style="align-items: center;">
                        <div class="file-info">
                            <span class="file-name">{{ $t->name }}</span>
                            <span class="file-meta">Last used: {{ $t->last_used_at ? $t->last_used_at->diffForHumans() : 'Never' }}</span>
                        </div>
                        <form action="{{ route('tokens.destroy', $t->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-secondary" style="border-color: rgba(239, 68, 68, 0.2); color: #ef4444; padding: 6px 12px; font-size: 0.8rem;">
                                Revoke
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<!-- API Documentation & Testing Helper -->
<div class="glass-panel" style="padding: 30px; margin-top: 30px;">
    <h3 style="font-weight: 700; margin-bottom: 20px;">🛡️ REST API Testing Guide (2026 Sandbox)</h3>
    <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5; margin-bottom: 20px;">
        Test the API flow using the generated bearer token. Ensure the <code>Accept: application/json</code> header is included to receive standard JSON Resource responses.
    </p>

    <div style="background: rgba(0,0,0,0.3); padding: 20px; border-radius: 8px; border: 1px solid var(--panel-border); font-family: monospace; font-size: 0.85rem; line-height: 1.6; color: var(--text-primary); overflow-x: auto;">
        <div style="color: var(--accent); margin-bottom: 8px;">// Fetch All Authorized Tasks</div>
        curl -X GET "{{ url('/api/tasks') }}" \<br>
        &nbsp;&nbsp;-H "Authorization: Bearer <span style="color: var(--primary); font-weight: 700;">&lt;YOUR_TOKEN&gt;</span>" \<br>
        &nbsp;&nbsp;-H "Accept: application/json"<br><br>

        <div style="color: var(--accent); margin-bottom: 8px;">// Create a Task via API</div>
        curl -X POST "{{ url('/api/tasks') }}" \<br>
        &nbsp;&nbsp;-H "Authorization: Bearer <span style="color: var(--primary); font-weight: 700;">&lt;YOUR_TOKEN&gt;</span>" \<br>
        &nbsp;&nbsp;-H "Accept: application/json" \<br>
        &nbsp;&nbsp;-H "Content-Type: application/json" \<br>
        &nbsp;&nbsp;-d '{<br>
        &nbsp;&nbsp;&nbsp;&nbsp;"title": "Launch Marketing Campaign",<br>
        &nbsp;&nbsp;&nbsp;&nbsp;"description": "Create marketing assets...",<br>
        &nbsp;&nbsp;&nbsp;&nbsp;"priority": "medium",<br>
        &nbsp;&nbsp;&nbsp;&nbsp;"due_date": "{{ date('Y-m-d', strtotime('+3 days')) }}"<br>
        &nbsp;&nbsp;}'
    </div>
</div>
@endsection

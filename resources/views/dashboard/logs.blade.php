@extends('layouts.app')

@section('title', 'System Activity Logs')

@section('content')
<div class="glass-panel" style="padding: 30px;">
    <h3 style="font-weight: 700; margin-bottom: 20px;">🛡️ Audit Activity Trail</h3>
    <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5; margin-bottom: 25px;">
        This log records all corporate actions, file operations, system transitions, and database audits executed across the task management application. Accessible to Super Admin role only.
    </p>

    @if($logs->isEmpty())
        <p style="color: var(--text-secondary); text-align: center; padding: 20px 0;">No system logs found.</p>
    @else
        <div class="table-container">
            <table class="custom-table" style="font-size: 0.9rem;">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td style="white-space: nowrap; font-size: 0.8rem; color: var(--text-secondary);">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td style="font-weight: 600;">
                                {{ $log->user?->name ?? 'System Action' }}
                            </td>
                            <td>
                                @if($log->user)
                                    <span class="task-badge" style="background: rgba(255,255,255,0.05); color: var(--text-secondary); font-size: 0.7rem;">
                                        {{ str_replace('_', ' ', $log->user->role) }}
                                    </span>
                                @else
                                    <span class="task-badge" style="background: rgba(139, 92, 246, 0.1); color: var(--primary); font-size: 0.7rem;">
                                        daemon
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="task-badge" style="background: rgba(6, 182, 212, 0.1); color: var(--accent); font-size: 0.75rem; text-transform: uppercase;">
                                    {{ str_replace('_', ' ', $log->action) }}
                                </span>
                            </td>
                            <td style="line-height: 1.4; color: var(--text-primary);">
                                {{ $log->description }}
                            </td>
                            <td style="font-family: monospace; font-size: 0.8rem; color: var(--text-secondary);">
                                {{ $log->ip_address }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 30px; display: flex; justify-content: center;">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection

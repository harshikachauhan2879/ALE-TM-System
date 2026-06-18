@extends('layouts.app')

@section('title', 'Dashboard Overview')

@section('content')
<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stats-card pending glass-panel">
        <div class="stats-value" style="color: var(--status-pending);">{{ $stats['pending'] }}</div>
        <div class="stats-label">Pending</div>
    </div>
    <div class="stats-card approved glass-panel">
        <div class="stats-value" style="color: var(--status-approved);">{{ $stats['approved'] }}</div>
        <div class="stats-label">Approved</div>
    </div>
    <div class="stats-card progress glass-panel">
        <div class="stats-value" style="color: var(--status-in-progress);">{{ $stats['in_progress'] }}</div>
        <div class="stats-label">In Progress</div>
    </div>
    <div class="stats-card review glass-panel">
        <div class="stats-value" style="color: var(--status-qa-review);">{{ $stats['qa_review'] }}</div>
        <div class="stats-label">QA Review</div>
    </div>
    <div class="stats-card completed glass-panel">
        <div class="stats-value" style="color: var(--status-completed);">{{ $stats['completed'] }}</div>
        <div class="stats-label">Completed</div>
    </div>
    <div class="stats-card overdue glass-panel" style="border: 1px solid rgba(239, 68, 68, 0.25);">
        <div class="stats-value" style="color: var(--status-overdue); text-shadow: 0 0 10px rgba(239, 68, 68, 0.25);">{{ $stats['overdue'] }}</div>
        <div class="stats-label">Overdue</div>
    </div>
</div>

<!-- Quick Actions Panel -->
<div style="display: flex; gap: 20px; align-items: center; margin-bottom: 40px; flex-wrap: wrap;">
    @if(in_array(auth()->user()->role, ['super_admin', 'admin', 'team_lead']))
        <a href="{{ route('tasks.create') }}" class="btn btn-primary">
            ➕ Create New Task
        </a>
    @endif

    @if(in_array(auth()->user()->role, ['super_admin', 'admin']))
        <form action="{{ route('scheduler.run') }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn-accent" title="Triggers Checks: Marks overdue tasks and fires 24h reminders">
                ⚙️ Run Scheduler Simulation
            </button>
        </form>
    @endif
</div>

<!-- Main Panels Container (2-Column Grid for Administrators) -->
@if(in_array(auth()->user()->role, ['super_admin', 'admin']))
<div style="display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 30px; align-items: start;">
@else
<div>
@endif
    <!-- Task Overview Section -->
    <div class="glass-panel" style="padding: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="font-weight: 700;">Recent Active Tasks</h3>
            <a href="{{ route('tasks.index') }}" style="color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.9rem;">View All Tasks →</a>
        </div>

        @if($recentTasks->isEmpty())
            <p style="color: var(--text-secondary); text-align: center; padding: 20px 0;">No tasks found.</p>
        @else
            <div class="table-container">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentTasks as $task)
                            <tr>
                                <td style="font-weight: 600; font-size: 0.9rem;">{{ $task->title }}</td>
                                <td>
                                    <span class="priority-badge {{ $task->priority }}">
                                        {{ $task->priority }}
                                    </span>
                                </td>
                                <td>
                                    <span class="task-badge badge-{{ $task->status }}" style="font-size: 0.65rem; padding: 3px 8px;">
                                        {{ str_replace('_', ' ', $task->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-secondary" style="padding: 4px 8px; font-size: 0.75rem;">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Admin User Management Panel -->
    @if(in_array(auth()->user()->role, ['super_admin', 'admin']))
    <div class="glass-panel" style="padding: 30px;">
        <h3 style="font-weight: 700; margin-bottom: 20px;">Access Control & Roles</h3>
        <div class="table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Toggle</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                        <tr>
                            <td>
                                <div style="font-weight: 600; font-size: 0.9rem;">{{ $u->name }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">{{ $u->email }}</div>
                            </td>
                            <td>
                                @if($u->id === auth()->id())
                                    <span style="font-weight: 700; text-transform: uppercase; color: var(--accent); font-size: 0.8rem;">{{ $u->role }}</span>
                                @else
                                    <form action="{{ route('users.update-role', $u->id) }}" method="POST" style="display: inline;">
                                        @csrf
                                        <select name="role" onchange="this.form.submit()" class="form-control" style="padding: 4px 6px; font-size: 0.8rem; width: auto; background: rgba(0,0,0,0.2); border-radius: 6px;">
                                            <option value="super_admin" {{ $u->role === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                                            <option value="admin" {{ $u->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                            <option value="team_lead" {{ $u->role === 'team_lead' ? 'selected' : '' }}>Team Lead</option>
                                            <option value="employee" {{ $u->role === 'employee' ? 'selected' : '' }}>Employee</option>
                                            <option value="client" {{ $u->role === 'client' ? 'selected' : '' }}>Client</option>
                                        </select>
                                    </form>
                                @endif
                            </td>
                            <td>
                                @if($u->id !== auth()->id())
                                    <form action="{{ route('users.toggle-status', $u->id) }}" method="POST" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary" style="padding: 4px 8px; font-size: 0.75rem; border-color: {{ $u->status === 'active' ? 'rgba(239, 68, 68, 0.2)' : 'rgba(16, 185, 129, 0.2)' }}; color: {{ $u->status === 'active' ? '#ef4444' : '#10b981' }};">
                                            {{ $u->status === 'active' ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                @else
                                    <span style="font-size: 0.75rem; color: var(--text-secondary);">Self</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

@endsection

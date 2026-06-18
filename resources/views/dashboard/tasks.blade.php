@extends('layouts.app')

@section('title', 'Task Board')

@section('content')
<!-- Filter bar -->
<div class="glass-panel" style="padding: 20px; margin-bottom: 30px;">
    <form action="{{ route('tasks.index') }}" method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
        <div style="flex: 1; min-width: 200px;">
            <label class="form-label">Search</label>
            <input type="text" name="search" class="form-control" placeholder="Search title, desc, comments..." value="{{ request('search') }}">
        </div>
        
        <div style="width: 150px;">
            <label class="form-label">Status</label>
            <select name="status" class="form-control" style="background: rgba(0,0,0,0.2);">
                <option value="">All Statuses (Kanban View)</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="qa_review" {{ request('status') === 'qa_review' ? 'selected' : '' }}>QA Review</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
        </div>

        <div style="width: 130px;">
            <label class="form-label">Priority</label>
            <select name="priority" class="form-control" style="background: rgba(0,0,0,0.2);">
                <option value="">All</option>
                <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
            </select>
        </div>

        <div style="width: 150px;">
            <label class="form-label">Assignee</label>
            <select name="assignee_id" class="form-control" style="background: rgba(0,0,0,0.2);">
                <option value="">All</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ request('assignee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                @endforeach
            </select>
        </div>

        <div style="width: 150px;">
            <label class="form-label">Sort By</label>
            <select name="sort" class="form-control" style="background: rgba(0,0,0,0.2);">
                <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>Latest Created</option>
                <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest Created</option>
                <option value="due_date" {{ request('sort') === 'due_date' ? 'selected' : '' }}>Due Date (Asc)</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="padding: 11px 20px;">🔍 Filter</button>
            <a href="{{ route('tasks.export', request()->query()) }}" class="btn btn-accent" style="padding: 11px 20px;">📤 Export CSV</a>
            <a href="{{ route('tasks.index') }}" class="btn btn-secondary" style="padding: 11px 20px;">Reset</a>
        </div>
    </form>
</div>

<!-- Task Content Area -->
@if($tasks->isEmpty())
    <div class="glass-panel" style="padding: 50px; text-align: center;">
        <p style="color: var(--text-secondary); font-size: 1.1rem;">No tasks found matching your filter parameters.</p>
    </div>
@else
    @if(request('status'))
        <!-- Grid View for Single Status -->
        <div class="tasks-container">
            @foreach($tasks as $task)
                @include('dashboard.partials.task_card', ['task' => $task])
            @endforeach
        </div>
    @else
        <!-- Kanban Board View for All Statuses -->
        <div class="kanban-board">
            @php
                $statuses = [
                    'pending' => ['title' => 'Pending Approval', 'icon' => '⏳', 'class' => 'pending'],
                    'approved' => ['title' => 'Approved', 'icon' => '✅', 'class' => 'approved'],
                    'in_progress' => ['title' => 'In Progress', 'icon' => '⚡', 'class' => 'progress'],
                    'qa_review' => ['title' => 'QA Review', 'icon' => '🔍', 'class' => 'review'],
                    'completed' => ['title' => 'Completed', 'icon' => '🎉', 'class' => 'completed']
                ];
            @endphp

            @foreach($statuses as $statusKey => $statusMeta)
                @php $statusTasks = $tasks->where('status', $statusKey); @endphp
                <div class="kanban-column">
                    <div class="kanban-column-header">
                        <div class="kanban-column-title" style="color: var(--status-{{ $statusMeta['class'] }})">
                            <span>{{ $statusMeta['icon'] }}</span>
                            <span>{{ $statusMeta['title'] }}</span>
                        </div>
                        <span class="kanban-column-count">{{ $statusTasks->count() }}</span>
                    </div>

                    @if($statusTasks->isEmpty())
                        <div style="border: 2px dashed var(--panel-border); border-radius: 12px; padding: 20px; text-align: center; color: var(--text-secondary); font-size: 0.8rem; font-style: italic;">
                            No Tasks
                        </div>
                    @else
                        @foreach($statusTasks as $task)
                            @include('dashboard.partials.task_card', ['task' => $task])
                        @endforeach
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <!-- Pagination -->
    <div style="margin-top: 30px; display: flex; justify-content: center;">
        {{ $tasks->links() }}
    </div>
@endif

@endsection

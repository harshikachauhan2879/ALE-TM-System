@extends('layouts.app')

@section('title', 'Edit Task')

@section('content')
<div class="glass-panel" style="padding: 30px; max-width: 800px; margin: 0 auto;">
    <h3 style="font-weight: 700; margin-bottom: 25px;">✏️ Edit Task Details: #{{ $task->id }}</h3>
    
    <form action="{{ route('tasks.update', $task->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="form-group">
            <label class="form-label" for="title">Task Title</label>
            <input type="text" name="title" id="title" class="form-control" placeholder="Define core deliverable..." required value="{{ old('title', $task->title) }}">
        </div>
        
        <div class="form-group">
            <label class="form-label" for="description">Task Description</label>
            <textarea name="description" id="description" class="form-control" rows="5" placeholder="Elaborate details, requirements..." required>{{ old('description', $task->description) }}</textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="priority">Priority Tier</label>
                <select name="priority" id="priority" class="form-control" onchange="toggleAdminComment(this.value)" style="background: rgba(0,0,0,0.2);" required>
                    <option value="low" {{ old('priority', $task->priority) === 'low' ? 'selected' : '' }}>Low</option>
                    <option value="medium" {{ old('priority', $task->priority) === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="high" {{ old('priority', $task->priority) === 'high' ? 'selected' : '' }}>High (Requires Admin Comment)</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="due_date">Due Date</label>
                <input type="date" name="due_date" id="due_date" class="form-control" required value="{{ old('due_date', $task->due_date) }}" min="{{ date('Y-m-d') }}">
            </div>
        </div>

        <!-- High Priority Admin Comment Trigger -->
        <div class="form-group" id="admin-comment-group" style="display: {{ old('priority', $task->priority) === 'high' ? 'block' : 'none' }};">
            <label class="form-label" for="admin_comment" style="color: var(--status-overdue);">⚠️ Required Admin Comment (High Priority)</label>
            <textarea name="admin_comment" id="admin_comment" class="form-control" rows="3" placeholder="Explain the business urgency or requirements for this high priority task...">{{ old('admin_comment', $task->admin_comment) }}</textarea>
        </div>

        <div class="form-group">
            <label class="form-label">Assign Task Staff (Multiple Select)</label>
            <div class="glass-panel" style="padding: 15px; max-height: 200px; overflow-y: auto; background: rgba(0,0,0,0.15);">
                @if($employees->isEmpty())
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">No active employees available to assign.</p>
                @else
                    @foreach($employees as $emp)
                        <div style="display: flex; align-items: center; gap: 10px; padding: 6px 0;">
                            <input type="checkbox" name="assignees[]" value="{{ $emp->id }}" id="emp_{{ $emp->id }}" {{ in_array($emp->id, old('assignees', $currentAssignees)) ? 'checked' : '' }} style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary);">
                            <label for="emp_{{ $emp->id }}" style="font-size: 0.95rem; cursor: pointer; user-select: none;">{{ $emp->name }} ({{ $emp->email }})</label>
                        </div>
                    @endforeach
                @endif
            </div>
            <small style="color: var(--text-secondary); margin-top: 6px; display: block;">Clients cannot be assigned. Only active employees will be listed.</small>
        </div>

        <div style="display: flex; gap: 15px; margin-top: 30px;">
            <button type="submit" class="btn btn-primary" style="flex: 1;">💾 Save Changes</button>
            <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
    function toggleAdminComment(val) {
        const group = document.getElementById('admin-comment-group');
        const input = document.getElementById('admin_comment');
        if (val === 'high') {
            group.style.display = 'block';
            input.required = true;
        } else {
            group.style.display = 'none';
            input.required = false;
        }
    }
</script>
@endsection

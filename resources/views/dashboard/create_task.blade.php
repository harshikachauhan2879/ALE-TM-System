@extends('layouts.app')

@section('title', 'Create Task')

@section('content')
<div class="glass-panel" style="padding: 35px; max-width: 1000px; margin: 0 auto;">
    <h3 style="font-weight: 700; margin-bottom: 25px;">➕ Initialize New Task</h3>
    
    <form action="{{ route('tasks.store') }}" method="POST">
        @csrf
        
        <div style="display: grid; grid-template-columns: 1.25fr 0.75fr; gap: 30px; align-items: start;">
            <!-- Column 1: Core Details -->
            <div>
                <div class="form-group">
                    <label class="form-label" for="title">Task Title</label>
                    <input type="text" name="title" id="title" class="form-control" placeholder="Define core deliverable..." required value="{{ old('title') }}">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="description">Task Description</label>
                    <textarea name="description" id="description" class="form-control" rows="8" placeholder="Elaborate details, requirements, and project scope..." required>{{ old('description') }}</textarea>
                </div>

                <!-- High Priority Admin Comment Trigger -->
                <div class="form-group" id="admin-comment-group" style="display: {{ old('priority') === 'high' ? 'block' : 'none' }}; margin-top: 15px;">
                    <label class="form-label" for="admin_comment" style="color: var(--status-overdue);">⚠️ Required Admin Comment (High Priority)</label>
                    <textarea name="admin_comment" id="admin_comment" class="form-control" rows="3" placeholder="Explain the business urgency or requirements for this high priority task...">{{ old('admin_comment') }}</textarea>
                </div>
            </div>

            <!-- Column 2: Parameters & Assignees -->
            <div>
                <div class="form-group">
                    <label class="form-label" for="priority">Priority Tier</label>
                    <select name="priority" id="priority" class="form-control" onchange="toggleAdminComment(this.value)" style="background: rgba(0,0,0,0.2);" required>
                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('priority') === 'medium' || !old('priority') ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High (Requires Admin Comment)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="due_date">Due Date</label>
                    <input type="date" name="due_date" id="due_date" class="form-control" required value="{{ old('due_date') }}" min="{{ date('Y-m-d') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Assign Task Staff (Multiple Select)</label>
                    <div class="glass-panel" style="padding: 15px; max-height: 200px; overflow-y: auto; background: rgba(0,0,0,0.15);">
                        @if($employees->isEmpty())
                            <p style="color: var(--text-secondary); font-size: 0.9rem;">No active employees available to assign.</p>
                        @else
                            @foreach($employees as $emp)
                                <div style="display: flex; align-items: center; gap: 10px; padding: 6px 0;">
                                    <input type="checkbox" name="assignees[]" value="{{ $emp->id }}" id="emp_{{ $emp->id }}" {{ is_array(old('assignees')) && in_array($emp->id, old('assignees')) ? 'checked' : '' }} style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary);">
                                    <label for="emp_{{ $emp->id }}" style="font-size: 0.95rem; cursor: pointer; user-select: none;">{{ $emp->name }}</label>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <small style="color: var(--text-secondary); margin-top: 6px; display: block; font-size: 0.75rem;">Clients cannot be assigned. Only active employees will be listed.</small>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 35px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">🚀 Launch Task</button>
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>
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

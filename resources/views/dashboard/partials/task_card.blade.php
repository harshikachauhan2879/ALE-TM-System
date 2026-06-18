@php 
    $isOverdue = \Carbon\Carbon::parse($task->due_date)->isPast() && $task->status !== 'completed';
@endphp
<div class="task-card glass-panel" onclick="window.location.href='{{ route('tasks.show', $task->id) }}'" style="border-left: 4px solid @if($task->status === 'pending') var(--status-pending) @elseif($task->status === 'approved') var(--status-approved) @elseif($task->status === 'in_progress') var(--status-in-progress) @elseif($task->status === 'qa_review') var(--status-qa-review) @elseif($task->status === 'completed') var(--status-completed) @endif; padding: 20px; min-height: auto; margin-bottom: 5px;">
    <div class="task-header" style="margin-bottom: 12px;">
        <span class="priority-badge {{ $task->priority }}">{{ $task->priority }} priority</span>
        @if(request('status'))
            <!-- Only show status badge in grid view -->
            <span class="task-badge badge-{{ $task->status }}">{{ str_replace('_', ' ', $task->status) }}</span>
        @endif
    </div>
    
    <h4 class="task-title" style="font-size: 1rem; font-weight: 700; margin-bottom: 8px;">{{ $task->title }}</h4>
    <p class="task-desc" style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px; line-height: 1.5; -webkit-line-clamp: 2;">
        {{ $task->description }}
    </p>
    
    <div class="task-footer" style="padding-top: 10px; font-size: 0.8rem;">
        <div class="task-date {{ $isOverdue ? 'overdue' : '' }}">
            📅 {{ $task->due_date }}
        </div>
        
        <div class="avatar-group" title="Assignees">
            @foreach($task->assignees->take(2) as $assignee)
                <div class="avatar-stack-item" title="{{ $assignee->name }}" style="width: 24px; height: 24px; font-size: 0.6rem;">
                    {{ strtoupper(substr($assignee->name, 0, 1)) }}
                </div>
            @endforeach
            @if($task->assignees->count() > 2)
                <div class="avatar-stack-item" style="width: 24px; height: 24px; font-size: 0.6rem; background: var(--primary);">
                    +{{ $task->assignees->count() - 2 }}
                </div>
            @endif
            @if($task->assignees->isEmpty())
                <span style="font-size: 0.7rem; color: var(--text-secondary); font-style: italic;">Unassigned</span>
            @endif
        </div>
    </div>
</div>

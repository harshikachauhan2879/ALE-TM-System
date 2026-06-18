@extends('layouts.app')

@section('title', 'Task Details')

@section('content')
<div style="margin-bottom: 25px;">
    <a href="{{ route('tasks.index') }}" style="color: var(--text-secondary); text-decoration: none; font-weight: 600; font-size: 0.95rem;">← Back to Task Board</a>
</div>

<div class="task-detail-grid">
    <!-- Main Task Info, Comments & Attachments -->
    <div class="detail-main">
        <div class="glass-panel detail-box">
            <!-- Header Info -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <div>
                    <span class="task-badge badge-{{ $task->status }}">{{ str_replace('_', ' ', $task->status) }}</span>
                    <span class="priority-badge {{ $task->priority }}" style="margin-left: 10px;">{{ $task->priority }} priority</span>
                </div>
                
                @if(in_array(auth()->user()->role, ['super_admin', 'admin', 'team_lead']) && $task->status !== 'completed')
                    <div style="display: flex; gap: 10px;">
                        <a href="{{ route('tasks.edit', $task->id) }}" class="btn btn-secondary" style="padding: 8px 16px; font-size: 0.85rem;">✏️ Edit</a>
                        @if(in_array(auth()->user()->role, ['super_admin', 'admin']))
                            <form action="{{ route('tasks.destroy', $task->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this task?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" style="padding: 8px 16px; font-size: 0.85rem;">🗑️ Delete</button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 15px;">{{ $task->title }}</h2>
            
            <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6; margin-bottom: 30px; white-space: pre-line;">
                {{ $task->description }}
            </p>

            <!-- Admin Comments -->
            @if($task->admin_comment)
                <div style="background: rgba(239, 68, 68, 0.04); border: 1px solid rgba(255,255,255,0.05); padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                    <div style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--status-overdue); margin-bottom: 8px;">📢 Administrator Comment / Urgency Note</div>
                    <p style="font-size: 0.9rem; line-height: 1.5;">{{ $task->admin_comment }}</p>
                </div>
            @endif

            <!-- Workflow Transitions -->
            @if($task->status !== 'completed')
                <div class="glass-panel" style="padding: 20px; background: rgba(255,255,255,0.02); border-color: rgba(255,255,255,0.04); margin-top: 30px;">
                    <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 15px; text-transform: uppercase;">🔄 Task Lifecycle Transition Action</h4>
                    
                    <!-- 1. Approve (Pending -> Approved) -->
                    @if($task->status === 'pending')
                        @if(in_array(auth()->user()->role, ['super_admin', 'admin', 'team_lead']))
                            <form action="{{ route('tasks.transition', $task->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-accent">👍 Approve Task</button>
                            </form>
                        @else
                            <p style="font-size: 0.85rem; color: var(--text-secondary);">Waiting for Admin / Team Lead approval.</p>
                        @endif
                    @endif

                    <!-- 2. Start (Approved -> In Progress) -->
                    @if($task->status === 'approved')
                        @if($task->assignees->contains(auth()->id()))
                            <form action="{{ route('tasks.transition', $task->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="action" value="start">
                                <button type="submit" class="btn btn-primary">▶️ Start Working on Task</button>
                            </form>
                        @else
                            <p style="font-size: 0.85rem; color: var(--text-secondary);">Waiting for assigned staff member ({{ $task->assignees->pluck('name')->implode(', ') ?: 'unassigned' }}) to start working.</p>
                        @endif
                    @endif

                    <!-- 3. Submit Review (In Progress -> QA Review) -->
                    @if($task->status === 'in_progress')
                        @if($task->assignees->contains(auth()->id()))
                            <form action="{{ route('tasks.transition', $task->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="action" value="submit_review">
                                <button type="submit" class="btn btn-primary">📤 Submit Task for Review</button>
                            </form>
                        @else
                            <p style="font-size: 0.85rem; color: var(--text-secondary);">Staff members are working on this task.</p>
                        @endif
                    @endif

                    <!-- 4. QA Review Complete / Reject (QA Review -> Completed / In Progress) -->
                    @if($task->status === 'qa_review')
                        @if(in_array(auth()->user()->role, ['super_admin', 'admin', 'team_lead']))
                            <div style="display: flex; flex-direction: column; gap: 20px;">
                                <form action="{{ route('tasks.transition', $task->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="action" value="complete">
                                    <button type="submit" class="btn btn-primary">🎉 Complete Task (Approve QA)</button>
                                </form>
                                
                                <form action="{{ route('tasks.transition', $task->id) }}" method="POST" style="border-top: 1px solid var(--panel-border); padding-top: 15px;">
                                    @csrf
                                    <input type="hidden" name="action" value="reject">
                                    <label class="form-label" style="color: var(--status-overdue);">Reject Back to In Progress (Requires Comment)</label>
                                    <textarea name="reject_comment" class="form-control" rows="2" placeholder="Explain what revisions are needed..." required style="margin-bottom: 12px;"></textarea>
                                    <button type="submit" class="btn btn-danger" style="padding: 8px 16px; font-size: 0.85rem;">❌ Reject & Send Back</button>
                                </form>
                            </div>
                        @else
                            <p style="font-size: 0.85rem; color: var(--text-secondary);">Task is in QA review. Awaiting Admin or Team Lead audit.</p>
                        @endif
                    @endif
                </div>
            @endif
        </div>

        <!-- Comments Section -->
        <div class="glass-panel detail-box">
            <h3 class="detail-section-title">💬 Task Discussion</h3>
            
            @if($task->status !== 'completed')
                <form action="{{ route('comments.store', $task->id) }}" method="POST" style="margin-bottom: 30px;">
                    @csrf
                    <div class="form-group">
                        <textarea name="content" class="form-control" rows="3" placeholder="Write a comment..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-secondary">Add Comment</button>
                </form>
            @endif

            <div class="comments-list">
                @if($task->comments->isEmpty())
                    <p style="color: var(--text-secondary); text-align: center; font-size: 0.9rem;">No comments yet.</p>
                @else
                    @foreach($task->comments as $comment)
                        <div class="comment-card">
                            <div class="comment-header">
                                <span class="comment-user">{{ $comment->user->name }} ({{ str_replace('_', ' ', $comment->user->role) }})</span>
                                <span>{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="comment-content">
                                {{ $comment->content }}
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <!-- Sidebar Info, Attachments & Meta -->
    <div class="detail-sidebar" style="display: flex; flex-direction: column; gap: 30px;">
        <!-- Task Meta box -->
        <div class="glass-panel" style="padding: 25px;">
            <h4 class="detail-section-title" style="margin-bottom: 15px;">Task Details</h4>
            
            <div style="display: flex; flex-direction: column; gap: 15px; font-size: 0.9rem;">
                <div>
                    <strong style="color: var(--text-secondary); display: block; font-size: 0.75rem; text-transform: uppercase;">Due Date</strong>
                    <span>📅 {{ $task->due_date }}</span>
                </div>
                <div>
                    <strong style="color: var(--text-secondary); display: block; font-size: 0.75rem; text-transform: uppercase;">Creator</strong>
                    <span>👤 {{ $task->creator?->name }} ({{ str_replace('_', ' ', $task->creator?->role) }})</span>
                </div>
                <div>
                    <strong style="color: var(--text-secondary); display: block; font-size: 0.75rem; text-transform: uppercase;">Assignees</strong>
                    <div style="display: flex; flex-direction: column; gap: 5px; margin-top: 5px;">
                        @foreach($task->assignees as $assignee)
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div class="profile-avatar" style="width: 24px; height: 24px; font-size: 0.7rem;">
                                    {{ strtoupper(substr($assignee->name, 0, 1)) }}
                                </div>
                                <span>{{ $assignee->name }}</span>
                            </div>
                        @endforeach
                        @if($task->assignees->isEmpty())
                            <span style="color: var(--text-secondary); font-style: italic;">None assigned</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Attachments box -->
        <div class="glass-panel" style="padding: 25px;">
            <h4 class="detail-section-title" style="margin-bottom: 15px;">Attachments</h4>
            
            @if($task->status !== 'completed')
                <!-- File Upload widget -->
                @php $maxSizeMsg = $task->priority === 'high' ? '10MB' : '5MB'; @endphp
                <form action="{{ route('files.store', $task->id) }}" method="POST" enctype="multipart/form-data" style="margin-bottom: 20px;">
                    @csrf
                    <div class="form-group" style="margin-bottom: 12px;">
                        <input type="file" name="attachment" required class="form-control" style="font-size: 0.85rem; padding: 6px;">
                    </div>
                    <button type="submit" class="btn btn-secondary" style="font-size: 0.8rem; padding: 8px 16px; width: 100%;">
                        📤 Upload File
                    </button>
                    <small style="color: var(--text-secondary); display: block; margin-top: 6px; font-size: 0.7rem;">
                        Allowed: jpg, png, pdf, docx, zip. Max size: {{ $maxSizeMsg }}.
                    </small>
                </form>
            @endif

            <div class="file-list">
                @if($task->attachments->isEmpty())
                    <p style="color: var(--text-secondary); text-align: center; font-size: 0.85rem; padding: 10px 0;">No attachments uploaded.</p>
                @else
                    @foreach($task->attachments->sortByDesc('version') as $file)
                        <div class="file-item">
                            <div class="file-info" style="max-width: 70%;">
                                <span class="file-name" title="{{ $file->original_name }}" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;">
                                    {{ $file->original_name }}
                                </span>
                                <span class="file-meta">
                                    v{{ $file->version }} • {{ number_format($file->file_size / 1024, 1) }} KB
                                </span>
                                <span class="file-meta" style="font-size: 0.65rem;">
                                    by {{ $file->uploader?->name }} at {{ $file->created_at->format('M d, H:i') }}
                                </span>
                            </div>
                            
                            <div style="display: flex; gap: 5px;">
                                <a href="{{ route('files.download', $file->id) }}" class="btn btn-secondary" style="padding: 6px 10px; font-size: 0.75rem;" title="Download">
                                    💾
                                </a>
                                @if(($task->status !== 'completed') && (in_array(auth()->user()->role, ['super_admin', 'admin', 'team_lead']) || $file->uploader_id === auth()->id()))
                                    <form action="{{ route('files.destroy', $file->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-secondary" style="padding: 6px 10px; font-size: 0.75rem; border-color: rgba(239, 68, 68, 0.2); color: #ef4444;" title="Delete">
                                            🗑️
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

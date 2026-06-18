<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Task;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(Request $request, $taskId)
    {
        $request->validate([
            'content' => 'required|string|min:2',
        ]);

        $task = Task::findOrFail($taskId);

        // Check if user is associated with the task (assigned or creator, or admin/TL)
        $user = Auth::user();
        $isAuthorized = in_array($user->role, ['super_admin', 'admin', 'team_lead']) || 
                        $task->creator_id === $user->id || 
                        $task->assignees->contains($user->id);

        if (!$isAuthorized) {
            abort(403, 'Unauthorized to comment on this task.');
        }

        if ($task->status === 'completed') {
            return back()->with('error', 'Cannot add comments to a completed task.');
        }

        $comment = Comment::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'content' => $request->content,
        ]);

        ActivityLog::log('comment_add', "Added comment to task #{$task->id}");

        return back()->with('success', 'Comment added successfully.');
    }
}

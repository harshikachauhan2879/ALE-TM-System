<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\User;
use App\Http\Resources\TaskResource;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class TaskApiController extends Controller
{
    // List user tasks
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Task::with(['assignees', 'creator', 'attachments', 'comments']);

        if ($user->role === 'client') {
            $query->where(function($q) use ($user) {
                $q->where('creator_id', $user->id)
                  ->orWhereHas('assignees', fn($sq) => $sq->where('users.id', $user->id));
            });
        } elseif ($user->role === 'employee') {
            $query->whereHas('assignees', fn($q) => $q->where('users.id', $user->id));
        }

        // Apply filters if passed in request
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $tasks = $query->get();
        return TaskResource::collection($tasks);
    }

    // View task details
    public function show($id)
    {
        $task = Task::with(['assignees', 'creator', 'attachments', 'comments'])->findOrFail($id);
        $user = Auth::user();

        // Authorization checks
        if ($user->role === 'client' && $task->creator_id !== $user->id && !$task->assignees->contains($user->id)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        if ($user->role === 'employee' && !$task->assignees->contains($user->id)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return new TaskResource($task);
    }

    // Create a new task via API
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['super_admin', 'admin', 'team_lead'])) {
            return response()->json(['message' => 'Unauthorized to create tasks.'], 403);
        }

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'required|date|after_or_equal:today',
            'assignees' => 'array',
            'assignees.*' => 'exists:users,id',
        ];

        if ($request->priority === 'high') {
            $rules['admin_comment'] = 'required|string|min:5';
        }

        $data = $request->validate($rules);

        // Assignee validations
        if (isset($data['assignees'])) {
            foreach ($data['assignees'] as $userId) {
                $assignee = User::find($userId);
                if ($assignee->status !== 'active') {
                    return response()->json(['message' => "Cannot assign inactive user: {$assignee->name}"], 422);
                }
                if ($assignee->role === 'client') {
                    return response()->json(['message' => "Cannot assign Client role to tasks."], 422);
                }
            }
        }

        $task = Task::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'due_date' => $data['due_date'],
            'creator_id' => $user->id,
            'status' => 'pending',
            'admin_comment' => $request->admin_comment ?? null,
        ]);

        if (isset($data['assignees'])) {
            $task->assignees()->sync(array_unique($data['assignees']));
        }

        ActivityLog::log('api_task_create', "API Created task #{$task->id}");

        return new TaskResource($task->load(['assignees', 'creator', 'attachments']));
    }

    // Add comment via API
    public function storeComment(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string|min:2',
        ]);

        $task = Task::findOrFail($id);
        $user = Auth::user();

        $isAuthorized = in_array($user->role, ['super_admin', 'admin', 'team_lead']) || 
                        $task->creator_id === $user->id || 
                        $task->assignees->contains($user->id);

        if (!$isAuthorized) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($task->status === 'completed') {
            return response()->json(['message' => 'Cannot add comments to a completed task.'], 422);
        }

        $comment = \App\Models\Comment::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'content' => $request->content,
        ]);

        ActivityLog::log('api_comment_add', "API Added comment to task #{$task->id}");

        return response()->json(['message' => 'Comment added.', 'comment' => $comment], 201);
    }

    // Upload attachment via API
    public function uploadFile(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $user = Auth::user();

        $isAuthorized = in_array($user->role, ['super_admin', 'admin', 'team_lead']) || 
                        $task->creator_id === $user->id || 
                        $task->assignees->contains($user->id);

        if (!$isAuthorized) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($task->status === 'completed') {
            return response()->json(['message' => 'Cannot upload files to a completed task.'], 422);
        }

        $maxSize = $task->priority === 'high' ? 10240 : 5120;

        $request->validate([
            'attachment' => "required|file|mimes:jpg,jpeg,png,pdf,docx,zip|max:{$maxSize}",
        ]);

        $file = $request->file('attachment');
        $originalName = $file->getClientOriginalName();

        $existing = \App\Models\TaskAttachment::where('task_id', $task->id)
            ->where('original_name', $originalName)
            ->orderBy('version', 'desc')
            ->first();
        
        $version = $existing ? $existing->version + 1 : 1;

        $uniqueName = time() . '_' . $originalName;
        $path = $file->storeAs('tasks', $uniqueName, 'public');

        $attachment = \App\Models\TaskAttachment::create([
            'task_id' => $task->id,
            'uploader_id' => $user->id,
            'original_name' => $originalName,
            'stored_path' => $path,
            'file_size' => $file->getSize(),
            'version' => $version
        ]);

        ActivityLog::log('api_file_upload', "API Uploaded attachment '{$originalName}' (v{$version}) on task #{$task->id}");

        return response()->json(['message' => 'File uploaded.', 'attachment' => $attachment], 201);
    }
}

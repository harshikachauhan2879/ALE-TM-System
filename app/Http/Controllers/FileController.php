<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class FileController extends Controller
{
    public function store(Request $request, $taskId)
    {
        $task = Task::findOrFail($taskId);
        $user = Auth::user();

        // Security check
        $isAuthorized = in_array($user->role, ['super_admin', 'admin', 'team_lead']) || 
                        $task->creator_id === $user->id || 
                        $task->assignees->contains($user->id);

        if (!$isAuthorized) {
            abort(403, 'Unauthorized.');
        }

        if ($task->status === 'completed') {
            return back()->with('error', 'Cannot upload files to a completed task.');
        }

        // Size rules based on priority
        $maxSize = $task->priority === 'high' ? 10240 : 5120; // 10MB vs 5MB in KB

        $request->validate([
            'attachment' => "required|file|mimes:jpg,jpeg,png,pdf,docx,zip|max:{$maxSize}",
        ], [
            'attachment.max' => "File size cannot exceed " . ($task->priority === 'high' ? '10MB' : '5MB') . " for " . $task->priority . " priority tasks.",
            'attachment.mimes' => "Allowed file types: jpg, png, pdf, docx, zip."
        ]);

        $file = $request->file('attachment');
        $originalName = $file->getClientOriginalName();

        // Versioning check
        $existing = TaskAttachment::where('task_id', $task->id)
            ->where('original_name', $originalName)
            ->orderBy('version', 'desc')
            ->first();
        
        $version = $existing ? $existing->version + 1 : 1;

        // Store file in storage/app/public/tasks (Laravel symlinks to public/storage/tasks)
        // Store with a unique filename to avoid conflict, but keep version reference
        $uniqueName = time() . '_' . $originalName;
        $path = $file->storeAs('tasks', $uniqueName, 'public');

        $attachment = TaskAttachment::create([
            'task_id' => $task->id,
            'uploader_id' => $user->id,
            'original_name' => $originalName,
            'stored_path' => $path,
            'file_size' => $file->getSize(),
            'version' => $version
        ]);

        ActivityLog::log('file_upload', "Uploaded attachment '{$originalName}' (v{$version}) on task #{$task->id}");

        return back()->with('success', "File uploaded successfully as Version {$version}.");
    }

    public function download($id)
    {
        $attachment = TaskAttachment::findOrFail($id);
        $task = $attachment->task;
        $user = Auth::user();

        // Role authorization check
        $isAuthorized = in_array($user->role, ['super_admin', 'admin', 'team_lead', 'employee', 'client']);
        
        // Client reads only assigned project tasks
        if ($user->role === 'client') {
            if ($task->creator_id !== $user->id && ! $task->assignees->contains($user->id)) {
                abort(403, 'Unauthorized.');
            }
        }

        if (!$isAuthorized) {
            abort(403, 'Unauthorized.');
        }

        $filePath = 'public/' . $attachment->stored_path;

        if (!Storage::exists($filePath)) {
            abort(404, 'File not found in storage.');
        }

        return Storage::download($filePath, $attachment->original_name);
    }

    public function destroy($id)
    {
        $attachment = TaskAttachment::findOrFail($id);
        $task = $attachment->task;
        $user = Auth::user();

        // Only uploader, Admin or Team Lead can delete attachment
        $isAuthorized = in_array($user->role, ['super_admin', 'admin', 'team_lead']) || 
                        $attachment->uploader_id === $user->id;

        if (!$isAuthorized) {
            abort(403, 'Unauthorized to delete this file.');
        }

        if ($task->status === 'completed') {
            return back()->with('error', 'Cannot delete files from a completed task.');
        }

        $filePath = 'public/' . $attachment->stored_path;

        if (Storage::exists($filePath)) {
            Storage::delete($filePath);
        }

        $attachment->delete();

        ActivityLog::log('file_delete', "Deleted attachment '{$attachment->original_name}' from task #{$task->id}");

        return back()->with('success', 'File deleted successfully.');
    }
}

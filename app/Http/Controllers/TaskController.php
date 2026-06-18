<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    // Search, Filter, Sort & Display Tasks
    public function index(Request $request)
    {
        $query = Task::with(['assignees', 'creator']);

        $user = Auth::user();

        // Client can only view tasks of projects they are assigned to (or just tasks they are associated with)
        // For simplicity: clients can view tasks where they are "creator" or where they are specifically added,
        // or let's assume they can view tasks associated with them.
        if ($user->role === 'client') {
            $query->where(function($q) use ($user) {
                $q->where('creator_id', $user->id)
                  ->orWhereHas('assignees', function($sq) use ($user) {
                      $sq->where('users.id', $user->id);
                  });
            });
        } elseif ($user->role === 'employee') {
            // Employees view all tasks or only their assigned tasks?
            // The flow diagram: "Employee: View Assigned Tasks". Let's show them tasks they are assigned to.
            $query->whereHas('assignees', function($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('due_date')) {
            $query->whereDate('due_date', $request->due_date);
        }
        if ($request->filled('assignee_id')) {
            $query->whereHas('assignees', function($q) use ($request) {
                $q->where('users.id', $request->assignee_id);
            });
        }

        // Search in Title, Description, comments, user names
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('comments', function($cq) use ($search) {
                      $cq->where('content', 'like', "%{$search}%");
                  })
                  ->orWhereHas('assignees', function($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting
        $sort = $request->get('sort', 'latest');
        if ($sort === 'latest') {
            $query->orderBy('created_at', 'desc');
        } elseif ($sort === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } elseif ($sort === 'due_date') {
            $query->orderBy('due_date', 'asc');
        }

        $tasks = $query->paginate(10)->withQueryString();
        $employees = User::where('role', 'employee')->where('status', 'active')->get();

        return view('dashboard.tasks', compact('tasks', 'employees'));
    }

    public function show($id)
    {
        $task = Task::with(['assignees', 'creator', 'attachments.uploader', 'comments.user'])->findOrFail($id);

        // Client authorization
        if (Auth::user()->role === 'client') {
            if ($task->creator_id !== Auth::id() && ! $task->assignees->contains(Auth::id())) {
                abort(403, 'Unauthorized.');
            }
        }
        // Employee authorization (only view assigned)
        if (Auth::user()->role === 'employee') {
            if (! $task->assignees->contains(Auth::id())) {
                abort(403, 'Unauthorized.');
            }
        }

        $allUsers = User::where('status', 'active')
            ->whereIn('role', ['employee', 'team_lead'])
            ->get();

        return view('dashboard.task_detail', compact('task', 'allUsers'));
    }

    public function create()
    {
        if (! in_array(Auth::user()->role, ['super_admin', 'admin', 'team_lead'])) {
            abort(403, 'Only Admins and Team Leads can create tasks.');
        }

        $employees = User::where('role', 'employee')->where('status', 'active')->get();
        return view('dashboard.create_task', compact('employees'));
    }

    public function store(Request $request)
    {
        if (! in_array(Auth::user()->role, ['super_admin', 'admin', 'team_lead'])) {
            abort(403, 'Only Admins and Team Leads can create tasks.');
        }

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'required|date|after_or_equal:today',
            'assignees' => 'array',
            'assignees.*' => 'exists:users,id',
        ];

        // High priority validation: requires admin comment/note
        if ($request->priority === 'high') {
            $rules['admin_comment'] = 'required|string|min:5';
        }

        $data = $request->validate($rules);

        // Validate assignees logic:
        if (isset($data['assignees'])) {
            foreach ($data['assignees'] as $userId) {
                $user = User::findOrFail($userId);
                if ($user->status !== 'active') {
                    return back()->withErrors(['assignees' => "Cannot assign inactive user: {$user->name}"])->withInput();
                }
                if ($user->role === 'client') {
                    return back()->withErrors(['assignees' => "Cannot assign Client role to a task."])->withInput();
                }
            }
        }

        $task = Task::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'due_date' => $data['due_date'],
            'creator_id' => Auth::id(),
            'status' => 'pending',
            'admin_comment' => $request->admin_comment ?? null,
        ]);

        if (isset($data['assignees'])) {
            // No duplicates due to array_unique
            $task->assignees()->sync(array_unique($data['assignees']));
        }

        ActivityLog::log('task_create', "Created task #{$task->id}: {$task->title}");

        return redirect()->route('tasks.show', $task->id)->with('success', 'Task created successfully.');
    }

    public function edit($id)
    {
        $task = Task::findOrFail($id);

        if ($task->status === 'completed') {
            return back()->with('error', 'Completed tasks cannot be edited.');
        }

        if (! in_array(Auth::user()->role, ['super_admin', 'admin', 'team_lead'])) {
            abort(403, 'Only Admins and Team Leads can edit tasks.');
        }

        $employees = User::where('role', 'employee')->where('status', 'active')->get();
        $currentAssignees = $task->assignees->pluck('id')->toArray();

        return view('dashboard.edit_task', compact('task', 'employees', 'currentAssignees'));
    }

    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        if ($task->status === 'completed') {
            return back()->with('error', 'Completed tasks cannot be edited.');
        }

        if (! in_array(Auth::user()->role, ['super_admin', 'admin', 'team_lead'])) {
            abort(403, 'Only Admins and Team Leads can edit tasks.');
        }

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'required|date|after_or_equal:today',
            'assignees' => 'array',
            'assignees.*' => 'exists:users,id',
        ];

        if ($request->priority === 'high' && ! $task->admin_comment && ! $request->admin_comment) {
            $rules['admin_comment'] = 'required|string|min:5';
        }

        $data = $request->validate($rules);

        // Validate assignees logic:
        if (isset($data['assignees'])) {
            foreach ($data['assignees'] as $userId) {
                $user = User::findOrFail($userId);
                if ($user->status !== 'active') {
                    return back()->withErrors(['assignees' => "Cannot assign inactive user: {$user->name}"])->withInput();
                }
                if ($user->role === 'client') {
                    return back()->withErrors(['assignees' => "Cannot assign Client role."])->withInput();
                }
            }
        }

        $task->update([
            'title' => $data['title'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'due_date' => $data['due_date'],
            'admin_comment' => $request->admin_comment ?? $task->admin_comment,
        ]);

        if (isset($data['assignees'])) {
            $task->assignees()->sync(array_unique($data['assignees']));
        } else {
            $task->assignees()->detach();
        }

        ActivityLog::log('task_update', "Updated task #{$task->id}: {$task->title}");

        return redirect()->route('tasks.show', $task->id)->with('success', 'Task updated successfully.');
    }

    public function destroy($id)
    {
        if (! in_array(Auth::user()->role, ['super_admin', 'admin'])) {
            abort(403, 'Only Admins can delete tasks.');
        }

        $task = Task::findOrFail($id);
        $task->delete();

        ActivityLog::log('task_delete', "Deleted task #{$id}");

        return redirect()->route('dashboard')->with('success', 'Task deleted successfully.');
    }

    // Strict Status Flow Engine transitions
    public function transition(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $action = $request->action;
        $user = Auth::user();

        // 1. Approve (Pending -> Approved)
        if ($action === 'approve') {
            if (! in_array($user->role, ['super_admin', 'admin', 'team_lead'])) {
                return back()->with('error', 'Only Admin or Team Lead can approve tasks.');
            }
            if ($task->status !== 'pending') {
                return back()->with('error', 'Task is not in pending status.');
            }

            $task->update(['status' => 'approved']);
            ActivityLog::log('task_status', "Approved task #{$task->id}");
            return back()->with('success', 'Task approved.');
        }

        // 2. Start (Approved -> In Progress)
        if ($action === 'start') {
            if ($task->status !== 'approved') {
                return back()->with('error', 'Task is not approved yet.');
            }
            // Only assigned employee can start task
            if (! $task->assignees->contains($user->id)) {
                return back()->with('error', 'Only assigned employees can start this task.');
            }

            $task->update(['status' => 'in_progress']);
            ActivityLog::log('task_status', "Started task #{$task->id}");
            return back()->with('success', 'Task started.');
        }

        // 3. Submit for Review (In Progress -> QA Review)
        if ($action === 'submit_review') {
            if ($task->status !== 'in_progress') {
                return back()->with('error', 'Task is not in progress.');
            }
            // Only assigned employee can submit
            if (! $task->assignees->contains($user->id)) {
                return back()->with('error', 'Only assigned employees can submit this task.');
            }

            $task->update(['status' => 'qa_review']);
            ActivityLog::log('task_status', "Submitted task #{$task->id} for review");
            return back()->with('success', 'Task submitted for QA review.');
        }

        // 4. Complete (QA Review -> Completed)
        if ($action === 'complete') {
            if (! in_array($user->role, ['super_admin', 'admin', 'team_lead'])) {
                return back()->with('error', 'Only Admin or Team Lead can complete tasks.');
            }
            if ($task->status !== 'qa_review') {
                return back()->with('error', 'Task is not in QA Review.');
            }

            $task->update(['status' => 'completed']);
            ActivityLog::log('task_status', "Completed task #{$task->id}");
            return back()->with('success', 'Task completed.');
        }

        // 5. Reject (QA Review -> In Progress) - requires a comment
        if ($action === 'reject') {
            if (! in_array($user->role, ['super_admin', 'admin', 'team_lead'])) {
                return back()->with('error', 'Only Admin or Team Lead can reject tasks.');
            }
            if ($task->status !== 'qa_review') {
                return back()->with('error', 'Task is not in QA Review.');
            }

            $request->validate([
                'reject_comment' => 'required|string|min:5'
            ]);

            $task->update([
                'status' => 'in_progress',
                'admin_comment' => $request->reject_comment
            ]);

            // Save comment to task comments list
            Comment::create([
                'task_id' => $task->id,
                'user_id' => $user->id,
                'content' => "REJECTED TASK: " . $request->reject_comment,
            ]);

            ActivityLog::log('task_status', "Rejected task #{$task->id} back to In Progress");
            return back()->with('success', 'Task rejected back to In Progress.');
        }

        return back()->with('error', 'Invalid transition action.');
    }

    public function exportReport(Request $request)
    {
        $query = Task::with(['assignees', 'creator']);

        $user = Auth::user();
        if ($user->role === 'client') {
            $query->where(function($q) use ($user) {
                $q->where('creator_id', $user->id)
                  ->orWhereHas('assignees', fn($sq) => $sq->where('users.id', $user->id));
            });
        } elseif ($user->role === 'employee') {
            $query->whereHas('assignees', fn($q) => $q->where('users.id', $user->id));
        }

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('due_date')) {
            $query->whereDate('due_date', $request->due_date);
        }
        if ($request->filled('assignee_id')) {
            $query->whereHas('assignees', fn($q) => $q->where('users.id', $request->assignee_id));
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tasks = $query->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=tasks_report_" . date('Ymd_His') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($tasks) {
            $file = fopen('php://output', 'w');
            
            // CSV Header
            fputcsv($file, ['ID', 'Title', 'Description', 'Status', 'Priority', 'Due Date', 'Creator Name', 'Creator Email', 'Assignee Names', 'Admin Comment', 'Created At']);

            foreach ($tasks as $task) {
                $assigneesStr = $task->assignees->pluck('name')->implode(', ');
                fputcsv($file, [
                    $task->id,
                    $task->title,
                    $task->description,
                    $task->status,
                    $task->priority,
                    $task->due_date,
                    $task->creator?->name,
                    $task->creator?->email,
                    $assigneesStr,
                    $task->admin_comment,
                    $task->created_at?->toDateTimeString()
                ]);
            }

            fclose($file);
        };

        ActivityLog::log('report_export', "Exported tasks CSV report.");

        return response()->stream($callback, 200, $headers);
    }
}

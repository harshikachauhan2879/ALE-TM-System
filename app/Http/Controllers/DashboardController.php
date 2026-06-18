<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Stats
        $stats = [
            'total' => 0,
            'pending' => 0,
            'approved' => 0,
            'in_progress' => 0,
            'qa_review' => 0,
            'completed' => 0,
            'overdue' => 0,
        ];

        $taskQuery = Task::query();
        if ($user->role === 'client') {
            $taskQuery->where(function($q) use ($user) {
                $q->where('creator_id', $user->id)
                  ->orWhereHas('assignees', function($sq) use ($user) {
                      $sq->where('users.id', $user->id);
                  });
            });
        } elseif ($user->role === 'employee') {
            $taskQuery->whereHas('assignees', function($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $allTasks = $taskQuery->get();
        $stats['total'] = $allTasks->count();
        $stats['pending'] = $allTasks->where('status', 'pending')->count();
        $stats['approved'] = $allTasks->where('status', 'approved')->count();
        $stats['in_progress'] = $allTasks->where('status', 'in_progress')->count();
        $stats['qa_review'] = $allTasks->where('status', 'qa_review')->count();
        $stats['completed'] = $allTasks->where('status', 'completed')->count();
        $stats['overdue'] = $allTasks->whereIn('status', ['pending', 'approved', 'in_progress'])
            ->where('due_date', '<', now()->toDateString())
            ->count();

        // Recent Tasks
        $recentTasks = $allTasks->sortByDesc('created_at')->take(5);

        // Fetch all users if Admin/Super Admin (to display in user management panel)
        $users = [];
        if (in_array($user->role, ['super_admin', 'admin'])) {
            $users = User::all();
        }

        return view('dashboard.index', compact('stats', 'recentTasks', 'users'));
    }

    // Toggle User Status (Active/Inactive)
    public function toggleUserStatus($id)
    {
        if (!in_array(Auth::user()->role, ['super_admin', 'admin'])) {
            abort(403, 'Unauthorized.');
        }

        $user = User::findOrFail($id);
        
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot deactivate yourself.');
        }

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        ActivityLog::log('user_status_toggle', "Toggled status of {$user->name} to {$newStatus}");

        return back()->with('success', "User status updated to {$newStatus}.");
    }

    // Update User Role
    public function updateUserRole(Request $request, $id)
    {
        if (!in_array(Auth::user()->role, ['super_admin', 'admin'])) {
            abort(403, 'Unauthorized.');
        }

        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot change your own role.');
        }

        $request->validate([
            'role' => 'required|in:super_admin,admin,team_lead,employee,client',
        ]);

        $user->update(['role' => $request->role]);

        ActivityLog::log('user_role_update', "Updated role of {$user->name} to {$request->role}");

        return back()->with('success', "User role updated to {$request->role}.");
    }

    // Run Daily Scheduler Simulation manually
    public function runScheduler()
    {
        if (!in_array(Auth::user()->role, ['super_admin', 'admin'])) {
            abort(403, 'Unauthorized.');
        }

        \Illuminate\Support\Facades\Artisan::call('app:check-overdue-tasks');
        $output = \Illuminate\Support\Facades\Artisan::output();

        ActivityLog::log('scheduler_run', "Admin manually triggered scheduler simulation.");

        return back()->with('success', 'Scheduler Run Success: ' . $output);
    }
}

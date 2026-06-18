<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class LogController extends Controller
{
    public function index()
    {
        // View Logs is Super Admin only
        if (Auth::user()->role !== 'super_admin') {
            abort(403, 'Unauthorized.');
        }

        $logs = ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return view('dashboard.logs', compact('logs'));
    }
}

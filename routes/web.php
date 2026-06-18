<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\LogController;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::middleware('role:super_admin,admin')->group(function () {
        Route::post('/users/{id}/toggle-status', [DashboardController::class, 'toggleUserStatus'])->name('users.toggle-status');
        Route::post('/users/{id}/update-role', [DashboardController::class, 'updateUserRole'])->name('users.update-role');
        Route::post('/scheduler/run', [DashboardController::class, 'runScheduler'])->name('scheduler.run');
    });

    // Task Board CRUD
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/export', [TaskController::class, 'exportReport'])->name('tasks.export');
    Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{id}', [TaskController::class, 'show'])->name('tasks.show');
    Route::get('/tasks/{id}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('/tasks/{id}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{id}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('/tasks/{id}/transition', [TaskController::class, 'transition'])->name('tasks.transition');

    // Comments
    Route::post('/tasks/{id}/comments', [CommentController::class, 'store'])->name('comments.store');

    // File Upload / Download / Delete
    Route::post('/tasks/{id}/files', [FileController::class, 'store'])->name('files.store');
    Route::get('/files/{id}/download', [FileController::class, 'download'])->name('files.download');
    Route::delete('/files/{id}', [FileController::class, 'destroy'])->name('files.destroy');

    // Token Management (for API Access)
    Route::get('/tokens', [AuthController::class, 'apiTokens'])->name('tokens.index');
    Route::post('/tokens', [AuthController::class, 'generateToken'])->name('tokens.store');
    Route::delete('/tokens/{id}', [AuthController::class, 'deleteToken'])->name('tokens.destroy');

    // Activity Logs (Super Admin only)
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
    });
});

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tasks', [TaskApiController::class, 'index']);
    Route::get('/tasks/{id}', [TaskApiController::class, 'show']);
    Route::post('/tasks', [TaskApiController::class, 'store']);
    Route::post('/tasks/{id}/comments', [TaskApiController::class, 'storeComment']);
    Route::post('/tasks/{id}/files', [TaskApiController::class, 'uploadFile']);
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BoardController;
use Illuminate\Http\Request;
use App\Models\UserFile;

// 📡 All API routes here are stateless (token-based or public)

// 🏠 General Boards Feed (with optional filters)
Route::middleware('auth:sanctum')->get('/boards', [BoardController::class, 'index']);

// 👤 Public User Boards by Username
Route::get('/users/{username}/boards', [BoardController::class, 'showUserBoards']);

// 🧍‍♂️ Authenticated User's MoodBoards
Route::middleware('auth:sanctum')->get('/my-boards', [BoardController::class, 'myBoards']);

Route::middleware('auth')->group(function () {
    Route::post('/user/files', [FileController::class, 'fetch']);
    Route::get('/user/lists', [FileController::class, 'getListsWithCounts']);
    Route::get('/user/lists/{list}/items', [FileController::class, 'fetchListItems']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/messages/thread/{receiverId}', [\App\Http\Controllers\MessageController::class, 'thread']);
});
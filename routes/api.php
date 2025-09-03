<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\TeaserController;

// 🔑 Standard Sanctum SPA bootstrap route
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// 👤 Alternative alias (optional, same result)
Route::middleware('auth:sanctum')->get('/me', fn (Request $r) => $r->user());

// 🗂️ Board routes

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/boards/latest', [BoardController::class, 'latest']);
    Route::get('/boards', [BoardController::class, 'index']);
    Route::get('/my-boards', [BoardController::class, 'myBoards']);
    Route::get('/messages/thread/{receiverId}', [MessageController::class, 'thread']);
    Route::get('/recent-messages', [MessageController::class, 'recentChats']);
    Route::get('/messages/unread-conversations-count', [MessageController::class, 'unreadConversationsCount']);
});

// 🌍 Public boards (no auth)
Route::get('/saved-boards', [\App\Http\Controllers\BoardController::class, 'savedBoards']);

// 🌍 Public boards (no auth)
Route::get('/users/{username}/boards', [BoardController::class, 'showUserBoards']);
Route::get('/favorited-boards', [BoardController::class, 'favoritedBoards']);

// 📁 Authenticated user file & list routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/user/files', [FileController::class, 'fetch']); // consider renaming if 'fetch' actually retrieves data
    Route::get('/user/lists', [FileController::class, 'getListsWithCounts']);
    Route::get('/user/lists/{list}/items', [FileController::class, 'fetchListItems']);
});

// 🛡️ JSON fallback for API (no HTML)
Route::fallback(function () {
    return response()->json(['message' => 'Not Found'], 404);
});

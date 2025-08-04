<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileSettingsController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ReplyController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\Api\BoardController as ApiBoardController;
use App\Http\Controllers\Api\UserSearchController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FileListController;

// 🚪 Guest landing → redirect to login
Route::get('/', fn () => redirect()->route('login'));

// ✅ Dashboard after login
Route::get('/dashboard', fn () => redirect()->route('home'))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// 👤 Auth routes
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// 🔐 Protected App Routes
Route::middleware('auth')->group(function () {
    // 🏠 Homepage Feed
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // 📋 MoodBoards (CRUD)
    Route::resource('boards', BoardController::class);
    Route::get('/api/boards/me', [ApiBoardController::class, 'myBoards'])->middleware('auth');


    // 🧑‍🚀 Public User Profile View (loads page only, data via API)
    Route::get('/space/{username}', [BoardController::class, 'showUserProfile'])->name('space.show');

    // 💬 Comments & Replies
    Route::post('/boards/{board}/comments', [CommentController::class, 'store']);
    Route::post('/comments/{id}/react', [CommentController::class, 'react']);
    Route::post('/comments/{comment}/replies', [ReplyController::class, 'store']);
    Route::get('/comments/{id}/replies', [ReplyController::class, 'viewReplies'])->name('comments.replies');

    // 💌 Messages
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [App\Http\Controllers\MessageController::class, 'store'])->name('messages.store');
    Route::get('/messages/load', [MessageController::class, 'loadMore'])->name('messages.loadMore');
    Route::get('/api/recent-messages', [MessageController::class, 'recentChats']);
    Route::post('/messages/mark-read', [MessageController::class, 'markAsRead'])->middleware('auth');
    Route::post('/messages/send-files', [MessageController::class, 'sendWithFiles'])->name('messages.send-files');

    // In routes/web.php
Route::post('/messages/send-with-files', [MessageController::class, 'sendWithFiles'])->middleware('auth');

    // 💖 Reactions
    Route::post('/reaction', [ReactionController::class, 'store'])->name('reaction.store');

    // 📡 API Endpoints (Frontend Fetch Only)
    Route::get('/api/boards', [ApiBoardController::class, 'index']);
    Route::get('/api/users/{username}/boards', [ApiBoardController::class, 'showUserBoards']);
    Route::get('/api/search-users', [UserSearchController::class, 'search']);

    Route::post('/follow/{user}', [\App\Http\Controllers\FollowController::class, 'toggle'])->middleware('auth');

    Route::prefix('files')->middleware('auth')->group(function () {
    Route::get('/', [FileController::class, 'index'])->name('files.index');
    Route::get('/create', [FileController::class, 'create'])->name('files.create');
    Route::post('/', [FileController::class, 'store'])->name('files.store');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/profile/update-username', [ProfileSettingsController::class, 'updateUsername']);
    Route::post('/profile/update-picture', [ProfileSettingsController::class, 'updateProfilePicture']);
    Route::post('/profile/update-password', [ProfileSettingsController::class, 'updatePassword']);
    Route::get('/api/my-boards', [ApiBoardController::class, 'myBoards']);
});

// 🎭 Me page — for logged-in user to view their own moodboards
Route::get('/my-vibes', function () {
    return view('boards.me');
})->middleware('auth')->name('boards.me');


// ✅ Custom frontend fetch routes (session-authenticated, CSRF protected)
Route::get('/file-lists', [FileListController::class, 'index'])->middleware('auth');
Route::post('/file-lists', [FileListController::class, 'store'])->middleware('auth');
Route::get('/user-files', [FileController::class, 'fetch'])->middleware('auth');

Route::post('/file-lists/{list}/attach', [FileListController::class, 'attach'])->middleware('auth');

Route::get('/file-lists/{list}/items', [FileController::class, 'fetchListItems'])->middleware('auth');
Route::get('/user-files', [FileController::class, 'fetch'])->middleware('auth');

Route::get('/file-lists/{id}/stats', [FileListController::class, 'stats'])->middleware('auth');

Route::put('/file-lists/{id}', [FileListController::class, 'update'])->middleware('auth');
Route::delete('/file-lists/{id}', [FileListController::class, 'destroy'])->middleware('auth');

Route::post('/file-lists/{list}/attach', [FileListController::class, 'attach'])->middleware('auth');
Route::post('/file-lists/{list}/detach', [FileListController::class, 'detach'])->middleware('auth');
Route::post('/user-files/delete', [FileListController::class, 'bulkDeleteOrRemove'])->middleware('auth');
Route::get('/files/file/lists-with-counts', [FileController::class, 'getListsWithCounts'])->name('file.lists');});
Route::get('/moodboards/{mood_board}', [BoardController::class, 'show'])
    ->name('moodboards.show');
Route::get('/files/lists-with-counts', [FileController::class, 'getListsWithCounts'])
    ->middleware('auth');

Route::get('/migrate-user-files', function () {
    Artisan::call('migrate', [
        '--path' => 'database/migrations/2025_07_13_123333_create_user_files_table.php'
    ]);

    return 'User files table migrated 🗃️';
});

Route::get('/migrate-messages', function () {
    Artisan::call('migrate', [
        '--path' => 'database/migrations/2025_07_13_133355_create_messages_table.php'
    ]);

    return 'Messages table migrated 💬';
});

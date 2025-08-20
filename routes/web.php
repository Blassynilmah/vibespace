<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Controllers
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
use App\Http\Controllers\SpaceController;

/*
|--------------------------------------------------------------------------
| Public / Guest Routes
|--------------------------------------------------------------------------
*/

// Landing → login
Route::get('/', fn () => redirect()->route('login'));

// Auth pages
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Sanctum's expected current user route
Route::middleware('auth:sanctum')->get('/api/user', fn (Request $r) => $r->user());

/*
|--------------------------------------------------------------------------
| Protected Web App Routes (auth:sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Dashboard / Home
    Route::get('/dashboard', fn () => redirect()->route('home'))->name('dashboard');
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // MoodBoards
    Route::resource('boards', BoardController::class);
    Route::get('/api/boards/me', [ApiBoardController::class, 'myBoards']);
    Route::post('/toggle-favorite', [BoardController::class, 'toggleFavorite']);
    Route::post('/moodboards/toggle-save', [BoardController::class, 'toggleSave'])->name('moodboards.toggleSave');

    Route::get('/api/boards/latest', [\App\Http\Controllers\Api\BoardController::class, 'latest']);
    Route::get('/api/boards', [\App\Http\Controllers\Api\BoardController::class, 'index']);

    // Profile
    Route::post('/profile/update-username', [ProfileSettingsController::class, 'updateUsername']);
    Route::post('/profile/update-picture', [ProfileSettingsController::class, 'updateProfilePicture']);
    Route::post('/profile/update-password', [ProfileSettingsController::class, 'updatePassword']);

    // Boards API
    Route::get('/api/boards', [ApiBoardController::class, 'index']);
    Route::get('/api/users/{username}/boards', [ApiBoardController::class, 'showUserBoards']);
    Route::get('/api/search-users', [UserSearchController::class, 'search']);

    // Messages
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::get('/messages/load', [MessageController::class, 'loadMore'])->name('messages.loadMore');
    Route::get('/api/recent-messages', [MessageController::class, 'recentChats']);
    Route::post('/messages/mark-read', [MessageController::class, 'markAsRead']);
    Route::post('/messages/send-files', [MessageController::class, 'sendWithFiles'])->name('messages.send-files');
    Route::get('/api/messages/thread/{receiverId}', [MessageController::class, 'thread']);

    // Comments & Replies
    Route::post('/boards/{board}/comments', [CommentController::class, 'store']);
    Route::post('/comments/{id}/react', [CommentController::class, 'react']);
    Route::post('/comments/{comment}/replies', [ReplyController::class, 'store']);
    Route::get('/comments/{id}/replies', [ReplyController::class, 'viewReplies'])->name('comments.replies');
    Route::post('/reaction', [ReactionController::class, 'store'])->name('reaction.react');

    // Files
    Route::prefix('files')->group(function () {
        Route::get('/', [FileController::class, 'index'])->name('files.index');
        Route::get('/create', [FileController::class, 'create'])->name('files.create');
        Route::post('/', [FileController::class, 'store'])->name('files.store');
        Route::get('/file/lists-with-counts', [FileController::class, 'getListsWithCounts'])->name('file.lists');
        Route::get('/lists-with-counts', [FileController::class, 'getListsWithCounts']);
    });

    // File Lists
    Route::get('/file-lists', [FileListController::class, 'index']);
    Route::post('/file-lists', [FileListController::class, 'store']);
    Route::get('/file-lists/{list}/items', [FileController::class, 'fetchListItems']);
    Route::get('/file-lists/{id}/stats', [FileListController::class, 'stats']);
    Route::put('/file-lists/{id}', [FileListController::class, 'update']);
    Route::delete('/file-lists/{id}', [FileListController::class, 'destroy']);
    Route::post('/file-lists/{list}/attach', [FileListController::class, 'attach']);
    Route::post('/file-lists/{list}/detach', [FileListController::class, 'detach']);
    Route::post('/user-files/delete', [FileListController::class, 'bulkDeleteOrRemove']);

    // Standalone file APIs
    Route::get('/user-files', [FileController::class, 'fetch']);

    // Follows
    Route::post('/follow/{user}', [FollowController::class, 'toggle']);

    // My Vibes page
    Route::get('/my-vibes', fn () => view('boards.me'))->name('boards.me');

    // Space
    Route::get('/space/{slug}', [SpaceController::class, 'show']);
});

Route::middleware('auth:sanctum')->get('/saved-boards', [BoardController::class, 'savedBoards']);

/*
|--------------------------------------------------------------------------
| Public Content
|--------------------------------------------------------------------------
*/
Route::get('/moodboards/{mood_board}', [BoardController::class, 'show'])->name('moodboards.show');

/*
|--------------------------------------------------------------------------
| SPA Catch‑All (must be last)
|--------------------------------------------------------------------------
*/

// Keep your SPA catch‑all LAST
Route::get('/app/{any?}', function () {
    return view('layouts.app');
})->where('any', '.*')->middleware('auth:sanctum');
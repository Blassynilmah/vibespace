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

    // 🎯 API endpoint to fetch a message thread between auth user and another user
Route::middleware(['auth'])->group(function () {
    Route::get('/api/messages/thread/{receiverId}', [MessageController::class, 'thread']);
});

Route::get('/migrate-users', function () {
    require_once database_path('migrations/0001_01_01_000000_create_users_table.php');
    (new CreateUsersTable)->up();
    return '✅ users table migrated.';
});

Route::get('/migrate-series', function () {
    require_once database_path('migrations/2025_07_05_172459_create_series_table.php');
    (new CreateSeriesTable)->up();
    return '✅ series table migrated.';
});

Route::get('/migrate-posts', function () {
    require_once database_path('migrations/2025_07_05_172514_create_posts_table.php');
    (new CreatePostsTable)->up();
    return '✅ Posts table migrated.';
});

Route::get('/migrate-reactions', function () {
    require_once database_path('migrations/2025_07_05_172533_create_reactions_table.php');
    (new CreateReactionsTable)->up();
    return '✅ Reactions table migrated.';
});

Route::get('/migrate-fan-mixes', function () {
    require_once database_path('migrations/2025_07_05_172628_create_fan_mixes_table.php');
    (new CreateFanMixesTable)->up();
    return '✅ FanMixes table migrated.';
});

Route::get('/migrate-moodboard-image', function () {
    require_once database_path('migrations/2025_07_07_140236_add_image_to_mood_boards_table.php');
    (new AddImageToMoodBoardsTable)->up();
    return '✅ Moodboard image column migrated.';
});

Route::get('/migrate-comments', function () {
    require_once database_path('migrations/2025_07_07_150211_create_comments_table.php');
    (new CreateCommentsTable)->up();
    return '✅ Comments table migrated.';
});

Route::get('/migrate-replies', function () {
    require_once database_path('migrations/2025_07_11_170655_create_replies_table.php');
    (new CreateRepliesTable)->up();
    return '✅ Replies table migrated.';
});

Route::get('/migrate-comment-reactions', function () {
    require_once database_path('migrations/2025_07_11_191021_create_comment_reactions_table.php');
    (new CreateCommentReactionsTable)->up();
    return '✅ Comment reactions table migrated.';
});

Route::get('/migrate-moodboard-title-null', function () {
    require_once database_path('migrations/2025_07_12_173013_make_title_nullable_in_mood_boards_table.php');
    (new MakeTitleNullableInMoodBoardsTable)->up();
    return '✅ Moodboard title nullable migrated.';
});

Route::get('/migrate-moodboard-video', function () {
    require_once database_path('migrations/2025_07_13_075613_add_video_to_mood_boards_table.php');
    (new AddVideoToMoodBoardsTable)->up();
    return '✅ Moodboard video column migrated.';
});

Route::get('/migrate-profile-pictures', function () {
    require_once database_path('migrations/2025_07_13_175818_create_profile_pictures_table.php');
    (new CreateProfilePicturesTable)->up();
    return '✅ Profile pictures table migrated.';
});

Route::get('/migrate-follows', function () {
    require_once database_path('migrations/2025_07_14_090732_create_follows_table.php');
    (new CreateFollowsTable)->up();
    return '✅ Follows table migrated.';
});

Route::get('/migrate-message-columns', function () {
    require_once database_path('migrations/2025_07_14_122335_add_is_read_and_attachments_to_messages_table.php');
    (new AddIsReadAndAttachmentsToMessagesTable)->up();
    return '✅ Extra message columns migrated.';
});

Route::get('/migrate-user-files-type', function () {
    require_once database_path('migrations/2025_07_18_195824_add_content_type_to_user_files_table.php');
    (new AddContentTypeToUserFilesTable)->up();
    return '✅ User files content type column migrated.';
});

Route::get('/migrate-personal-tokens', function () {
    require_once database_path('migrations/2025_07_18_210335_create_personal_access_tokens_table.php');
    (new CreatePersonalAccessTokensTable)->up();
    return '✅ Personal access tokens table migrated.';
});

Route::get('/migrate-file-lists', function () {
    require_once database_path('migrations/2025_07_21_175814_create_file_lists_table.php');
    (new CreateFileListsTable)->up();
    return '✅ File lists table migrated.';
});

Route::get('/migrate-file-list-items', function () {
    require_once database_path('migrations/2025_07_21_175851_create_file_list_items_table.php');
    (new CreateFileListItemsTable)->up();
    return '✅ File list items table migrated.';
});

Route::get('/migrate-attachments', function () {
    require_once database_path('migrations/2025_08_01_161838_create_attachments_table.php');
    (new CreateAttachmentsTable)->up();
    return '✅ Attachments table migrated.';
});
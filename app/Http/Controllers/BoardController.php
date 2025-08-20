<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Models\MoodBoard;
use App\Models\User;
use Illuminate\Support\Str;
use App\Models\UserFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\UserFavoriteMoodboard;
use Illuminate\Support\Facades\Log;
use App\Models\SavedMoodboard;

class BoardController extends Controller
{
    // 🌿 Show Recent MoodBoards (Home Page)
public function index()
{
    $boards = MoodBoard::with(['user.profilePicture'])->latest()->take(10)->get();

    $boards->each(function ($board) {
        $board->user->profile_picture = $board->user->profilePicture?->path ?? null;
    });

    return view('home', compact('boards'));
}


    // 📝 Show Create Form
    public function create()
    {
        return view('boards.create');
    }


public function store(Request $request)
{
    \Log::info('🟢 Starting moodboard creation', [
        'user_id' => auth()->id(),
        'raw_input' => $request->all(),
        'input_types' => collect($request->all())->map(fn($v) => gettype($v))->toArray(),
    ]);

    try {
        // Defensive casting for mood
        $request->merge([
            'latest_mood' => (string) $request->input('latest_mood'),
        ]);

        // Validate input
        $validated = $request->validate([
            'title'        => 'nullable|string|max:255',
            'description'  => 'nullable|string|max:1000',
            'latest_mood'  => 'required|string|in:excited,happy,chill,thoughtful,sad,flirty,mindblown,love',
            'image_ids'    => 'nullable',
            'image_ids.*'  => 'integer|exists:user_files,id',
        ]);

        // Normalize image_ids
        $raw = $request->input('image_ids');
        $fileIds = is_array($raw) ? $raw : (is_numeric($raw) ? [(int) $raw] : []);

        // Fetch files
        $userFiles = \App\Models\UserFile::whereIn('id', $fileIds)
            ->where('user_id', auth()->id())
            ->get();

        $imageFiles = $userFiles->filter(fn($f) => preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f->filename));
        $videoFiles = $userFiles->filter(fn($f) => preg_match('/\.(mp4|mov|avi|webm)$/i', $f->filename));

        // Validation: Only one video or up to 20 images, not both
        if ($videoFiles->count() > 1) {
            return response()->json([
                'success' => false,
                'error' => 'You can only upload one video.',
            ], 422);
        }
        if ($imageFiles->count() > 20) {
            return response()->json([
                'success' => false,
                'error' => 'You can upload up to 20 images.',
            ], 422);
        }
        if ($videoFiles->count() && $imageFiles->count()) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot mix images and video.',
            ], 422);
        }

        // Must have title or at least one file
        $hasTitle = filled($validated['title'] ?? null);
        $hasFiles = count($fileIds) > 0;
        if (! ($hasTitle || $hasFiles)) {
            \Log::warning('⚠️ Validation failed - no title or files');
            return response()->json([
                'success' => false,
                'error'   => 'Please provide at least a title or some media.',
            ], 422);
        }

        // Create the MoodBoard
        $board = MoodBoard::create([
            'title'       => $validated['title'] ?? null,
            'description' => $validated['description'] ?? null,
            'latest_mood' => $validated['latest_mood'],
            'user_id'     => auth()->id(),
            'image'       => null,
            'video'       => null,
        ]);

        \Log::debug('🧱 Board model created', ['board' => $board->toArray()]);

        $storedImages = [];
        $storedVideo = null;

        if (!empty($fileIds)) {
            foreach ($fileIds as $fileId) {
                $file = \App\Models\UserFile::where('id', $fileId)
                    ->where('user_id', auth()->id())
                    ->first();

                if ($file) {
                    $sourcePath = 'user_files/' . auth()->id() . '/' . basename($file->path);
                    $extension  = pathinfo($sourcePath, PATHINFO_EXTENSION);
                    $targetDir  = 'moodboard_uploads/' . $board->id . '/';
                    $targetName = \Illuminate\Support\Str::random(40) . '.' . $extension;
                    $targetPath = $targetDir . $targetName;

                    \Storage::disk('public')->makeDirectory($targetDir);

                    if (\Storage::disk('public')->exists($sourcePath)) {
                        \Storage::disk('public')->copy($sourcePath, $targetPath);

                        $isVideo = preg_match('/\.(mp4|mov|avi|webm)$/i', $targetPath);
                        if ($isVideo) {
                            $storedVideo = $targetPath;
                        } else {
                            $storedImages[] = $targetPath;
                        }
                    }
                }
            }

            // Save paths to the board
            if ($storedVideo) {
                $board->video = $storedVideo;
            }
            if (!empty($storedImages)) {
                $board->image = json_encode($storedImages);
            }
            $board->save();
        }

        \Log::info('✅ Board saved successfully', [
            'board_id'  => $board->id,
            'savedData' => $board->toArray(),
        ]);

        return response()->json([
            'success'  => true,
            'redirect' => route('moodboards.show', $board->id),
        ]);
    }
    catch (\Illuminate\Validation\ValidationException $e) {
        \Log::error('🛑 Validation exception', [
            'errors' => $e->errors(),
        ]);
        return response()->json([
            'success' => false,
            'errors'  => $e->errors(),
        ], 422);
    }
    catch (\Exception $e) {
        \Log::error('🔥 Unexpected error', [
            'message' => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
        ]);
        return response()->json([
            'success' => false,
            'error'   => 'Something went wrong: ' . $e->getMessage(),
        ], 500);
    }
}

public function show($id)
{
    $board = MoodBoard::with([
        'user',
        'posts',
        'comments.user',
        'comments.replies',
        'reactions' => fn($q) => $q->where('user_id', auth()->id()),
    ])->withCount('comments')->findOrFail($id);

    // Reaction counts
    $reactionCounts = $board->reactions()
        ->selectRaw('mood, COUNT(*) as count')
        ->groupBy('mood')
        ->pluck('count', 'mood')
        ->toArray();

    foreach (['fire', 'love', 'funny', 'mind-blown', 'cool', 'crying', 'clap', 'flirty'] as $mood) {
        $board->{$mood . '_count'} = $reactionCounts[$mood] ?? 0;
    }

    $board->user_reacted_mood = optional($board->reactions->first())->mood;

    // Comments meta
    $board->comments->each(function ($comment) {
        $comment->reply_count = $comment->replies->count();
        $comment->like_count = $comment->commentReactions()->where('type', 'like')->count();
        $comment->dislike_count = $comment->commentReactions()->where('type', 'dislike')->count();
        $userReaction = $comment->commentReactions()->where('user_id', auth()->id())->first();
        $comment->user_reacted_type = $userReaction?->type;
    });

    // 🖼️ Prepare media for the view
    $images = [];
    if ($board->image) {
        $decoded = json_decode($board->image, true);
        $images = is_array($decoded) ? $decoded : [$decoded];
    }
    $video = $board->video;

    return view('boards.show', compact('board', 'images', 'video'));
}

    // 🛠️ Edit Board Form
    public function edit($id)
    {
        $board = MoodBoard::findOrFail($id);
        return view('boards.edit', compact('board'));
    }

    // 🔁 Update Existing Board
    public function update(Request $request, $id)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $board = MoodBoard::findOrFail($id);
        $board->update([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        return redirect()->route('boards.show', $board->id)->with('success', 'Board updated ✅');
    }

    // ❌ Delete MoodBoard
    public function destroy($id)
    {
        $board = MoodBoard::findOrFail($id);
        $board->delete();

        return redirect()->route('home')->with('success', 'Board deleted 🗑️');
    }
// 👤 Public User Profile Page
public function showUserProfile($username)
{
    $user = User::with('profilePicture')
        ->withCount(['followers', 'following'])
        ->where('username', $username)
        ->firstOrFail();

    $viewerId = auth()->id();

    $isFollowing = false;

    if ($viewerId) {
        $isFollowing = $user->followers()->where('follower_id', $viewerId)->exists();
    }

    return view('space.show', [
        'user' => $user,
        'username' => $username,
        'viewerId' => $viewerId,
        'isFollowing' => $isFollowing,
        'followerCount' => $user->followers_count,
    ]);
}

public function me()
{
    $user = auth()->user();

    $moodboards = MoodBoard::where('user_id', $user->id)
        ->latest()
        ->get();

    // Prepare images and video for each board
    foreach ($moodboards as $board) {
        // Decode images
        $decoded = [];
        if ($board->image) {
            $decoded = json_decode($board->image, true);
            $decoded = is_array($decoded) ? $decoded : [$decoded];
        }
        // Store as array of paths (relative, for Alpine)
        $board->images = $decoded;

        // Prepare video path (relative, for Alpine)
        $board->video = $board->video ? $board->video : null;
    }

    return view('boards.me', compact('moodboards'));
}

public function toggleFavorite(Request $request)
{
    $user = auth()->user();
    $moodboardId = $request->input('moodboard_id');

    Log::debug('[toggleFavorite] User:', ['id' => $user->id, 'moodboard_id' => $moodboardId]);

    // 🔐 Ensure the moodboard belongs to the user
    $moodboard = MoodBoard::where('id', $moodboardId)
        ->where('user_id', $user->id)
        ->first();

    if (!$moodboard) {
        return response()->json([
            'success' => false,
            'message' => 'Moodboard not found or unauthorized.',
        ], 404);
    }

    // 🕒 Rate limit: max 5 clicks per minute
    $cacheKey = "favorite_clicks_{$user->id}_{$moodboardId}";
    $clickCount = Cache::get($cacheKey, 0);

    if ($clickCount >= 5) {
        return response()->json([
            'success' => false,
            'message' => 'Too many clicks. Please wait a minute before trying again.',
        ], 429);
    }

    Cache::put($cacheKey, $clickCount + 1, now()->addMinutes(1));

    // 💖 Toggle favorite
    $existingFavorite = UserFavoriteMoodboard::where('user_id', $user->id)
        ->where('moodboard_id', $moodboardId)
        ->first();

    if ($existingFavorite) {
        $existingFavorite->delete();

        return response()->json([
            'success' => true,
            'favorited' => false,
        ]);
    }

    // 🚫 Enforce max 3 favorites
    $favoriteCount = $user->favoriteMoodboards()->count();

    if ($favoriteCount >= 3) {
        return response()->json([
            'success' => false,
            'message' => 'You can only favorite up to 3 moodboards.',
        ], 403);
    }

    // ✅ Add new favorite
    $user->favoriteMoodboards()->create([
        'moodboard_id' => $moodboardId,
    ]);

    return response()->json([
        'success' => true,
        'favorited' => true,
    ]);
}

public function toggleSave(Request $request)
{
    Log::info('Toggle save: request received', [
        'route'   => 'moodboards.toggle-save',
        'user_id' => optional($request->user())->id,
        'payload' => $request->only('mood_board_id'),
        'ip'      => $request->ip(),
        'ua'      => $request->userAgent(),
    ]);

    try {
        // Auth guard
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Validate input
        $validated = $request->validate([
            'mood_board_id' => ['required', 'integer', 'exists:mood_boards,id'],
        ]);

        $boardId = $validated['mood_board_id'];

        DB::beginTransaction();

        $existing = SavedMoodboard::where('user_id', $user->id)
            ->where('mood_board_id', $boardId)
            ->lockForUpdate()
            ->first();

        if ($existing) {
            $existing->delete();
            DB::commit();

            Log::info('Toggle save: unsaved', [
                'user_id'       => $user->id,
                'mood_board_id' => $boardId,
            ]);

            return response()->json(['is_saved' => false]);
        }

        SavedMoodboard::create([
            'user_id'       => $user->id,
            'mood_board_id' => $boardId,
        ]);

        DB::commit();

        Log::info('Toggle save: saved', [
            'user_id'       => $user->id,
            'mood_board_id' => $boardId,
        ]);

        return response()->json(['is_saved' => true]);

    } catch (ValidationException $e) {
        Log::warning('Toggle save: validation failed', [
            'errors' => $e->errors(),
        ]);
        throw $e;
    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('Toggle save: exception', [
            'user_id'       => optional($request->user())->id,
            'mood_board_id' => $request->input('mood_board_id'),
            'error'         => $e->getMessage(),
        ]);
        return response()->json([
            'message' => 'Something went wrong while toggling save',
        ], 500);
    }
}
}


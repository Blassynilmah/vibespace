<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MoodBoard;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;


class BoardController extends Controller
{
    // 🏠 Home Feed / Filtered Boards
    public function index(Request $request)
    {
        $viewerId = optional($request->user())->id ?? 0;

        $query = MoodBoard::query()
            ->with([
                'user',
                'favorites' => fn($q) => $q->where('user_id', $viewerId),
                'reactions' => fn($q) => $q->where('user_id', $viewerId)
            ])
            ->withCount([
                'posts',
                'comments',
                'saves as is_saved' => fn($q) => $q->where('user_id', $viewerId),
            ]);

        // 🎯 Mood Filter
        if ($request->filled('moods')) {
            $query->whereIn('latest_mood', $request->input('moods'));
        }

        // 🖼️ Media Type Filter
        if ($request->filled('types')) {
            $types = $request->input('types');
            $query->where(function ($q) use ($types) {
                if (in_array('image', $types)) {
                    $q->orWhereNotNull('image');
                }
                if (in_array('video', $types)) {
                    $q->orWhereNotNull('video');
                }
                if (in_array('text', $types)) {
                    $q->orWhere(function ($subQ) {
                        $subQ->whereNull('image')->whereNull('video');
                    });
                }
            });
        }

        $boards = $query->latest()->paginate(10);

        return response()->json([
            'data' => $boards->map(fn($board) => $this->formatBoard($board)),
            'next_page_url' => $boards->nextPageUrl(),
            'current_page' => $boards->currentPage(),
            'last_page' => $boards->lastPage(),
        ]);
    }

public function showUserBoards($username)
{
    $viewerId = auth()->id();

    $user = User::with(['profilePicture'])
        ->withCount(['followers', 'following'])
        ->where('username', $username)
        ->firstOrFail();

    $isFollowing = $viewerId
        ? $user->followers()->where('follower_id', $viewerId)->exists()
        : false;

    $boards = MoodBoard::where('user_id', $user->id)
        ->with([
            'user.profilePicture',
            'favorites' => fn($q) => $q->where('user_id', $viewerId),
            'reactions' => fn($q) => $q->where('user_id', $viewerId)
        ])
        ->withCount([
            'posts',
            'comments',
            'saves as is_saved' => fn($q) => $q->where('user_id', $viewerId),
        ])
        ->latest()
        ->get();

    return response()->json([
        'user' => [
            'id' => $user->id,
            'username' => $user->username,
            'profile_picture' => $user->profilePicture?->path,
            'joined_at' => $user->created_at,
            'is_following' => $isFollowing,
            'follower_count' => $user->followers_count,
        ],
        'boards' => $boards->map(fn($board) => $this->formatBoard($board))
    ]);
}

public function myBoards(Request $request)
{
    $user = $request->user();

    $boards = MoodBoard::where('user_id', $user->id)
        ->with([
            'user.profilePicture',
            'favorites' => fn($q) => $q->where('user_id', $user->id),
            'reactions' => fn($q) => $q->where('user_id', $user->id)
        ])
        ->withCount([
            'posts',
            'comments',
            'saves as is_saved' => fn($q) => $q->where('user_id', $user->id),
        ])
        ->latest()
        ->get();

    return response()->json($boards->map(fn($board) => $this->formatBoard($board)));
}




private function formatBoard($board)
{
    $counts = $board->reaction_counts;

    return [
        'id' => $board->id,
        'title' => $board->title,
        'description' => $board->description,
        'latest_mood' => $board->latest_mood,
        'created_at' => $board->created_at,
        'user_reacted_mood' => $board->reactions->first()?->mood,
        'post_count' => $board->posts_count,
        'comment_count' => $board->comments_count,
        'reaction_counts' => $counts,
        'fire_count'      => $counts['fire'] ?? 0,
        'flirty_count'    => $counts['flirty'] ?? 0,
        'love_count'      => $counts['love'] ?? 0,
        'funny_count'     => $counts['funny'] ?? 0,
        'mindblown_count' => $counts['mindblown'] ?? 0,
        'cool_count'      => $counts['cool'] ?? 0,
        'crying_count'    => $counts['crying'] ?? 0,
        'clap_count'      => $counts['clap'] ?? 0,

        'image' => $board->image ? $this->formatImages($board->image) : [],
        'video' => $board->video ? asset('storage/' . ltrim($board->video, '/')) : null,

        // Keep existing favorite logic intact
        'is_favorited' => $board->favorites->isNotEmpty(),

        // New: "saved" simply mirrors favorite state
        'is_saved' => (bool) ($board->is_saved ?? false),

        'user' => [
            'id' => $board->user->id,
            'username' => $board->user->username,
            'profile_picture' => $board->user->profilePicture?->path ?? null,
            'is_following' => auth()->check()
                ? $board->user->followers()->where('follower_id', auth()->id())->exists()
                : false,
        ]
    ];
}

    private function formatImages($imageJson)
    {
        $decoded = json_decode($imageJson, true);

        if (!is_array($decoded)) {
            $decoded = [$decoded];
        }

        return collect($decoded)->map(function ($path) {
            return asset('storage/' . ltrim($path, '/'));
        })->toArray();
    }

public function latest(Request $request)
{
    // Route should be protected with: Route::middleware('auth:sanctum')->get(...)
    $user = $request->user(); // guaranteed by middleware
    $viewerId = $user->id;

    $perPage = max(1, min(100, $request->integer('per_page', 20)));

    Log::info('BoardController@latest called', [
        'viewer_id' => $viewerId,
        'per_page'  => $perPage,
    ]);

    try {
        $boards = MoodBoard::query()
            ->with(['user'])
            ->with([
                'favorites' => fn ($q) => $q->where('user_id', $viewerId),
                'reactions' => fn ($q) => $q->where('user_id', $viewerId),
                'saves'     => fn ($q) => $q->where('user_id', $viewerId),
            ])
            ->withCount([
                'posts',
                'comments',
                'saves as is_saved' => fn ($q) => $q->where('user_id', $viewerId),
            ])
            ->latest()
            ->paginate($perPage);

        // Map the paginator collection, then put it back
        $mapped = $boards->getCollection()->map(function ($board) use ($viewerId) {
            $savedRecord = $board->saves->first();
            $isSaved = (bool) ($board->is_saved ?? 0);

            Log::info('Board save check', [
                'viewer_id'       => $viewerId,
                'moodboard_id'    => $board->id,
                'is_saved'        => $isSaved,
                'saved_record_id' => $savedRecord?->id,
                'saved_user_id'   => $savedRecord?->user_id,
                'saved_fk'        => $savedRecord?->mood_board_id,
            ]);

            return $this->formatBoard($board);
        });

        $boards->setCollection($mapped);

        return response()->json([
            'data'          => $boards->items(),
            'next_page_url' => $boards->nextPageUrl(),
            'current_page'  => $boards->currentPage(),
            'last_page'     => $boards->lastPage(),
        ]);
    } catch (Throwable $e) {
        Log::error('Error in BoardController@latest', [
            'error'  => $e->getMessage(),
            'trace'  => $e->getTraceAsString(),
            'viewer' => $viewerId,
        ]);

        return response()->json([
            'error' => 'An unexpected error occurred while fetching boards.',
        ], 500);
    }
}

public function favoritedBoards(Request $request)
{
    $viewer = $request->user();
    $username = $request->query('username') ?? $viewer?->username;

    if (!$username) {
        return response()->json(['error' => 'Username is required'], 400);
    }

    $user = User::withCount(['followers', 'following'])
        ->where('username', $username)
        ->first();

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    $isFollowing = $viewer && $viewer->id !== $user->id
        ? $user->followers()->where('follower_id', $viewer->id)->exists()
        : false;

    $boards = MoodBoard::query()
    ->whereHas('favorites', fn($q) => $q->where('user_id', $user->id)) // ← this is the key
    ->with([
        'user.profilePicture',
        'favorites' => fn($q) => $viewer ? $q->where('user_id', $viewer->id) : $q->whereRaw('1 = 0'),
        'reactions' => fn($q) => $viewer ? $q->where('user_id', $viewer->id) : $q->whereRaw('1 = 0'),
    ])
    ->withCount([
        'posts',
        'comments',
        'saves as is_saved' => fn($q) => $viewer ? $q->where('user_id', $viewer->id) : $q->whereRaw('1 = 0'),
    ])
    ->latest()
    ->paginate(10);


    return response()->json([
        'user' => [
            'id' => $user->id,
            'username' => $user->username,
            'profile_picture' => $user->profilePicture?->path,
            'joined_at' => $user->created_at,
            'is_following' => $isFollowing,
            'follower_count' => $user->followers_count,
        ],
        'boards' => $boards->map(fn($board) => $this->formatBoard($board)),
        'next_page_url' => $boards->nextPageUrl(),
        'current_page' => $boards->currentPage(),
        'last_page' => $boards->lastPage(),
    ]);
}

public function savedBoards(Request $request)
{
    $viewer = $request->user();
    $username = $request->query('username') ?? $viewer?->username;

    if (!$username) {
        return response()->json(['error' => 'Username is required'], 400);
    }

    $user = User::withCount(['followers', 'following'])
        ->where('username', $username)
        ->first();

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    $isFollowing = $viewer && $viewer->id !== $user->id
        ? $user->followers()->where('follower_id', $viewer->id)->exists()
        : false;

    $boards = MoodBoard::whereHas('saves', fn($q) =>
            $viewer ? $q->where('user_id', $viewer->id) : $q->whereRaw('1 = 0')
        )
        ->with([
            'user.profilePicture',
            'favorites' => fn($q) => $viewer ? $q->where('user_id', $viewer->id) : $q->whereRaw('1 = 0'),
            'reactions' => fn($q) => $viewer ? $q->where('user_id', $viewer->id) : $q->whereRaw('1 = 0'),
            'saves' => fn($q) => $viewer ? $q->where('user_id', $viewer->id) : $q->whereRaw('1 = 0'),
        ])
        ->withCount([
            'posts',
            'comments',
            'saves as is_saved' => fn($q) => $viewer ? $q->where('user_id', $viewer->id) : $q->whereRaw('1 = 0'),
        ])
        ->latest()
        ->paginate(10);

    return response()->json([
        'user' => [
            'id' => $user->id,
            'username' => $user->username,
            'profile_picture' => $user->profilePicture?->path,
            'joined_at' => $user->created_at,
            'is_following' => $isFollowing,
            'follower_count' => $user->followers_count,
        ],
        'boards' => $boards->map(fn($board) => $this->formatBoard($board)),
        'next_page_url' => $boards->nextPageUrl(),
        'current_page' => $boards->currentPage(),
        'last_page' => $boards->lastPage(),
    ]);
}
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MoodBoard;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Models\Teaser;


class BoardController extends Controller
{
    // 🏠 Home Feed / Filtered Boards
public function index(Request $request)
{
    $viewerId = optional($request->user())->id ?? 0;

    // Get latest 35 moodboards
    $boards = MoodBoard::query()
        ->with([
            'user',
            'favorites' => fn($q) => $q->where('user_id', $viewerId),
            'reactions' => fn($q) => $q->where('user_id', $viewerId)
        ])
        ->withCount([
            'posts',
            'comments',
            'saves as is_saved' => fn($q) => $q->where('user_id', $viewerId),
        ])
        ->latest()
        ->limit(35)
        ->get()
        ->map(function ($board) {
            $board->type = 'board';
            return $board;
        });

    // Get latest 15 teasers
    $teasers = \App\Models\Teaser::with('user')
        ->latest()
        ->limit(15)
        ->get()
        ->map(function ($teaser) {
            $teaser->type = 'teaser';
            return $teaser;
        });

    // Shuffle both arrays
    $boards = $boards->shuffle()->values();
    $teasers = $teasers->shuffle()->values();

    $final = [];
    $teaserIndex = 0;
    $boardIndex = 0;
    $totalBoards = $boards->count();
    $totalTeasers = $teasers->count();

    // Always start with a board if available
    if ($totalBoards > 0) {
        $final[] = $boards[$boardIndex++];
    }

    // Sprinkle teasers, never two in a row
    while ($boardIndex < $totalBoards || $teaserIndex < $totalTeasers) {
        $canInsertTeaser = $teaserIndex < $totalTeasers && (empty($final) || $final[count($final) - 1]->type !== 'teaser');
        $canInsertBoard = $boardIndex < $totalBoards;

        if ($canInsertTeaser && $canInsertBoard) {
            // 30% chance to insert a teaser, 70% board
            if (mt_rand(1, 100) <= 30) {
                $final[] = $teasers[$teaserIndex++];
            } else {
                $final[] = $boards[$boardIndex++];
            }
        } elseif ($canInsertBoard) {
            $final[] = $boards[$boardIndex++];
        } elseif ($canInsertTeaser) {
            $final[] = $teasers[$teaserIndex++];
        } else {
            // Prevent infinite loop if both are false
            break;
        }
    }

    // Format for API response
    $formatted = collect($final)->map(function ($item) use ($viewerId) {
        if ($item->type === 'board') {
            return $this->formatBoard($item) + ['type' => 'board'];
        } else {
            return [
                'id' => $item->id,
                'title' => $item->title ?? '',
                'description' => $item->description ?? '',
                'created_at' => $item->created_at,
                'user' => [
                    'id' => $item->user->id,
                    'username' => $item->user->username,
                    'profile_picture' => $item->user->profilePicture->path ?? null,
                ],
                'type' => 'teaser',
                // Add more teaser fields as needed
            ];
        }
    });

    return response()->json([
        'data' => $formatted,
        'next_page_url' => null, // Not paginated
        'current_page' => 1,
        'last_page' => 1,
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
        // Accept ?username= param, or use logged-in user if available
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

        // If viewer is authenticated, show their saved boards. If guest, show for the requested username only.
        $targetUserId = $user->id;
        $viewerId = $viewer?->id;

        // Only show saved boards for the requested user
        $boards = MoodBoard::whereHas('saves', fn($q) => $q->where('user_id', $targetUserId))
            ->with([
                'user.profilePicture',
                'favorites' => fn($q) => $viewerId ? $q->where('user_id', $viewerId) : $q->whereRaw('1 = 0'),
                'reactions' => fn($q) => $viewerId ? $q->where('user_id', $viewerId) : $q->whereRaw('1 = 0'),
                'saves' => fn($q) => $viewerId ? $q->where('user_id', $viewerId) : $q->whereRaw('1 = 0'),
            ])
            ->withCount([
                'posts',
                'comments',
                'saves as is_saved' => fn($q) => $viewerId ? $q->where('user_id', $viewerId) : $q->whereRaw('1 = 0'),
            ])
            ->latest()
            ->paginate(10);

        // For guests, is_following is always false
        $isFollowing = $viewerId && $viewerId !== $user->id
            ? $user->followers()->where('follower_id', $viewerId)->exists()
            : false;

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
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
public function index(Request $request)
{
    $viewerId = optional($request->user())->id ?? 0;

    $boardsPerPage = 20;
    $teasersPerPage = 3;

    $excludeBoardIds = $request->query('exclude_board_ids', []);
    $excludeTeaserIds = $request->query('exclude_teaser_ids', []);
    if (!is_array($excludeBoardIds)) $excludeBoardIds = explode(',', $excludeBoardIds);
    if (!is_array($excludeTeaserIds)) $excludeTeaserIds = explode(',', $excludeTeaserIds);

    $mediaTypes = array_filter(explode(',', $request->query('media_types', '')));
    $mediaType = count($mediaTypes) === 1 ? $mediaTypes[0] : null;

    $moods = array_filter(explode(',', $request->query('moods', '')));

    // Boards query
    $boardsQuery = MoodBoard::query()
        ->whereNotIn('id', $excludeBoardIds)
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

    if (!empty($moods)) {
        $boardsQuery->whereIn('latest_mood', $moods);
    }

    if ($mediaType) {
        if ($mediaType === 'video') {
            $boardsQuery->whereNotNull('video')->whereNull('image');
        } elseif ($mediaType === 'image') {
            $boardsQuery->whereNotNull('image')->whereNull('video');
        } elseif ($mediaType === 'text') {
            $boardsQuery->whereNull('video')
                ->whereNull('image')
                ->whereNotNull('description');
        }
    }

    $boards = collect();
    if (!$mediaType || $mediaType !== 'teaser') {
        $boards = $boardsQuery
            ->latest()
            ->take($boardsPerPage)
            ->get()
            ->map(fn($board) => $this->formatBoard($board) + ['type' => 'board']);
    }

    // Teasers query
    $teasers = collect();
    if (!$mediaType || $mediaType === 'teaser') {
        $teasers = Teaser::query()
            ->whereNotIn('id', $excludeTeaserIds)
            ->with([
                'user.profilePicture',
                'comments.user', // eager load comment user
                'comments.replies.user', // eager load reply user
                'comments.reactions', // eager load reactions
            ])
            ->withCount('comments')
            ->latest()
            ->take($teasersPerPage)
            ->get()
            ->map(function ($teaser) use ($viewerId) {
                return [
                    'id' => $teaser->id,
                    'title' => $teaser->title ?? '',
                    'description' => $teaser->description ?? '',
                    'created_at' => $teaser->created_at,
                    'video' => $teaser->video ? asset('storage/' . ltrim($teaser->video, '/')) : null,
                    'hashtags' => $teaser->hashtags ?? '',
                    'username' => $teaser->user->username ?? '',
                    'user' => [
                        'id' => $teaser->user->id,
                        'username' => $teaser->user->username,
                        'profile_picture' => $teaser->user->profilePicture->path ?? null,
                    ],
                    'is_saved' => $viewerId ? $teaser->saves()->where('user_id', $viewerId)->exists() : false,
                    'comment_count' => $teaser->comments_count ?? 0,
                    'expires_on' => $teaser->expires_on ?? null,
                    'expires_after' => $teaser->expires_after ?? null,
                    'teaser_mood' => $teaser->teaser_mood,
                    'fire_count' => $teaser->reactions()->where('reaction', 'fire')->count(),
                    'love_count' => $teaser->reactions()->where('reaction', 'love')->count(),
                    'boring_count' => $teaser->reactions()->where('reaction', 'boring')->count(),
                    'user_teaser_reaction' => $viewerId ? $teaser->reactions()->where('user_id', $viewerId)->value('reaction') : null,
                    'type' => 'teaser',
                    // Add comments with reactions and replies
                    'comments' => $teaser->comments->map(function ($comment) {
                        return [
                            'id' => $comment->id,
                            'body' => $comment->body,
                            'created_at' => $comment->created_at,
                            'user' => [
                                'id' => $comment->user->id,
                                'username' => $comment->user->username,
                                'profile_picture' => $comment->user->profilePicture->path ?? null,
                            ],
                            // Reaction counts
                            'like_count' => $comment->reactions->where('reaction_type', 'like')->count(),
                            'dislike_count' => $comment->reactions->where('reaction_type', 'dislike')->count(),
                            // Replies and reply count
                            'replies' => $comment->replies->map(function ($reply) {
                                return [
                                    'id' => $reply->id,
                                    'body' => $reply->body,
                                    'created_at' => $reply->created_at,
                                    'user' => [
                                        'id' => $reply->user->id,
                                        'username' => $reply->user->username,
                                        'profile_picture' => $reply->user->profilePicture->path ?? null,
                                    ],
                                ];
                            }),
                            'reply_count' => $comment->replies->count(),
                        ];
                    }),
                ];
            });
    }

    // Merge logic
    $final = [];
    $teaserIndex = 0;
    $boardIndex = 0;
    $totalBoards = $boards->count();
    $totalTeasers = $teasers->count();

    // Placement positions for teasers (1-based index)
    $teaserPositions = [2, 8, 16]; // for first page
    $page = (int) $request->query('page', 1);

    // For subsequent pages, teasers at 24, 32, etc.
    if ($page > 1) {
        $teaserPositions = [];
        $start = 24 + ($page - 2) * $boardsPerPage;
        for ($i = 0; $i < $teasersPerPage; $i++) {
            $teaserPositions[] = $start + ($i * 8);
        }
    }

    $currentPos = 1;
    $teaserPosIndex = 0;

    while ($boardIndex < $totalBoards || $teaserIndex < $totalTeasers) {
        // Insert teaser if current position matches a teaser position and teasers remain
        if ($teaserPosIndex < count($teaserPositions) && $currentPos === $teaserPositions[$teaserPosIndex] && $teaserIndex < $totalTeasers) {
            $final[] = $teasers[$teaserIndex++];
            $teaserPosIndex++;
        } elseif ($boardIndex < $totalBoards) {
            $final[] = $boards[$boardIndex++];
        }
        $currentPos++;
    }

    // If any teasers left, append them at the end
    while ($teaserIndex < $totalTeasers) {
        $final[] = $teasers[$teaserIndex++];
    }

    return response()->json([
        'data' => $final,
        'sent_board_ids' => $boards->pluck('id')->all(),
        'sent_teaser_ids' => $teasers->pluck('id')->all(),
        'all_loaded' => ($boards->count() < $boardsPerPage) && ($teasers->count() < $teasersPerPage),
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
    // Try to decode as JSON array
    $decoded = json_decode($imageJson, true);

    if (is_array($decoded)) {
        $paths = $decoded;
    } elseif (is_string($imageJson) && !empty($imageJson)) {
        $paths = [$imageJson];
    } else {
        $paths = [];
    }

    return collect($paths)->map(function ($path) {
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
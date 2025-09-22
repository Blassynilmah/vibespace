<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Teaser;
use App\Models\UserFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\TeaserSave;
use App\Models\TeaserComment;
use App\Models\TeaserCommentReaction;
use App\Models\TeaserCommentReply;
use App\Models\FavoriteTeaser;

class TeaserController extends Controller
{

    public function store(Request $request)
{
    \Log::info('Teaser upload initiated', ['user_id' => Auth::id(), 'payload' => $request->all()]);

    try {
        $request->validate([
            'description'    => 'nullable|string|max:1000',
            'hashtags'       => 'nullable|string|max:255',
            'expires_after'  => 'nullable|integer|in:24,48,72,168',
            'video_id'       => 'required|integer|exists:user_files,id',
            'teaser_mood'    => 'required|string|in:hype,funny,shock,love', // <-- add this line
        ]);

        $user = Auth::user();

        $userFile = UserFile::where('id', $request->video_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $sourcePath = 'user_files/' . $user->id . '/' . basename($userFile->path);
        $extension  = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $targetDir  = 'teasers/' . $user->id . '/';
        $targetName = \Str::random(40) . '.' . $extension;
        $targetPath = $targetDir . $targetName;

        \Storage::disk('public')->makeDirectory($targetDir);

        if (!\Storage::disk('public')->exists($sourcePath)) {
            \Log::error('Source video not found', ['path' => $sourcePath]);
            return response()->json(['error' => 'Video file not found.'], 404);
        }

        \Storage::disk('public')->copy($sourcePath, $targetPath);

        // Calculate expires_on on the backend
        $expiresAfter = $request->expires_after ? (int) $request->expires_after : null;
        $expiresOn = $expiresAfter ? now()->addHours($expiresAfter) : null;

        $teaser = Teaser::create([
            'user_id'       => $user->id,
            'teaser_id'     => \Str::uuid(),
            'hashtags'      => $request->hashtags,
            'video'         => $targetPath,
            'expires_after' => $expiresAfter,
            'expires_on'    => $expiresOn,
            'description'   => $request->description,
            'teaser_mood'   => $request->teaser_mood, // <-- add this line
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        \Log::info('Teaser created successfully', ['teaser_id' => $teaser->id]);

        return response()->json([
            'success'  => true,
        ]);
    } catch (\Exception $e) {
        \Log::error('Teaser upload failed', [
            'user_id' => Auth::id(),
            'error'   => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
        ]);

        return response()->json(['error' => 'Upload failed. Please try again later.'], 500);
    }
}

    public function myTeasers(Request $request)
{
    try {
        $user = Auth::user();

        if (!$user) {
            \Log::warning('myTeasers: No authenticated user found');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        \Log::info('myTeasers: Fetching teasers for user', ['user_id' => $user->id]);

        $teasers = Teaser::with('user:id,username')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        $viewerId = $user->id;

        $teasers->getCollection()->transform(function ($teaser) use ($viewerId) {
            return [
                'id' => $teaser->id,
                'description' => $teaser->description ?? '',
                'created_at' => $teaser->created_at,
                'is_favorited' => \App\Models\FavoriteTeaser::where('teaser_id', $teaser->id)
                    ->where('user_id', $viewerId)
                    ->exists(),
                'video' => $teaser->video ?? null,
                'hashtags' => $teaser->hashtags ?? '',
                'username' => $teaser->user->username ?? '',
                'user' => [
                    'id' => $teaser->user->id,
                    'username' => $teaser->user->username,
                ],
                'comment_count' => $teaser->comments()->count(),
                'expires_on' => $teaser->expires_on ?? null,
                'expires_after' => $teaser->expires_after ?? null,
                'teaser_mood' => $teaser->teaser_mood,
                'fire_count' => $teaser->reactions()->where('reaction', 'fire')->count(),
                'love_count' => $teaser->reactions()->where('reaction', 'love')->count(),
                'boring_count' => $teaser->reactions()->where('reaction', 'boring')->count(),
                'user_teaser_reaction' => $teaser->reactions()->where('user_id', $viewerId)->value('reaction'),
                'type' => 'teaser',
                'comments' => $teaser->comments()
                    ->with(['user:id,username', 'reactions', 'replies.user:id,username'])
                    ->orderByDesc('created_at')
                    ->take(5)
                    ->get()
                    ->map(function ($comment) {
                        return [
                            'id' => $comment->id,
                            'body' => $comment->body,
                            'created_at' => $comment->created_at,
                            'user' => [
                                'id' => $comment->user->id,
                                'username' => $comment->user->username,
                            ],
                            'like_count' => $comment->reactions->where('reaction_type', 'like')->count(),
                            'dislike_count' => $comment->reactions->where('reaction_type', 'dislike')->count(),
                            'reply_count' => $comment->replies->count(),
                            'latest_replies' => $comment->replies
                                ->sortByDesc('created_at')
                                ->take(5)
                                ->map(function ($reply) {
                                    return [
                                        'id' => $reply->id,
                                        'body' => $reply->body,
                                        'created_at' => $reply->created_at,
                                        'user' => [
                                            'id' => $reply->user->id,
                                            'username' => $reply->user->username,
                                        ],
                                    ];
                                })->values(),
                        ];
                    })->values(),
            ];
        });

        return response()->json([
            'teasers' => $teasers->items(),
            'current_page' => $teasers->currentPage(),
            'last_page' => $teasers->lastPage(),
            'total' => $teasers->total(),
        ]);
    } catch (\Exception $e) {
        \Log::error('myTeasers: Failed to fetch teasers', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json(['error' => 'Failed to load teasers. Please try again later.'], 500);
    }
}

    public function allTeasers(Request $request)
    {
        $teasers = Teaser::with('user:id,username')
            ->orderByDesc('created_at')
            ->paginate(20);

        // Each teaser will have a 'user' relation with 'id' and 'username'
        return response()->json([
            'teasers' => $teasers->items(),
            'current_page' => $teasers->currentPage(),
            'last_page' => $teasers->lastPage(),
            'total' => $teasers->total(),
        ]);
    }

    public function react(Request $request)
    {
        $request->validate([
            'teaser_id' => 'required|exists:teasers,id',
            'reaction' => 'required|in:fire,love,boring',
            'remove' => 'sometimes|boolean',
        ]);
        $user = $request->user();

        $query = \App\Models\TeaserReaction::where('teaser_id', $request->teaser_id)
            ->where('user_id', $user->id);

        if ($request->boolean('remove')) {
            $query->delete();
            $userReaction = null;
        } else {
            \App\Models\TeaserReaction::updateOrCreate(
                ['teaser_id' => $request->teaser_id, 'user_id' => $user->id],
                ['reaction' => $request->reaction]
            );
            $userReaction = $request->reaction;
        }

        // Get updated counts
        $counts = \App\Models\TeaserReaction::where('teaser_id', $request->teaser_id)
            ->selectRaw("count(*) filter (where reaction = 'fire') as fire_count")
            ->selectRaw("count(*) filter (where reaction = 'love') as love_count")
            ->selectRaw("count(*) filter (where reaction = 'boring') as boring_count")
            ->first();

        return response()->json([
            'fire_count' => $counts->fire_count,
            'love_count' => $counts->love_count,
            'boring_count' => $counts->boring_count,
            'user_reaction' => $userReaction,
        ]);
    }

    public function postComment(Request $request)
    {
        $request->validate([
            'teaser_id' => 'required|exists:teasers,id',
            'body' => 'required|string|max:1000',
        ]);
        $comment = \App\Models\TeaserComment::create([
            'teaser_id' => $request->teaser_id,
            'user_id' => $request->user()->id,
            'body' => $request->body,
        ]);
        $comment->load('user:id,username');
        return response()->json($comment);
    }

    public function getComments($teaserId)
    {
        $comments = \App\Models\TeaserComment::with('user:id,username')
            ->where('teaser_id', $teaserId)
            ->orderByDesc('created_at')
            ->take(50)
            ->get();
        return response()->json($comments);
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'teaser_id' => 'required|exists:teasers,id',
        ]);
        $user = $request->user();
        $teaserId = $request->teaser_id;

        $existing = TeaserSave::where('teaser_id', $teaserId)->where('user_id', $user->id)->first();
        if ($existing) {
            $existing->delete();
            $isSaved = false;
        } else {
            TeaserSave::create([
                'teaser_id' => $teaserId,
                'user_id' => $user->id,
            ]);
            $isSaved = true;
        }
        return response()->json(['is_saved' => $isSaved]);
    }

    public function reactComment(Request $request, $comment)
    {
        $request->validate([
            'reaction_type' => 'required|in:like,dislike',
        ]);
        $user = $request->user();

        \App\Models\TeaserCommentReaction::updateOrCreate(
            ['comment_id' => $comment, 'user_id' => $user->id],
            ['reaction_type' => $request->reaction_type]
        );

        $counts = \App\Models\TeaserCommentReaction::where('comment_id', $comment)
            ->selectRaw("count(*) filter (where reaction_type = 'like') as like_count")
            ->selectRaw("count(*) filter (where reaction_type = 'dislike') as dislike_count")
            ->first();

        return response()->json([
            'like_count' => $counts->like_count,
            'dislike_count' => $counts->dislike_count,
        ]);
    }

    public function replyComment(Request $request, $comment)
    {
        $request->validate([
            'body' => 'required|string|max:1000',
        ]);
        $reply = \App\Models\TeaserCommentReply::create([
            'comment_id' => $comment,
            'user_id' => $request->user()->id,
            'body' => $request->body,
        ]);

        $reply_count = \App\Models\TeaserCommentReply::where('comment_id', $comment)->count();

        return response()->json([
            'reply_count' => $reply_count,
        ]);
    }

    public function getCommentReplies(Request $request, $commentId)
    {
        $offset = (int) $request->query('offset', 0);
        $limit = (int) $request->query('limit', 5);

        $replies = \App\Models\TeaserCommentReply::where('comment_id', $commentId)
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->with('user')
            ->get()
            ->map(function ($reply) {
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
            });

        return response()->json($replies);
    }

    public function toggleFavorite(Request $request)
    {
        $request->validate([
            'teaser_id' => 'required|exists:teasers,id',
        ]);
        $user = $request->user();
        $teaserId = $request->teaser_id;

        // Only allow favoriting own teasers
        $teaser = Teaser::where('id', $teaserId)->where('user_id', $user->id)->first();
        if (!$teaser) {
            return response()->json(['error' => 'You can only favorite your own teasers.'], 403);
        }

        $existing = FavoriteTeaser::where('teaser_id', $teaserId)->where('user_id', $user->id)->first();
        if ($existing) {
            $existing->delete();
            $isFavorited = false;
        } else {
            FavoriteTeaser::create([
                'teaser_id' => $teaserId,
                'user_id' => $user->id,
            ]);
            $isFavorited = true;
        }
        return response()->json(['is_favorited' => $isFavorited]);
    }

    public function myFavoriteTeasers(Request $request)
    {
        $user = $request->user();

        $favoriteTeasers = FavoriteTeaser::with('teaser.user')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get()
            ->map(function ($fav) {
                $teaser = $fav->teaser;
                return [
                    'id' => $teaser->id,
                    'description' => $teaser->description ?? '',
                    'created_at' => $teaser->created_at,
                    'video' => $teaser->video ? asset('storage/' . ltrim($teaser->video, '/')) : null,
                    'hashtags' => $teaser->hashtags ?? '',
                    'username' => $teaser->user->username ?? '',
                    'user' => [
                        'id' => $teaser->user->id,
                        'username' => $teaser->user->username,
                    ],
                    'teaser_mood' => $teaser->teaser_mood,
                    // Add other fields as needed
                ];
            });

        return response()->json([
            'favorite_teasers' => $favoriteTeasers,
        ]);
    }
}

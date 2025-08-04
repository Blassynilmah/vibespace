<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MoodBoard;
use App\Models\User;

class BoardController extends Controller
{
    // 🏠 Home Feed / Filtered Boards
    public function index(Request $request)
    {
        $query = MoodBoard::query()
            ->with([
                'user',
                'reactions' => fn($q) => $q->where('user_id', auth()->id())
            ])
            ->withCount(['posts', 'comments']);

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

        return response()->json($boards->through(fn($board) => $this->formatBoard($board)));
    }

    // 👤 Fetch Boards by Username
public function showUserBoards($username)
{
    $viewerId = auth()->id();
    
    $user = User::with(['profilePicture'])
        ->withCount(['followers', 'following']) // 👈 better than calling count() manually
        ->where('username', $username)
        ->firstOrFail();

    $isFollowing = $viewerId 
        ? $user->followers()->where('follower_id', $viewerId)->exists()
        : false;

    $boards = MoodBoard::where('user_id', $user->id)
        ->with([
            'user.profilePicture',
            'reactions' => fn($q) => $q->where('user_id', $viewerId)
        ])
        ->withCount(['posts', 'comments'])
        ->latest()
        ->get();

    return response()->json([
        'user' => [
            'id' => $user->id,
            'username' => $user->username,
            'profile_picture' => $user->profilePicture?->path,
            'joined_at' => $user->created_at,
            'is_following' => $isFollowing,
            'follower_count' => $user->followers_count, // from withCount
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
            'reactions' => fn($q) => $q->where('user_id', $user->id)
        ])
        ->withCount(['posts', 'comments'])
        ->latest()
        ->get();

    return response()->json($boards->map(fn($board) => $this->formatBoard($board)));
}




    // 🧠 Shared board formatting
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
            'relaxed_count' => $counts['relaxed'] ?? 0,
            'craving_count' => $counts['craving'] ?? 0,
            'hyped_count' => $counts['hyped'] ?? 0,
            'obsessed_count' => $counts['obsessed'] ?? 0,
            'image' => $board->image ? asset('storage/' . $board->image) : null,
            'video' => $board->video ? asset('storage/' . $board->video) : null,

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
}
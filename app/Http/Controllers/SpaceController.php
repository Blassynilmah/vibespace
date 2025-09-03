<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\MoodBoard;
use App\Models\UserFavoriteMoodboard;
use Illuminate\Support\Facades\Log;

class SpaceController extends Controller
{
    public function show($slug)
    {
        // 🧩 Validate and parse slug
        if (!str_contains($slug, '-') || count(explode('-', $slug)) !== 2) {
            Log::warning("❌ Invalid profile slug: {$slug}");
            abort(404, 'Invalid profile slug');
        }

        [$username, $id] = explode('-', $slug);
        Log::info("🔍 Parsed slug", ['username' => $username, 'id' => $id]);

        // 🔍 Find user by ID and case-insensitive username
        $user = User::with('profilePicture')
            ->where('id', $id)
            ->whereRaw('LOWER(username) = ?', [strtolower($username)])
            ->first();

        if (!$user) {
            Log::warning("🚫 User not found", ['slug' => $slug]);
            return view('space.user-not-found', compact('username'));
        }

        // 👀 Viewer context
        $viewerId = auth()->id();
        $isFollowing = $viewerId && $viewerId !== $user->id
            ? (bool) $user->followers()->where('follower_id', $viewerId)->exists()
            : false;

        $followerCount = $user->followers()->count();
        $followingCount = $user->following()->count();

        Log::info("🎯 Loaded user profile", [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'follower_count' => $followerCount,
            'following_count' => $followingCount,
            'is_following' => $isFollowing,
            'profile_picture' => $user->profilePicture?->path,
        ]);

        // 💾 Favorited moodboards from user's own collection
        $favoritedMoodboardIds = UserFavoriteMoodboard::where('user_id', $user->id)
            ->pluck('moodboard_id');

        Log::info("🧠 Favorited moodboard IDs", $favoritedMoodboardIds->toArray());

        $boards = MoodBoard::where('user_id', $user->id)
            ->whereIn('id', $favoritedMoodboardIds)
            ->with([
                'user.profilePicture',
                'favorites' => fn($q) => $viewerId ? $q->where('user_id', $viewerId) : $q->whereRaw('1 = 0'),
                'reactions' => fn($q) => $viewerId ? $q->where('user_id', $viewerId) : $q->whereRaw('1 = 0'),
            ])
            ->withCount(['posts', 'comments'])
            ->latest()
            ->get();

        // 🧠 Format boards with reaction counts
        $formattedBoards = $boards->map(function ($board) {
            $counts = $board->reaction_counts;

            return [
                'id' => $board->id,
                'title' => $board->title,
                'description' => $board->description,
                'latest_mood' => $board->latest_mood,
                'created_at' => $board->created_at,
                'image' => $board->image,
                'video' => $board->video,
                'comment_count' => $board->comments_count,
                'post_count' => $board->posts_count,
                'user_reacted_mood' => $board->reactions->first()?->mood,
                'is_favorited' => $board->favorites->isNotEmpty(),
                'reaction_counts' => $counts,
                'fire_count'      => $counts['fire'] ?? 0,
                'flirty_count'    => $counts['flirty'] ?? 0,
                'love_count'      => $counts['love'] ?? 0,
                'funny_count'     => $counts['funny'] ?? 0,
                'mindblown_count' => $counts['mindblown'] ?? 0,
                'cool_count'      => $counts['cool'] ?? 0,
                'crying_count'    => $counts['crying'] ?? 0,
                'clap_count'      => $counts['clap'] ?? 0,
                'user' => [
                    'id' => $board->user->id,
                    'username' => $board->user->username,
                    'profile_picture' => $board->user->profilePicture?->path,
                    'is_following' => auth()->check()
                        ? $board->user->followers()->where('follower_id', auth()->id())->exists()
                        : false,
                ],
            ];
        });

        Log::info("📦 Final formatted moodboards", $formattedBoards->toArray());

        return view('space.show', [
            'user' => $user,
            'formattedBoards' => $formattedBoards,
            'viewerId' => $viewerId,
            'isFollowing' => $isFollowing,
            'followerCount' => $followerCount,
            'followingCount' => $followingCount,
        ]);
    }
}
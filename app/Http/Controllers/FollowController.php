<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;

class FollowController extends Controller
{
public function toggle(User $user)
{
    $auth = Auth::user();

    if ($auth->id === $user->id) {
        return response()->json(['message' => 'You cannot follow yourself'], 403);
    }

    $isFollowing = $auth->following()->where('following_id', $user->id)->exists();


    if ($isFollowing) {
        $auth->following()->detach($user->id);
    } else {
        $auth->following()->attach($user->id);
    }

    // Send notification to the user being followed/unfollowed (not self)
    if ($auth->id !== $user->id) {
        Notification::create([
            'user_id' => $user->id,
            'reactor_id' => $auth->id,
            'third_party_ids' => null,
            'third_party_message' => null,
            'type'    => $isFollowing ? 'unfollow' : 'follow',
            'data'    => [
                'message' => $auth->name . ($isFollowing ? ' unfollowed you.' : ' followed you.'),
                'reactor_id' => $auth->id,
            ],
            'read_at' => null,
            'is_read' => 0,
        ]);
    }

    // Re-fetch follower count
    $followerCount = $user->followers()->count();
    $followingCount = $user->following()->count();

    return response()->json([
        'message' => $isFollowing ? 'Unfollowed' : 'Followed',
        'isFollowing' => !$isFollowing,
        'followerCount' => $followerCount,
        'followingCount' => $followingCount,
    ]);
}

}


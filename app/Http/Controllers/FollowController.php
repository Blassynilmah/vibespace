<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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


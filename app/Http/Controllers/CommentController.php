<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\MoodBoard;
use App\Models\Reply;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
public function store(Request $request, $boardId)
{
    $request->validate([
        'body' => 'required|string|max:1000',
    ]);

    $board = MoodBoard::findOrFail($boardId); // This line can fail if the board doesn't exist

    $comment = $board->comments()->create([
        'user_id' => auth()->id(),
        'body' => $request->body,
    ]);

    return response()->json([
        'message' => 'Comment added!',
        'comment' => $comment->load('user'),
    ]);
}

public function react(Request $request, $id)
{
    $request->validate([
        'type' => 'required|in:like,dislike',
    ]);

    $comment = Comment::findOrFail($id);

    $reaction = $comment->commentReactions()->updateOrCreate(
        ['user_id' => auth()->id()],
        ['type' => $request->type]
    );

    return response()->json([
        'message' => 'Reaction saved',
        'like_count' => $comment->commentReactions()->where('type', 'like')->count(),
        'dislike_count' => $comment->commentReactions()->where('type', 'dislike')->count(),
    ]);
}

}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reply;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;

class ReplyController extends Controller
{
    public function store(Request $request, $commentId)
    {
        $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $comment = Comment::findOrFail($commentId);

        $reply = $comment->replies()->create([
            'user_id' => Auth::id(),
            'body' => $request->body,
        ]);

        return response()->json([
            'message' => 'Reply created!',
            'reply' => $reply->load('user'),
        ]);
    }
    // 📄 View Specific Comment with Replies
public function viewReplies($id)
{
    $comment = Comment::with(['user', 'replies.user', 'moodBoard.user'])->findOrFail($id);
    $comment->board_user = $comment->moodBoard->user;

    return view('boards.replies', compact('comment'));
}


}


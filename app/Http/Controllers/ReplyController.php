<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reply;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;

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

        // Notify moodboard owner and comment owner (if not the replier)
        $replierId = Auth::id();
        $moodBoardOwnerId = $comment->moodBoard->user_id ?? null;
        $commentOwnerId = $comment->user_id;
        $notified = [];

        // Notify moodboard owner
        if ($moodBoardOwnerId && $moodBoardOwnerId !== $replierId) {
            Notification::create([
                'user_id' => $moodBoardOwnerId,
                'reactor_id' => $replierId,
                'third_party_ids' => json_encode([$commentOwnerId]),
                'third_party_message' => Auth::user()->name . ' replied to a comment on your mood board.',
                'type'    => 'reply',
                'data'    => [
                    'message' => Auth::user()->name . ' replied to a comment on your mood board.',
                    'reply_id' => $reply->id,
                    'comment_id' => $comment->id,
                    'mood_board_id' => $comment->moodBoard->id ?? null,
                    'reactor_id' => $replierId,
                ],
                'read_at' => null,
                'is_read' => 0,
            ]);
            $notified[] = $moodBoardOwnerId;
        }

        // Notify comment owner (if not the replier or already notified)
        if ($commentOwnerId && $commentOwnerId !== $replierId && !in_array($commentOwnerId, $notified)) {
            Notification::create([
                'user_id' => $commentOwnerId,
                'reactor_id' => $replierId,
                'third_party_ids' => json_encode([$moodBoardOwnerId]),
                'third_party_message' => Auth::user()->name . ' replied to your comment.',
                'type'    => 'reply',
                'data'    => [
                    'message' => Auth::user()->name . ' replied to your comment.',
                    'reply_id' => $reply->id,
                    'comment_id' => $comment->id,
                    'mood_board_id' => $comment->moodBoard->id ?? null,
                    'reactor_id' => $replierId,
                ],
                'read_at' => null,
                'is_read' => 0,
            ]);
        }

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


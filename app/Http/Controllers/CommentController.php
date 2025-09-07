<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\MoodBoard;
use App\Models\Reply;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;

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

    // Send notification to the mood board owner (if not commenting on own board)
    if ($board->user_id !== auth()->id()) {
        Notification::create([
            'user_id' => $board->user_id,
            'reactor_id' => auth()->id(),
            'third_party_ids' => null,
            'third_party_message' => null,
            'type'    => 'comment',
            'data'    => [
                'message' => auth()->user()->name . ' commented on your mood board.',
                'mood_board_id' => $board->id,
                'comment_id' => $comment->id,
                'reactor_id' => auth()->id(),
            ],
            'read_at' => null,
            'is_read' => 0,
        ]);
    }

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

    // Notify moodboard owner and comment owner (if not the reactor)
    $reactorId = auth()->id();
    $moodBoardOwnerId = $comment->moodBoard->user_id ?? null;
    $commentOwnerId = $comment->user_id;
    $notified = [];

    // Notify moodboard owner
    if ($moodBoardOwnerId && $moodBoardOwnerId !== $reactorId) {
        Notification::create([
            'user_id' => $moodBoardOwnerId,
            'reactor_id' => $reactorId,
            'third_party_ids' => json_encode([$commentOwnerId]),
            'third_party_message' => auth()->user()->name . ' reacted to a comment on your mood board.',
            'type'    => 'comment_reaction',
            'data'    => [
                'message' => auth()->user()->name . ' reacted to a comment on your mood board.',
                'comment_id' => $comment->id,
                'mood_board_id' => $comment->moodBoard->id ?? null,
                'reaction_type' => $request->type,
                'reactor_id' => $reactorId,
            ],
            'read_at' => null,
            'is_read' => 0,
        ]);
        $notified[] = $moodBoardOwnerId;
    }

    // Notify comment owner (if not the reactor or already notified)
    if ($commentOwnerId && $commentOwnerId !== $reactorId && !in_array($commentOwnerId, $notified)) {
        Notification::create([
            'user_id' => $commentOwnerId,
            'reactor_id' => $reactorId,
            'third_party_ids' => json_encode([$moodBoardOwnerId]),
            'third_party_message' => auth()->user()->name . ' reacted to your comment.',
            'type'    => 'comment_reaction',
            'data'    => [
                'message' => auth()->user()->name . ' reacted to your comment.',
                'comment_id' => $comment->id,
                'mood_board_id' => $comment->moodBoard->id ?? null,
                'reaction_type' => $request->type,
                'reactor_id' => $reactorId,
            ],
            'read_at' => null,
            'is_read' => 0,
        ]);
    }

    return response()->json([
        'message' => 'Reaction saved',
        'like_count' => $comment->commentReactions()->where('type', 'like')->count(),
        'dislike_count' => $comment->commentReactions()->where('type', 'dislike')->count(),
    ]);
}

}

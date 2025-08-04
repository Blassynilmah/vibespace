<?php

namespace App\Http\Controllers;

use App\Models\Reaction;
use Illuminate\Http\Request;

class ReactionController extends Controller
{
    public function store(Request $request)
{
    $request->validate([
        'mood_board_id' => 'required|exists:mood_boards,id',
        'mood' => 'required|in:relaxed,craving,hyped,obsessed',
    ]);

    // 🔍 Get the previous mood BEFORE the update
    $previous = Reaction::where('user_id', auth()->id())
        ->where('mood_board_id', $request->mood_board_id)
        ->first()?->mood;

    // 💾 Save or update the new mood
    $reaction = Reaction::updateOrCreate(
        [
            'user_id' => auth()->id(),
            'mood_board_id' => $request->mood_board_id,
        ],
        [
            'mood' => $request->mood,
        ]
    );

    // 🔁 Return both the new and previous mood
    return response()->json([
        'success' => true,
        'mood' => $reaction->mood,
        'previous' => $previous,
    ]);
}

}

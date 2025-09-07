<?php

namespace App\Http\Controllers;

use App\Models\MoodBoard;
use Illuminate\Http\Request;

class MoodBoardController extends Controller
{
    public function index()
    {
        $boards = MoodBoard::with(['user', 'reactions'])->latest()->get();

        // Attach reaction counts per mood
        foreach ($boards as $board) {
            $board->reaction_counts = $board->reactions->groupBy('mood')->map->count();
        }

        return view('boards.index', compact('boards'));
    }


    public function create()
    {
        return view('boards.create');
    }

    public function show(MoodBoard $board)
    {
        $board->load('posts');
        return view('boards.show', compact('board'));
    }

    public function destroy(MoodBoard $board)
    {
        if ($board->user_id === auth()->id()) {
            $board->delete();
        }

        return redirect()->route('boards.index');
    }
}

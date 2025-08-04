<?php

namespace App\Http\Controllers;

use App\Models\User;

class SpaceController extends Controller
{
    public function show($username)
    {
        $user = User::where('username', $username)->firstOrFail();
        $boards = $user->moodBoards()->latest()->get();
        return view('space.show', compact('user', 'boards'));
    }
}

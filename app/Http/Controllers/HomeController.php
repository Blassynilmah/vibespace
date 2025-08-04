<?php

namespace App\Http\Controllers;

use App\Models\MoodBoard;

class HomeController extends Controller
{
    public function index()
{
    $moodBoards = MoodBoard::with('user')->latest()->take(10)->get();

    return view('home', compact('moodBoards'));
}

}


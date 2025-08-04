<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserSearchController extends Controller
{
public function search(Request $request)
{
    $query = $request->query('q');
    $userId = auth()->id(); // exclude self

    $users = User::where('id', '!=', $userId)
        ->where('username', 'like', '%' . $query . '%')
        ->orderBy('username')
        ->limit(20)
        ->get(['id', 'username', 'profile_picture']);

    return response()->json(['users' => $users]);
}

}


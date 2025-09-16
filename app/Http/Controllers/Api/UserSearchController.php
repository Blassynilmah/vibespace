<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserSearchController extends Controller
{

public function search(Request $request)
{
    $q = $request->query('q', '');
    $q = ltrim($q, '@');
    $userId = $request->user()->id;

    $users = \DB::table('users')
        ->leftJoin('profile_pictures', 'users.id', '=', 'profile_pictures.user_id')
        ->where('users.id', '!=', $userId)
        ->where('users.username', 'ilike', "%{$q}%")
        ->orderBy('users.username')
        ->limit(20)
        ->select('users.id', 'users.username', 'profile_pictures.path as profile_picture')
        ->get();

    return response()->json(['data' => $users]);
}

}


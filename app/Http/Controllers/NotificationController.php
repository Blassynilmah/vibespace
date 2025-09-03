<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        // Fetch notifications for the authenticated user (web view)
    $notifications = \App\Models\Notification::where('user_id', Auth::id())->latest()->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    // API endpoint for Alpine table
    public function api(Request $request)
    {
        $user = Auth::user();
    $perPage = (int) $request->input('per_page', 20);
    $notifications = \App\Models\Notification::where('user_id', $user->id)->latest()->paginate($perPage);
        // Return paginated notifications as JSON (with data as array)
        $notifications->getCollection()->transform(function ($item) {
            $item->data = (array) $item->data;
            return $item;
        });
        return response()->json($notifications);
    }
}

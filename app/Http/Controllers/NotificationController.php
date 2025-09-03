<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{

    public function index()
    {
        // Only fetch unread notifications
        $notifications = \App\Models\Notification::where('user_id', Auth::id())
            ->where('is_read', 0)
            ->latest()
            ->get();

        $grouped = $this->groupNotifications($notifications);
        return view('notifications.index', ['notifications' => $grouped]);
    }

    // API endpoint for Alpine table
    public function api(Request $request)
    {
        $user = Auth::user();
        $notifications = \App\Models\Notification::where('user_id', $user->id)
            ->where('is_read', 0)
            ->latest()
            ->get();

        // Debug: Log notification count to storage/logs/laravel.log
        \Log::debug('[notifications] User ' . $user->id . ' unread notifications count: ' . $notifications->count());

        $grouped = $this->groupNotifications($notifications);
        return response()->json(array_values($grouped));
    }

    /**
     * Group notifications by type, moodboard, and action as described.
     */
    protected function groupNotifications($notifications)
    {
        $grouped = [];
        $now = now();
        // Collect all unique reactor_ids
        $allReactorIds = [];
        foreach ($notifications as $notification) {
            if ($notification->reactor_id) {
                $allReactorIds[] = $notification->reactor_id;
            }
        }
        $allReactorIds = array_unique($allReactorIds);
        $usernames = [];
        if (count($allReactorIds)) {
            $usernames = \App\Models\User::whereIn('id', $allReactorIds)->pluck('name', 'id')->toArray();
        }

        foreach ($notifications as $notification) {
            $type = $notification->type;
            $data = (array) $notification->data;
            $moodBoardId = $data['mood_board_id'] ?? null;
            $reactionType = $data['reaction_type'] ?? ($data['mood'] ?? null);
            $commentId = $data['comment_id'] ?? null;
            $createdAt = $notification->created_at;

            // Group key logic
            if ($type === 'reaction' || $type === 'comment_reaction') {
                $key = $type . '-' . $moodBoardId . '-' . $reactionType;
            } elseif ($type === 'comment') {
                $key = $type . '-' . $moodBoardId;
            } elseif ($type === 'reply') {
                $key = $type . '-' . $moodBoardId . '-' . $commentId;
            } elseif ($type === 'follow' || $type === 'unfollow') {
                // Group follows/unfollows by 24h window
                $window = $createdAt->copy()->startOfDay()->format('Y-m-d');
                $key = $type . '-' . $window;
            } else {
                $key = $type . '-' . ($moodBoardId ?? 'other');
            }

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'type' => $type,
                    'mood_board_id' => $moodBoardId,
                    'reaction_type' => $reactionType,
                    'comment_id' => $commentId,
                    'notifications' => [],
                    'users' => [],
                    'usernames' => [],
                    'count' => 0,
                    'latest_message' => $data['message'] ?? '',
                    'created_at' => $createdAt,
                ];
            }
            $grouped[$key]['notifications'][] = $notification;
            if ($notification->reactor_id) {
                $grouped[$key]['users'][] = $notification->reactor_id;
                $grouped[$key]['usernames'][] = $usernames[$notification->reactor_id] ?? ('User ' . $notification->reactor_id);
            }
            $grouped[$key]['count']++;
            // Always keep the latest message
            if ($createdAt > $grouped[$key]['created_at']) {
                $grouped[$key]['latest_message'] = $data['message'] ?? $grouped[$key]['latest_message'];
                $grouped[$key]['created_at'] = $createdAt;
            }
        }

        // Format users and deduplicate
        foreach ($grouped as &$group) {
            $group['users'] = array_unique(array_filter($group['users']));
            $group['usernames'] = array_unique(array_filter($group['usernames']));
        }
        return $grouped;
    }
}

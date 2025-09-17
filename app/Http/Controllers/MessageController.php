<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;

class MessageController extends Controller
{

public function index(Request $request)
{
    $auth = Auth::user();

    // Step 1: Get all contact IDs from messages
    $recentMessages = Message::where('sender_id', $auth->id)
        ->orWhere('receiver_id', $auth->id)
        ->latest()
        ->get();

    $contactIds = collect();
    foreach ($recentMessages as $msg) {
        $contactIds->push($msg->sender_id === $auth->id ? $msg->receiver_id : $msg->sender_id);
    }
    $contactIds = $contactIds->unique()->take(20);

    // Step 2: Fetch users
    $contacts = User::whereIn('id', $contactIds)->get();

    // Step 2b: Get follow relationships
    $authFollowingIds = $auth->following()->pluck('users.id')->toArray(); // users the auth user follows
    $authFollowerIds = $auth->followers()->pluck('users.id')->toArray(); // users who follow the auth user

    // Step 3: Fetch latest messages in one go (N+1 fix)
    $latestMessages = Message::where(function ($q) use ($auth) {
            $q->whereIn('sender_id', [$auth->id])
              ->orWhereIn('receiver_id', [$auth->id]);
        })
        ->orderByDesc('created_at')
        ->get()
        ->groupBy(function ($msg) use ($auth) {
            return $msg->sender_id === $auth->id ? $msg->receiver_id : $msg->sender_id;
        });

    $contacts->transform(function ($u) use ($latestMessages, $auth, $authFollowingIds, $authFollowerIds) {
        $lastMsg = optional($latestMessages[$u->id])->first();
        $u->unread_count = 0;
        $u->should_bold = false;
        $u->has_attachment = false;

        // Flags for tab logic
        $userFollows = in_array($u->id, $authFollowingIds);
        $followsUser = in_array($u->id, $authFollowerIds);
        $isFriend = $userFollows && $followsUser;
        $hasMessaged = $lastMsg !== null;

        $u->user_follows = $userFollows;
        $u->follows_user = $followsUser;
        $u->is_friend = $isFriend;
        $u->has_messaged = $hasMessaged;

        if ($lastMsg) {
            // Count unread messages where auth is receiver and contact is sender
            if ($lastMsg->sender_id !== $auth->id) {
                $u->unread_count = Message::where('sender_id', $u->id)
                    ->where('receiver_id', $auth->id)
                    ->where('is_read', false)
                    ->count();
                $u->should_bold = $u->unread_count > 0;
            }
            $hasAttachment = method_exists($lastMsg, 'attachments') && $lastMsg->attachments && $lastMsg->attachments->count() > 0;
            $u->has_attachment = $hasAttachment;
            if ((empty($lastMsg->body) || trim($lastMsg->body) === '') && $hasAttachment) {
                $lastMsg->body = 'Attachment';
            }
        }
        $u->last_message = $lastMsg;
        return $u;
    });

    // Selected thread logic unchanged...
    $receiver = null;
    $messages = [];

    if ($request->filled('receiver_id')) {
        $receiverId = $request->receiver_id;
        if (! $contactIds->contains($receiverId)) {
            $allowed = User::find($receiverId);
            if (! $allowed) {
                abort(403, 'Unauthorized chat access');
            }
        }
        $receiver = User::findOrFail($receiverId);

        $messages = Message::where(function ($q) use ($auth, $receiver) {
            $q->where('sender_id', $auth->id)
              ->where('receiver_id', $receiver->id);
        })->orWhere(function ($q) use ($auth, $receiver) {
            $q->where('sender_id', $receiver->id)
              ->where('receiver_id', $auth->id);
        })->latest()
          ->take(10)
          ->get()
          ->reverse()
          ->values()
          ->toArray();

        Message::where('sender_id', $receiver->id)
            ->where('receiver_id', $auth->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    return view('messages.index', compact('contacts', 'receiver', 'messages'));
}

    // API: Get count of unique users with unread messages for the logged-in user
    public function unreadConversationsCount()
    {
        $user = Auth::user();
        // Get all unread messages where the logged-in user is the receiver
        $unread = Message::where('receiver_id', $user->id)
            ->where('is_read', false)
            ->pluck('sender_id')
            ->unique();
        return response()->json(['count' => $unread->count()]);
    }

public function store(Request $request)
{
    $validated = $request->validate([
        'receiver_id' => 'required|exists:users,id',
        'body' => 'nullable|string|max:1000',
        'file_ids' => 'nullable|array',
        'file_ids.*' => 'exists:user_files,id,user_id,' . auth()->id(),
    ]);

    $user = auth()->user();
    $fileIds = $validated['file_ids'] ?? [];
    $receiverId = $validated['receiver_id'];

    try {
        \DB::beginTransaction();

        $message = Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $receiverId,
            'body' => $validated['body'] ?? '',
            'is_read' => false,
        ]);

        $attachments = [];

        foreach ($fileIds as $id) {
            $file = \App\Models\UserFile::find($id);
            if (!$file) {
                \DB::rollBack();
                return response()->json(['error' => "Attachment file not found (ID: $id)"], 500);
            }

            $attachment = $message->attachments()->create([
                'file_name' => $file->name ?? null,
                'file_path' => $file->path,
                'mime_type' => $file->mime,
                'size' => $file->size,
                'sender_id' => $user->id,
                'receiver_id' => $receiverId,
                'message_id' => $message->id,
            ]);

            if (!$attachment) {
                \DB::rollBack();
                return response()->json(['error' => "Failed to store attachment (ID: $id)"], 500);
            }

            $attachments[] = [
                'name' => $attachment->file_name,
                'url' => \Storage::url($attachment->file_path),
                'mime' => $attachment->mime_type,
                'size' => $attachment->size,
            ];
        }

        \DB::commit();

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'body' => $message->body,
                'sender_id' => $message->sender_id,
                'receiver_id' => $message->receiver_id,
                'created_at' => $message->created_at,
                'attachments' => $attachments,
            ],
        ]);
    } catch (\Exception $e) {
        \DB::rollBack();
        return response()->json(['error' => 'Failed to send message or attachments'], 500);
    }
}

    // 🔁 Load more messages
    public function loadMore(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'offset' => 'nullable|integer',
        ]);

        $user = Auth::user();
        $receiver = User::findOrFail($request->receiver_id);
        $offset = intval($request->offset ?? 0);

        $messages = Message::where(function ($q) use ($user, $receiver) {
            $q->where('sender_id', $user->id)->where('receiver_id', $receiver->id);
        })->orWhere(function ($q) use ($user, $receiver) {
            $q->where('sender_id', $receiver->id)->where('receiver_id', $user->id);
        })->latest()
          ->skip($offset)
          ->take(10)
          ->get()
          ->reverse()
          ->values();

        return response()->json(['messages' => $messages]);
    }

    // 📥 Mark messages as read (API version)
    public function markAsRead(Request $request)
        {
            $request->validate([
                'receiver_id' => 'required|exists:users,id',
            ]);

            $user = Auth::user();

            Message::where('sender_id', $request->receiver_id)
                ->where('receiver_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return response()->json(['success' => true]);
        }

public function recentChats()
{
    $user = Auth::user();
    $recentMessages = Message::with('attachments')
        ->where('sender_id', $user->id)
        ->orWhere('receiver_id', $user->id)
        ->latest()
        ->get();

    $contactIds = $recentMessages->map(function ($msg) use ($user) {
        return $msg->sender_id === $user->id ? $msg->receiver_id : $msg->sender_id;
    })->unique()->take(20);

    $contacts = User::whereIn('id', $contactIds)->get();

    // Get follow relationships
    $authFollowingIds = $user->following()->pluck('users.id')->toArray();
    $authFollowerIds = $user->followers()->pluck('users.id')->toArray();

    // Group messages by contact
    $groupedMessages = $recentMessages->groupBy(function ($msg) use ($user) {
        return $msg->sender_id === $user->id ? $msg->receiver_id : $msg->sender_id;
    });

    $contacts->transform(function ($u) use ($groupedMessages, $user, $authFollowingIds, $authFollowerIds) {
        $msg = optional($groupedMessages[$u->id])->first();
        $u->unread_count = 0;
        $u->should_bold = false;
        $u->has_attachment = false;

        // Flags for tab logic
        $userFollows = in_array($u->id, $authFollowingIds);
        $followsUser = in_array($u->id, $authFollowerIds);
        $isFriend = $userFollows && $followsUser;
        $hasMessaged = $msg !== null;

        $u->user_follows = $userFollows;
        $u->follows_user = $followsUser;
        $u->is_friend = $isFriend;
        $u->has_messaged = $hasMessaged;

        if ($msg) {
            if ($msg->sender_id !== $user->id) {
                $u->unread_count = Message::where('sender_id', $u->id)
                    ->where('receiver_id', $user->id)
                    ->where('is_read', false)
                    ->count();
                $u->should_bold = $u->unread_count > 0;
            }
            $hasAttachment = method_exists($msg, 'attachments') && $msg->attachments && $msg->attachments->count() > 0;
            $u->has_attachment = $hasAttachment;
            if ((empty($msg->body) || trim($msg->body) === '') && $hasAttachment) {
                $msg->body = 'Attachment';
            }
        }
        $u->last_message = $msg;
        return $u;
    });

    return response()->json($contacts);
}

public function thread($receiverId)
{
    \Log::info('[MessageThread] Incoming request', [
        'receiver_id' => $receiverId,
        'user_id' => auth()->id(),
        'limit' => request('limit'),
        'offset' => request('offset'),
    ]);

    try {
        $userId = auth()->id();

        $limit = intval(request('limit', 20));
        $offset = intval(request('offset', 0));


        // Mark all unread messages from the other user as read
        Message::where('sender_id', $receiverId)
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = Message::with('attachments')
            ->where(function ($query) use ($userId, $receiverId) {
                $query->where('sender_id', $userId)
                    ->where('receiver_id', $receiverId);
            })
            ->orWhere(function ($query) use ($userId, $receiverId) {
                $query->where('sender_id', $receiverId)
                    ->where('receiver_id', $userId);
            })
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get()
            ->reverse()
            ->values();

        $mappedMessages = $messages->map(function ($msg) {
            $attachments = collect($msg->attachments)->map(function ($file) {
                $extension = pathinfo($file->file_path ?? '', PATHINFO_EXTENSION);
                $filename = $file->file_name ?? basename($file->file_path);
                return [
                    'name' => $filename,
                    'url' => Storage::url($file->file_path),
                    'size' => $file->size,
                    'extension' => strtolower($extension),
                    'mime_type' => $file->mime_type,
                    'meta' => $file->meta,
                ];
            })->values()->all();

            return [
                'id' => $msg->id,
                'body' => $msg->body,
                'sender_id' => $msg->sender_id,
                'receiver_id' => $msg->receiver_id,
                'created_at' => $msg->created_at,
                'read_at' => $msg->read_at,
                'attachments' => $attachments,
            ];
        });

        $receiver = User::find($receiverId);
        if (!$receiver) {
            throw new \Exception("Receiver not found (ID: $receiverId)");
        }

        if (request()->expectsJson()) {
            return response()->json([
                'messages' => $mappedMessages,
                'receiver' => $receiver,
            ]);
        }

        $contacts = $this->getRecentContacts($userId);
        return view('messages.index', [
            'contacts' => $contacts,
            'receiver' => $receiver,
            'messages' => $mappedMessages,
        ]);

    } catch (\Exception $e) {
        \Log::error('[MessageThread] Failed to fetch thread', [
            'receiver_id' => $receiverId,
            'user_id' => auth()->id(),
            'limit' => request('limit'),
            'offset' => request('offset'),
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return request()->expectsJson()
            ? response()->json(['error' => 'Failed to fetch message thread'], 500)
            : abort(500, 'Something went wrong loading this thread');
    }
}


    protected function getRecentContacts($authId)
    {
        $recentMessages = Message::where('sender_id', $authId)
            ->orWhere('receiver_id', $authId)
            ->latest()
            ->get();

        $contactIds = collect();
        foreach ($recentMessages as $msg) {
            $contactIds->push($msg->sender_id === $authId ? $msg->receiver_id : $msg->sender_id);
        }
        $contactIds = $contactIds->unique()->take(20);

        return User::whereIn('id', $contactIds)->get();
    }


    public function sendWithFiles(Request $request)
    {
        \Log::info('[SendWithFiles] Incoming request', $request->all());

        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'body' => 'nullable|string|max:1000',
            'files' => 'required|array|max:20',
            'files.*' => 'exists:user_files,id,user_id,' . auth()->id(),
        ]);

        $user = auth()->user();
        \Log::info('[SendWithFiles] Authenticated user', ['id' => $user->id]);

        $fileIds = $validated['files'];
        $receiverId = $validated['receiver_id'];

        // 📨 Create the message
        try {
            $message = Message::create([
                'sender_id' => $user->id,
                'receiver_id' => $receiverId,
                'body' => $validated['body'] ?? '',
                'is_read' => false,
            ]);
            \Log::info('[SendWithFiles] Message created', ['id' => $message->id]);
        } catch (\Exception $e) {
            \Log::error('[SendWithFiles] Failed to create message', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create message'], 500);
        }

        $attachments = [];

        foreach ($fileIds as $id) {
            try {
                $file = \App\Models\UserFile::findOrFail($id);
                \Log::info('[SendWithFiles] Attaching file', ['file_id' => $id]);

                $attachment = $message->attachments()->create([
                    'file_name' => $file->name ?? null,
                    'file_path' => $file->path,
                    'mime_type' => $file->mime,
                    'size' => $file->size,
                    'sender_id' => $user->id,
                    'receiver_id' => $receiverId,
                    'message_id' => $message->id,
                ]);

                \Log::info('[SendWithFiles] Attachment created', ['attachment_id' => $attachment->id]);
                $attachments[] = [
                    'name' => $attachment->file_name,
                    'url' => Storage::url($attachment->file_path),
                    'mime' => $attachment->mime_type,
                    'size' => $attachment->size,
                ];
            } catch (\Exception $e) {
                \Log::error('[SendWithFiles] Failed to attach file', [
                    'file_id' => $id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'body' => $message->body,
                'sender_id' => $message->sender_id,
                'receiver_id' => $message->receiver_id,
                'created_at' => $message->created_at,
                'attachments' => $attachments,
            ],
        ]);
    }
}

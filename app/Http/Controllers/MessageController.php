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
    // 📨 Show inbox + selected thread
    public function index(Request $request)
    {
        $auth = Auth::user();

        // 🧠 Step 1: Get all contact IDs from messages
        $recentMessages = Message::where('sender_id', $auth->id)
            ->orWhere('receiver_id', $auth->id)
            ->latest()
            ->get();

        $contactIds = collect();
        foreach ($recentMessages as $msg) {
            $contactIds->push($msg->sender_id === $auth->id ? $msg->receiver_id : $msg->sender_id);
        }
        $contactIds = $contactIds->unique()->take(20);

        // 🧠 Step 2: Fetch users
        $contacts = User::whereIn('id', $contactIds)->get();

        // 🧠 Step 3: Fetch latest messages in one go (N+1 fix)
        $latestMessages = Message::where(function ($q) use ($auth) {
                $q->whereIn('sender_id', [$auth->id])
                  ->orWhereIn('receiver_id', [$auth->id]);
            })
            ->orderByDesc('created_at')
            ->get()
            ->groupBy(function ($msg) use ($auth) {
                return $msg->sender_id === $auth->id ? $msg->receiver_id : $msg->sender_id;
            });

        $contacts->transform(function ($u) use ($latestMessages) {
            $u->last_message = optional($latestMessages[$u->id])->first();
            return $u;
        });

        // 💬 Step 4: Selected thread
        $receiver = null;
        $messages = [];

        if ($request->filled('receiver_id')) {
            $receiverId = $request->receiver_id;

            // 🚨 Check if user is allowed to DM this person
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

            // ✅ Mark all unread as read
            Message::where('sender_id', $receiver->id)
                ->where('receiver_id', $auth->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        return view('messages.index', compact('contacts', 'receiver', 'messages'));
    }

public function store(Request $request)
{
    \Log::info('[MessageStore] Incoming request', $request->all());

    try {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'body' => 'nullable|string|max:1000',
            'file_ids' => 'nullable|array',
            'file_ids.*' => 'exists:user_files,id,user_id,' . auth()->id(),
        ]);
        \Log::info('[MessageStore] Validation passed', $validated);
    } catch (\Illuminate\Validation\ValidationException $e) {
        \Log::error('[MessageStore] Validation failed', ['errors' => $e->errors()]);
        return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
    }

    $user = auth()->user();
    \Log::info('[MessageStore] Authenticated user', ['id' => $user->id]);

    $fileIds = $validated['file_ids'] ?? [];
    $receiverId = $validated['receiver_id'];

    // 📨 Create the message
    try {
        $message = Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $receiverId,
            'body' => $validated['body'] ?? '',
            'is_read' => false,
        ]);
        \Log::info('[MessageStore] Message created', ['id' => $message->id]);
    } catch (\Exception $e) {
        \Log::error('[MessageStore] Failed to create message', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to create message'], 500);
    }

    $attachments = [];

    foreach ($fileIds as $id) {
        try {
            $file = \App\Models\UserFile::findOrFail($id);
            \Log::info('[MessageStore] Found file', ['file_id' => $id]);

            $attachmentData = [
                'file_path' => $file->path,
                'mime_type' => $file->mime,
                'size' => $file->size,
                'sender_id' => $user->id,
                'receiver_id' => $receiverId,
            ];

            if (!empty($file->name)) {
                $attachmentData['file_name'] = $file->name;
            }

            $attachment = $message->attachments()->create($attachmentData);
            \Log::info('[MessageStore] Attachment created', ['attachment_id' => $attachment->id]);

            $attachments[] = [
                'name' => $attachment->file_name,
                'url' => Storage::url($attachment->file_path),
                'mime' => $attachment->mime_type,
                'size' => $attachment->size,
            ];
        } catch (\Exception $e) {
            \Log::error('[MessageStore] Failed to attach file', [
                'file_id' => $id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // 🧾 Return enriched response
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
            \Log::info('[RecentChats] Auth user:', ['id' => $user->id]);

            // 📨 Step 1: Fetch recent messages involving the user
            $recentMessages = Message::with('attachments')
                ->where('sender_id', $user->id)
                ->orWhere('receiver_id', $user->id)
                ->latest()
                ->get();

            \Log::info('[RecentChats] Recent messages count:', ['count' => $recentMessages->count()]);

            // 🧠 Step 2: Extract contact IDs
            $contactIds = $recentMessages->map(function ($msg) use ($user) {
                return $msg->sender_id === $user->id ? $msg->receiver_id : $msg->sender_id;
            })->unique()->take(20);

            \Log::info('[RecentChats] Contact IDs:', ['ids' => $contactIds->values()]);

            // 👥 Step 3: Fetch contact users
            $contacts = User::whereIn('id', $contactIds)->get();
            \Log::info('[RecentChats] Found users:', ['count' => $contacts->count()]);

            // 💬 Step 4: Group messages by contact
            $groupedMessages = $recentMessages->groupBy(function ($msg) use ($user) {
                return $msg->sender_id === $user->id ? $msg->receiver_id : $msg->sender_id;
            });

            \Log::info('[RecentChats] Grouped latest messages:', ['keys' => $groupedMessages->keys()]);

            // ✨ Step 5: Attach enriched last_message to each contact
            $contacts->transform(function ($contact) use ($groupedMessages) {
                $msg = optional($groupedMessages[$contact->id])->first();

                $hasAttachment = $msg && $msg->attachments && $msg->attachments->count() > 0;

                $contact->last_message = $msg ? [
                    'id' => $msg->id,
                    'body' => $msg->body ?: ($hasAttachment ? 'Attachment' : ''),
                    'created_at' => $msg->created_at,
                    'has_attachment' => $hasAttachment,
                ] : null;

                return $contact;
            });

            \Log::info('[RecentChats] Final contacts list:', ['user_ids' => $contacts->pluck('id')]);

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

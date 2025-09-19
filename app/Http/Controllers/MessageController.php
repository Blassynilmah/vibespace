<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use App\Models\Block;

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

                $u->is_blocked = Block::where('blocker_id', $auth->id)
                    ->where('blocked_id', $u->id)
                    ->where('block_type', 'message')
                    ->exists();

                $u->blocked_by = Block::where('blocker_id', $u->id)
                    ->where('blocked_id', $auth->id)
                    ->where('block_type', 'message')
                    ->exists();

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
            \Log::info('[SEND] Incoming request', $request->all());

            try {
                $validated = $request->validate([
                    'receiver_id' => 'required|exists:users,id',
                    'body' => 'nullable|string|max:1000',
                    'file_ids' => 'nullable|array',
                    'file_ids.*' => 'exists:user_files,id,user_id,' . auth()->id(),
                ]);
                \Log::info('[SEND] Validation passed', $validated);
            } catch (\Illuminate\Validation\ValidationException $e) {
                \Log::error('[SEND] Validation failed', ['errors' => $e->errors()]);
                return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
            }

            $user = auth()->user();
            $fileIds = $validated['file_ids'] ?? [];
            $receiverId = $validated['receiver_id'];

            try {
                \DB::beginTransaction();

                // Create the message
                $message = Message::create([
                    'sender_id' => $user->id,
                    'receiver_id' => $receiverId,
                    'body' => $validated['body'] ?? '',
                    'is_read' => false,
                ]);
                \Log::info('[SEND] Message created', ['id' => $message->id]);

                // Attach files (copy to attachments folder and create attachment records)
                foreach ($fileIds as $id) {
                    $file = \App\Models\UserFile::find($id);
                    if (!$file) {
                        \Log::error('[SEND] File not found', ['file_id' => $id]);
                        \DB::rollBack();
                        return response()->json(['error' => "Attachment file not found (ID: $id)"], 500);
                    }

                    // Copy file to attachments folder if needed
                    $newPath = 'attachments/' . basename($file->path);
                    if (!\Storage::disk('public')->exists($newPath)) {
                        \Storage::disk('public')->copy($file->path, $newPath);
                        \Log::info('[SEND] File copied to attachments folder', ['from' => $file->path, 'to' => $newPath]);
                    }

                    // Create attachment record
                    $attachment = $message->attachments()->create([
                        'file_name' => $file->name ?? null,
                        'file_path' => $newPath,
                        'mime_type' => $file->mime,
                        'size' => $file->size,
                        'sender_id' => $user->id,
                        'receiver_id' => $receiverId,
                        'message_id' => $message->id,
                    ]);

                    if (!$attachment) {
                        \Log::error('[SEND] Failed to create attachment', ['file_id' => $id]);
                        \DB::rollBack();
                        return response()->json(['error' => "Failed to store attachment (ID: $id)"], 500);
                    }

                    \Log::info('[SEND] Attachment created', ['attachment_id' => $attachment->id]);
                }

                \DB::commit();

                // Fetch the message with attachments using the same mapping as thread
                $msg = Message::with('attachments')->find($message->id);

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

                $mappedMessage = [
                    'id' => $msg->id,
                    'body' => $msg->body,
                    'sender_id' => $msg->sender_id,
                    'receiver_id' => $msg->receiver_id,
                    'created_at' => $msg->created_at,
                    'read_at' => $msg->read_at,
                    'attachments' => $attachments,
                ];

                \Log::info('[SEND] Returning response', [
                    'message_id' => $msg->id,
                    'attachments_count' => count($attachments)
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $mappedMessage,
                ]);
            } catch (\Exception $e) {
                \DB::rollBack();
                \Log::error('[SEND] Transaction failed', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Failed to send message or attachments', 'details' => $e->getMessage()], 500);
            }
        }

    // Load more messages with blocking logic
    public function loadMore(Request $request)
        {
            $request->validate([
                'receiver_id' => 'required|exists:users,id',
                'offset' => 'nullable|integer',
            ]);

            $user = Auth::user();
            $receiver = User::findOrFail($request->receiver_id);
            $offset = intval($request->offset ?? 0);

            $block = Block::where('blocker_id', $user->id)
                ->where('blocked_id', $receiver->id)
                ->where('block_type', 'message')
                ->first();

            $blockedBy = Block::where('blocker_id', $receiver->id)
                ->where('blocked_id', $user->id)
                ->where('block_type', 'message')
                ->first();

            $messagesQuery = Message::with('attachments')
                ->where(function ($q) use ($user, $receiver) {
                    $q->where('sender_id', $user->id)->where('receiver_id', $receiver->id);
                })
                ->orWhere(function ($q) use ($user, $receiver) {
                    $q->where('sender_id', $receiver->id)->where('receiver_id', $user->id);
                });

            if ($block) {
                $messagesQuery->where(function ($q) use ($user, $receiver, $block) {
                    $q->where('sender_id', $user->id)->where('receiver_id', $receiver->id);
                })
                ->orWhere(function ($q) use ($user, $receiver, $block) {
                    $q->where('sender_id', $receiver->id)->where('receiver_id', $user->id)->where('created_at', '<', $block->blocked_at);
                });
            } elseif ($blockedBy) {
                $messagesQuery->where(function ($q) use ($user, $receiver, $blockedBy) {
                    $q->where('sender_id', $user->id)->where('receiver_id', $receiver->id);
                })
                ->orWhere(function ($q) use ($user, $receiver, $blockedBy) {
                    $q->where('sender_id', $receiver->id)->where('receiver_id', $user->id)->where('created_at', '<', $blockedBy->blocked_at);
                });
            }

            $messages = $messagesQuery->latest()->skip($offset)->take(10)->get()->reverse()->values();

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

            return response()->json(['messages' => $mappedMessages]);
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
                $messages = $groupedMessages[$u->id] ?? collect();

                // Block state
                $block = Block::where('blocker_id', $user->id)
                    ->where('blocked_id', $u->id)
                    ->where('block_type', 'message')
                    ->first();

                $blockedBy = Block::where('blocker_id', $u->id)
                    ->where('blocked_id', $user->id)
                    ->where('block_type', 'message')
                    ->first();

                // Select last_message according to block logic
                if ($block) {
                    // If viewer is the blocker, only show messages sent before block date (from blocked user)
                    $filtered = $messages->filter(function ($msg) use ($user, $u, $block) {
                        // Show all messages sent by viewer
                        if ($msg->sender_id == $user->id) return true;
                        // Show messages sent by contact before block date
                        if ($msg->sender_id == $u->id && $msg->created_at < $block->blocked_at) return true;
                        return false;
                    });
                    $lastMsg = $filtered->first();
                } elseif ($blockedBy) {
                    // If viewer is the blocked, show all messages
                    $lastMsg = $messages->first();
                } else {
                    // No block, show all messages
                    $lastMsg = $messages->first();
                }

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

                $u->is_blocked = (bool) $block;
                $u->blocked_by = (bool) $blockedBy;

                if ($lastMsg) {
                    if ($lastMsg->sender_id !== $user->id) {
                        $u->unread_count = Message::where('sender_id', $u->id)
                            ->where('receiver_id', $user->id)
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

            return response()->json($contacts);
        }

    public function thread($receiverId)
        {
            $userId = auth()->id();
            $limit = intval(request('limit', 5));
            $offset = intval(request('offset', 0));

            $block = Block::where('blocker_id', $userId)
                ->where('blocked_id', $receiverId)
                ->where('block_type', 'message')
                ->first();

            $blockedBy = Block::where('blocker_id', $receiverId)
                ->where('blocked_id', $userId)
                ->where('block_type', 'message')
                ->first();

            Message::where('sender_id', $receiverId)
                ->where('receiver_id', $userId)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            $messagesQuery = Message::with('attachments')
                ->where(function ($q) use ($userId, $receiverId) {
                    $q->where('sender_id', $userId)->where('receiver_id', $receiverId);
                })
                ->orWhere(function ($q) use ($userId, $receiverId) {
                    $q->where('sender_id', $receiverId)->where('receiver_id', $userId);
                });

            if ($block) {
                $messagesQuery->where(function ($q) use ($userId, $receiverId, $block) {
                    $q->where('sender_id', $userId)->where('receiver_id', $receiverId);
                })
                ->orWhere(function ($q) use ($userId, $receiverId, $block) {
                    $q->where('sender_id', $receiverId)->where('receiver_id', $userId)->where('created_at', '<', $block->blocked_at);
                });
            } elseif ($blockedBy) {
                $messagesQuery->where(function ($q) use ($userId, $receiverId, $blockedBy) {
                    $q->where('sender_id', $userId)->where('receiver_id', $receiverId);
                })
                ->orWhere(function ($q) use ($userId, $receiverId, $blockedBy) {
                    $q->where('sender_id', $receiverId)->where('receiver_id', $userId)->where('created_at', '<', $blockedBy->blocked_at);
                });
            }

            $messages = $messagesQuery->orderBy('created_at', 'desc')->skip($offset)->take($limit)->get()->reverse()->values();

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

            // Add block state to receiver object
            $receiver->is_blocked = Block::where('blocker_id', $userId)
                ->where('blocked_id', $receiverId)
                ->where('block_type', 'message')
                ->exists();

            $receiver->blocked_by = Block::where('blocker_id', $receiverId)
                ->where('blocked_id', $userId)
                ->where('block_type', 'message')
                ->exists();

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
        }

    protected function getRecentContacts($authId)
        {
            // Get all messages involving the user
            $recentMessages = Message::where('sender_id', $authId)
                ->orWhere('receiver_id', $authId)
                ->latest()
                ->get();

            // Get unique contact IDs
            $contactIds = collect();
            foreach ($recentMessages as $msg) {
                $contactIds->push($msg->sender_id === $authId ? $msg->receiver_id : $msg->sender_id);
            }
            $contactIds = $contactIds->unique()->take(20);

            // Fetch contacts
            $contacts = User::whereIn('id', $contactIds)->get();

            // Group messages by contact
            $groupedMessages = $recentMessages->groupBy(function ($msg) use ($authId) {
                return $msg->sender_id === $authId ? $msg->receiver_id : $msg->sender_id;
            });

            // Attach block state and correct last_message for each contact
            $contacts->transform(function ($u) use ($groupedMessages, $authId) {
                $messages = $groupedMessages[$u->id] ?? collect();

                // Check block state
                $block = Block::where('blocker_id', $authId)
                    ->where('blocked_id', $u->id)
                    ->where('block_type', 'message')
                    ->first();

                $blockedBy = Block::where('blocker_id', $u->id)
                    ->where('blocked_id', $authId)
                    ->where('block_type', 'message')
                    ->first();

                // Filter messages for last_message according to block logic
                if ($block) {
                    // If viewer is the blocker, only show messages sent before block date (from blocked user)
                    $filtered = $messages->filter(function ($msg) use ($authId, $u, $block) {
                        // Show all messages sent by viewer
                        if ($msg->sender_id == $authId) return true;
                        // Show messages sent by contact before block date
                        if ($msg->sender_id == $u->id && $msg->created_at < $block->blocked_at) return true;
                        return false;
                    });
                    $lastMsg = $filtered->first();
                } elseif ($blockedBy) {
                    // If viewer is the blocked, show all messages
                    $lastMsg = $messages->first();
                } else {
                    // No block, show all messages
                    $lastMsg = $messages->first();
                }

                $u->is_blocked = (bool) $block;
                $u->blocked_by = (bool) $blockedBy;
                $u->last_message = $lastMsg;

                return $u;
            });

            return $contacts;
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

    public function blockUser(Request $request)
        {
            $user = auth()->user();
            $blockedId = $request->input('blocked_id');
            $blockType = $request->input('block_type', 'message');

            if (!$blockedId || !in_array($blockType, ['message', 'profile'])) {
                return response()->json(['success' => false, 'error' => 'Invalid request'], 400);
            }

            if ($blockedId == $user->id) {
                return response()->json(['success' => false, 'error' => 'You cannot block yourself'], 400);
            }

            $block = \App\Models\Block::updateOrCreate(
                [
                    'blocker_id' => $user->id,
                    'blocked_id' => $blockedId,
                ],
                [
                    'block_type' => $blockType,
                    'blocked_at' => now(),
                ]
            );

            return response()->json(['success' => true]);
        }

    public function unblockUser(Request $request)
        {
            $user = auth()->user();
            $blockedId = $request->input('blocked_id');
            $blockType = $request->input('block_type', 'message');

            if (!$blockedId || !in_array($blockType, ['message', 'profile'])) {
                return response()->json(['success' => false, 'error' => 'Invalid request'], 400);
            }

            if ($blockedId == $user->id) {
                return response()->json(['success' => false, 'error' => 'You cannot unblock yourself'], 400);
            }

            $deleted = \App\Models\Block::where('blocker_id', $user->id)
                ->where('blocked_id', $blockedId)
                ->where('block_type', $blockType)
                ->delete();

            return response()->json(['success' => $deleted > 0]);
        }


    public function muteUser(Request $request)
        {
            $user = auth()->user();
            $mutedId = $request->input('muted_id');
            $muteUntil = $request->input('mute_until'); // ISO string or null

            if (!$mutedId || $mutedId == $user->id) {
                return response()->json(['success' => false, 'error' => 'Invalid request'], 400);
            }

            $mute = \App\Models\Mute::updateOrCreate(
                [
                    'muter_id' => $user->id,
                    'muted_id' => $mutedId,
                ],
                [
                    'muted_at' => now(),
                    'mute_until' => $muteUntil ? Carbon::parse($muteUntil) : null,
                ]
            );

            return response()->json(['success' => true]);
        }
}

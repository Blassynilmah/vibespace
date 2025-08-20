@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-10 px-4" x-data="repliesPage({{ json_encode($comment) }})" x-init="init()">

    {{-- ⬅️ Back to Board --}}
    <div class="mb-4">
        <a href="{{ route('boards.show', $comment['mood_board_id']) }}#comment-{{ $comment['id'] }}"
           class="text-sm text-blue-500 hover:underline">← Back to Board</a>
    </div>

    {{-- 🗨️ Main Comment --}}
    <<div class="bg-pink-50 border border-pink-200 p-5 rounded-2xl shadow-lg mb-6 relative">
        <div class="mb-1 text-xs text-pink-700 font-semibold uppercase tracking-wide">Main Comment</div>
        
        <h2 class="text-sm text-pink-600 font-medium mb-1">
            <a :href="'/space/' + main.user.username" class="text-blue-600 hover:underline" x-text="'@' + main.user.username"></a>'s comment to 
            <a :href="'/space/' + main.board_user.username" class="text-blue-600 hover:underline" x-text="'@' + main.board_user.username"></a>'s post
        </h2>

        <p class="text-gray-700 text-sm mb-2" x-text="main.body"></p>

        <div class="text-xs text-gray-400" x-text="timeSince(main.created_at)"></div>
    </div>

    {{-- 💬 Replies List --}}
    <div class="space-y-4 mb-6">
        <template x-for="reply in main.replies" :key="reply.id">
            <div class="bg-gray-50 p-3 rounded-xl shadow">
                <div class="flex justify-between items-center">
                    <a
                        :href="'/space/' + reply.user.username"
                        class="text-sm font-semibold text-indigo-600 hover:underline"
                        x-text="'@' + reply.user.username"
                    ></a>
                    <span class="text-xs text-gray-400" x-text="timeSince(reply.created_at)"></span>
                </div>
                <p class="text-sm text-gray-700 mt-1">
                    <template x-if="/^@\w+/.test(reply.body)">
                        <a
                            :href="'/space/' + reply.body.match(/^@(\w+)/)[1]"
                            class="text-blue-600 hover:underline"
                            x-text="reply.body.match(/^@(\w+)/)[0]"
                        ></a>
                    </template>
                    <span x-text="reply.body.replace(/^@\w+\s*/, '')"></span>
                </p>

                <button class="text-xs text-purple-600 mt-2 hover:underline"
                        @click="setReplyTag(reply)">Reply</button>
            </div>
        </template>

        <template x-if="main.replies.length === 0">
            <p class="text-gray-500 italic text-sm">No replies yet. Be the first to respond ✨</p>
        </template>
    </div>

    {{-- ✍️ Reply Input --}}
    <div class="bg-white p-4 rounded-xl shadow sticky bottom-4">
        <div class="flex items-center gap-2">
            <input type="text" x-model="replyText"
                :placeholder="replyingTo ? 'Replying to @' + replyingTo : 'Write a reply...'"
                class="flex-1 px-4 py-2 text-sm border border-gray-300 rounded-full">
            <button @click="submitReply"
                    class="bg-blue-500 text-white px-4 py-2 rounded-full text-sm">Send</button>
        </div>
    </div>
</div>

<div id="toast" style="
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    background-color: #333;
    color: #fff;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.25);
    font-size: 0.875rem;
    z-index: 9999;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease-in-out;
"></div>

@endsection

@push('scripts')
<script>
    function showToast(message, isError = false) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.style.backgroundColor = isError ? '#e53935' : '#38a169'; // red/green
    toast.style.opacity = '1';

    setTimeout(() => {
        toast.style.opacity = '0';
    }, 3000);
}

    document.addEventListener('alpine:init', () => {
        Alpine.data('repliesPage', (raw) => ({
            main: {
                ...raw,
                user: raw.user || {}, // <- make sure this is set
                replies: (raw.replies || []).map(reply => ({
                    ...reply,
                    user: reply.user || {}, // <- make sure reply user is set too
                }))
            },

            replyText: '',
            replyingTo: null,

            init() {
                // Set default to main comment's username
                this.replyingTo = this.main.user.username;
                this.replyText = `@${this.main.user.username} `;
                console.log("💬 Replies page initialized");
            },

            setReplyTag(reply) {
                this.replyingTo = reply.user.username;
                this.replyText = `@${reply.user.username} `;
            },

            submitReply() {
                if (!this.replyText.trim()) return;

                // Ensure @username is always at the start
                if (this.replyingTo && !this.replyText.startsWith(`@${this.replyingTo}`)) {
                    this.replyText = `@${this.replyingTo} ` + this.replyText.replace(/^@\w+\s*/, '');
                }

                showToast("Replying...");

                setTimeout(() => {
                    fetch(`/comments/${this.main.id}/replies`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                        },
                        body: JSON.stringify({ body: this.replyText.trim() })
                    })
                    .then(res => res.ok ? res.json() : Promise.reject())
                    .then(data => {
                        this.main.replies.push(data.reply);
                        this.replyText = '';
                        this.replyingTo = null;
                        showToast("Reply added 💬");
                    })
                    .catch(() => {
                        showToast("Reply failed 😢", true);
                    });
                }, 1000);
            },

            setReplyTag(reply) {
                this.replyingTo = reply.user.username;
                this.replyText = `@${reply.user.username} `;
            },

            timeSince(date) {
                const seconds = Math.floor((new Date() - new Date(date)) / 1000);
                const interval = seconds / 3600;
                if (interval > 24) return `${Math.floor(interval / 24)} days ago`;
                if (interval >= 1) return `${Math.floor(interval)} hrs ago`;
                if (seconds > 60) return `${Math.floor(seconds / 60)} mins ago`;
                return "just now";
            }
        }));
    });
</script>
@endpush

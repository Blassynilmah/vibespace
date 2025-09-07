@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto py-10 px-4"
     x-data="singleBoard({{ json_encode($board) }}, {{ json_encode($images) }}, {{ json_encode($video) }})">    {{-- ⬅️ Back to Home --}}
    <div class="mb-6">
        <a href="{{ route('home') }}#board-{{ $board['id'] }}"
           class="text-sm text-blue-500 hover:underline">← Back to Feed</a>
    </div>

    {{-- 🔹 Board Header --}}
<div class="mb-6 flex flex-col gap-2">
    <!-- Mood badge -->
    <template x-if="board.mood || board.latest_mood">
        <span
            class="text-xs font-medium px-2 py-0.5 rounded-full self-start flex items-center gap-1 capitalize mb-1"
            :class="{
                'bg-blue-100 text-blue-700': (board.latest_mood ?? board.mood) === 'excited',
                'bg-orange-100 text-orange-700': (board.latest_mood ?? board.mood) === 'happy',
                'bg-pink-100 text-pink-700': (board.latest_mood ?? board.mood) === 'chill',
                'bg-purple-100 text-purple-700': (board.latest_mood ?? board.mood) === 'thoughtful',
                'bg-teal-100 text-teal-700': (board.latest_mood ?? board.mood) === 'sad',
                'bg-amber-100 text-amber-700': (board.latest_mood ?? board.mood) === 'flirty',
                'bg-indigo-100 text-indigo-700': (board.latest_mood ?? board.mood) === 'mindblown',
                'bg-yellow-100 text-yellow-700': (board.latest_mood ?? board.mood) === 'love',
            }"
            style="backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); box-shadow: 0 1px 0 rgba(0,0,0,0.05);"
        >
            <span x-text="moods[board.latest_mood ?? board.mood]"></span>
            <span x-text="(board.latest_mood ?? board.mood).charAt(0).toUpperCase() + (board.latest_mood ?? board.mood).slice(1)"></span>
            <span>Vibes</span>
        </span>
    </template>
    <!-- Username and time -->
    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
        <a :href="'/space/' + board.user.username" class="hover:underline font-medium text-blue-600" x-text="'@' + board.user.username"></a>
        <span class="mx-1">•</span>
        <span x-text="timeSince(board.created_at)"></span>
    </div>
    <!-- Title -->
    <h1 class="text-2xl sm:text-3xl font-extrabold bg-gradient-to-r from-pink-500 to-purple-500 bg-clip-text text-transparent mb-1"
        x-text="board.title"></h1>
    <!-- Description -->
    <div x-show="board.description" class="text-base text-black-800 dark:text-black-200 leading-snug">
        <p class="whitespace-pre-line" x-text="board.description"></p>
    </div>
</div>

    {{-- 🌐 Media Preview --}}
<div class="order-2 md:order-1 md:col-span-3 w-full mb-3">
    <template x-if="files && files.length">
        <div class="md:col-span-3">
            <div
                class="mt-3 w-full max-w-md mx-auto aspect-[9/12] min-h-[180px] rounded-xl overflow-hidden flex items-center justify-center relative z-0 bg-gray-50 dark:bg-gray-800 shadow-inner"
                x-data="{ currentIndex: 0 }"
            >
                <!-- 🔢 File count -->
                <template x-if="files.length > 1">
                    <div class="absolute top-2 right-3 bg-black/60 text-white text-xs px-2 py-0.5 rounded-full z-10">
                        <span x-text="`${currentIndex + 1} / ${files.length}`"></span>
                    </div>
                </template>

                <!-- 📸 Media Preview -->
                <div class="flex items-center justify-center w-full h-full">
                    <template x-if="files[currentIndex].type === 'image'">
                        <img
                            :src="files[currentIndex].path"
                            alt="Preview"
                            class="max-h-full max-w-full object-contain transition-transform duration-300 group-hover:scale-[1.03] cursor-pointer"
                        />
                    </template>
                    <template x-if="files[currentIndex].type === 'video'">
                        <video
                            :src="files[currentIndex].path"
                            playsinline
                            preload="metadata"
                            muted
                            autoplay
                            loop
                            class="max-h-full max-w-full object-contain rounded-xl transition-transform duration-300 group-hover:scale-[1.02] cursor-pointer"
                        ></video>
                    </template>
                </div>

                <!-- ⬅ Prev Arrow -->
                <button
                    x-show="files.length > 1"
                    @click="if (currentIndex > 0) currentIndex--"
                    :disabled="currentIndex === 0"
                    class="absolute left-2 bg-white dark:bg-gray-700 bg-opacity-80 dark:bg-opacity-70 rounded-full p-1.5 shadow hover:bg-opacity-100 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    style="top: 50%; transform: translateY(-50%);"
                >◀</button>

                <!-- ➡ Next Arrow -->
                <button
                    x-show="files.length > 1"
                    @click="if (currentIndex < files.length - 1) currentIndex++"
                    :disabled="currentIndex === files.length - 1"
                    class="absolute right-2 bg-white dark:bg-gray-700 bg-opacity-80 dark:bg-opacity-70 rounded-full p-1.5 shadow hover:bg-opacity-100 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    style="top: 50%; transform: translateY(-50%);"
                >▶</button>
            </div>
        </div>
    </template>
</div>

<!-- 💥 Reactions -->
<div class="flex flex-wrap gap-2 mb-6">
  <template x-for="(emoji, mood) in reactionMoods" :key="mood">
    <button
      @click.prevent="react(board.id, mood)"
      class="px-3 py-1 text-xs rounded-full font-medium flex items-center gap-1 transition-all"
      :class="[
        board.user_reacted_mood === mood ? 'ring-2 ring-offset-1 ring-pink-400' : '',
        mood === 'fire' && 'bg-blue-100 text-blue-700',
        mood === 'love' && 'bg-orange-100 text-orange-700',
        mood === 'funny' && 'bg-red-100 text-red-700',
        mood === 'mind-blown' && 'bg-purple-100 text-purple-700',
        mood === 'cool' && 'bg-teal-100 text-teal-700',
        mood === 'crying' && 'bg-amber-100 text-amber-700',
        mood === 'clap' && 'bg-indigo-100 text-indigo-700',
        mood === 'flirty' && 'bg-yellow-100 text-yellow-700',
      ]">
      <span x-text="emoji"></span>
      <span class="capitalize" x-text="mood"></span>
      <span x-text="getReactionCount(board, mood)" class="text-pink-500 text-[0.75rem]"></span>
    </button>
  </template>
</div>

    {{-- 💬 Comment Box --}}
    <div class="bg-white p-4 rounded-xl shadow mb-6">
        <div class="flex gap-2">
            <input type="text" x-model="board.newComment" placeholder="Write a comment..." class="flex-1 px-4 py-2 rounded-full border border-gray-300 text-sm">
            <button @click="postComment" :disabled="isSendDisabled()"
                    class="bg-blue-500 text-white px-4 py-2 rounded-full disabled:opacity-40">Send</button>
        </div>
    </div>

    {{-- 📃 Comments List --}}
    <div class="space-y-4">
        <template x-if="board.comments.length > 0">
            <template x-for="comment in board.comments" :key="comment.id">
                <div class="bg-gray-50 p-4 rounded-xl shadow">
                    <div class="flex justify-between items-center">
                        <a
                        :href="'/space/' + comment.user.username"
                        class="font-semibold text-sm text-blue-600 hover:underline"
                        x-text="'@' + comment.user.username"
                        ></a>
                        <span class="text-xs text-gray-400" x-text="timeSince(comment.created_at)"></span>
                    </div>
                    <p class="text-sm text-gray-700 mt-1" x-text="comment.body"></p>
                    <div class="flex items-center gap-3 mt-2">
                        <button @click="reactToComment(comment, 'like')"
                                class="text-xs"
                                :class="comment.user_reacted_type === 'like' ? 'text-blue-600 font-bold' : 'text-gray-500 hover:text-blue-600'">
                            ⬆️ Like (<span x-text="comment.like_count"></span>)
                        </button>

                        <button @click="reactToComment(comment, 'dislike')"
                                class="text-xs"
                                :class="comment.user_reacted_type === 'dislike' ? 'text-red-600 font-bold' : 'text-gray-500 hover:text-red-600'">
                            🔽 Dislike (<span x-text="comment.dislike_count"></span>)
                        </button>
                        <button class="text-xs text-gray-500 hover:text-purple-600" @click="toggleReply(comment)">🖊️ Reply</button>
                        <template x-if="comment.replies && comment.replies.length > 0">
                            <a :href="'/comments/' + comment.id + '/replies'"
                               class="text-xs text-gray-500 hover:text-indigo-600">
                                💬 <span x-text="comment.replies.length + ' Replies'"></span>
                            </a>
                        </template>
                    </div>

                    <div class="mt-2" x-show="comment.showReply" x-transition>
                        <div class="flex items-center gap-2 mt-2">
                            <input type="text" x-model="comment.replyText" class="w-full px-3 py-2 text-sm border rounded-lg" :placeholder="'@' + comment.user.username">
                            <button @click="sendReply(comment)" class="bg-blue-500 text-white text-sm px-3 py-1 rounded">Send</button>
                        </div>
                    </div>
                </div>
            </template>
        </template>
        <template x-if="board.comments.length === 0">
            <p class="text-gray-500 text-sm italic">No comments yet. Be the first to drop some love ✨</p>
        </template>
    </div>
</div>

{{-- 🌟 Toast Notification --}}
<div id="toast" class="fixed bottom-6 right-6 bg-gray-800 text-white text-sm px-4 py-2 rounded-lg shadow opacity-0 pointer-events-none transition-opacity duration-300 z-[9999]"></div>
@endsection


@push('scripts')
<script>
    document.addEventListener("alpine:init", () => {
        console.log("✅ Alpine initialized");

        Alpine.data('singleBoard', (raw, images, video) => ({
            board: {
                ...raw,
                newComment: '',
                comments: (raw.comments || []).map(comment => ({
                ...comment,
                showReply: false,
                replyText: `@${comment.user.username} `,
                replies: comment.replies || [],
                like_count: comment.like_count || 0,
                dislike_count: comment.dislike_count || 0,
                user_reacted_type: comment.user_reacted_type || null,
            }))
            },

            files: [
                ...(images || []).map(path => ({
                    path: path.startsWith('http') ? path : `/storage/${path.replace(/^\/?storage\//, '')}`,
                    type: 'image'
                })),
                ...(video ? [{
                    path: video.startsWith('http') ? video : `/storage/${video.replace(/^\/?storage\//, '')}`,
                    type: 'video'
                }] : [])
            ],

            moods: {
                excited: "🔥",
                happy: "😊",
                chill: "😎",
                thoughtful: "🤔",
                sad: "😭",
                flirty: "😏",
                mindblown: "🤯",
                love: "💖"
            },

            reactionMoods: {
            fire: '🔥',
            love: '❤️',
            funny: '😂',
            'mind-blown': '🤯',
            cool: '😎',
            crying: '😭',
            clap: '👏',
            flirty: '😉'
            },

            react(boardId, mood) {
              const board = (Array.isArray(this.boards) ? this.boards.find(b => b.id === boardId) : null) || this.board;
                if (!board) { this.showToast("Board not found 😢", true); return; }

                if (board.user_reacted_mood === mood) {
                    this.showToast("You already picked this mood 💅");
                    return;
                }

            showToast("Reacting...");

            setTimeout(() => {
                fetch('/reaction', {
                method: 'POST',
                headers: this._headers(),
                body: JSON.stringify({ mood_board_id: board.id, mood })
                })
                .then(res => res.ok ? res.json() : Promise.reject())
                .then(() => {
                const prev = board.user_reacted_mood;
                if (prev && prev !== mood) {
                    board[prev + '_count'] = Math.max(0, (board[prev + '_count'] || 0) - 1);
                }
                board[mood + '_count'] = (board[mood + '_count'] || 0) + 1;
                board.user_reacted_mood = mood;
                showToast("Mood updated 💖");
                })
                .catch(() => {
                showToast("Failed to react 😢", true);
                });
            }, 1000);
            },

            getReactionCount(board, mood) {
            return board[mood + '_count'] ?? 0;
            },

            // ✅ Commenting
            isSendDisabled() {
                return !this.board.newComment.trim();
            },

            postComment() {
                if (!this.board.newComment.trim()) return;

                showToast("Commenting...");

                setTimeout(() => {
                    fetch(`/boards/${this.board.id}/comments`, {
                        method: 'POST',
                        headers: this._headers(),
                        body: JSON.stringify({ body: this.board.newComment.trim() }),
                    })
                    .then(res => res.ok ? res.json() : Promise.reject())
                    .then(data => {
                        this.board.comments.push({
                            ...data.comment,
                            showReply: false,
                            replyText: `@${data.comment.user.username} `,
                            replies: [],
                            like_count: 0,
                            dislike_count: 0,
                            user_reacted_type: null,
                        });
                        this.board.newComment = '';
                        this.board.comment_count++;
                        showToast("Comment posted 🎉");
                    })
                    .catch(() => {
                        showToast("Comment failed 😢", true);
                    });
                }, 3000);
            },

            // ✅ Reply inline
            toggleReply(comment) {
                comment.showReply = !comment.showReply;
            },

            sendReply(comment) {
                if (!comment.replyText.trim()) return;

                showToast("Replying...");

                setTimeout(() => {
                    fetch(`/comments/${comment.id}/replies`, {
                        method: 'POST',
                        headers: this._headers(),
                        body: JSON.stringify({ body: comment.replyText }),
                    })
                    .then(res => res.ok ? res.json() : Promise.reject())
                    .then(data => {
                        comment.replies.push(data.reply);
                        comment.replyText = '';
                        showToast("Reply added 💬");
                    })
                    .catch(() => {
                        showToast("Reply failed 😢", true);
                    });
                }, 1000);
            },

            // ✅ Utils
            _headers() {
                return {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                };
            },

            timeSince(date) {
                const seconds = Math.floor((new Date() - new Date(date)) / 1000);
                const interval = seconds / 3600;
                if (interval > 24) return `${Math.floor(interval / 24)} days ago`;
                if (interval >= 1) return `${Math.floor(interval)} hrs ago`;
                if (seconds > 60) return `${Math.floor(seconds / 60)} mins ago`;
                return "just now";
            },

            reactToComment(comment, type) {
                showToast("Reacting to comment...");

                setTimeout(() => {
                    fetch(`/comments/${comment.id}/react`, {
                        method: 'POST',
                        headers: this._headers(),
                        body: JSON.stringify({ type })
                    })
                    .then(res => res.ok ? res.json() : Promise.reject())
                    .then(data => {
                        comment.user_reacted_type = type;
                        comment.like_count = data.like_count;
                        comment.dislike_count = data.dislike_count;
                        showToast(type === 'like' ? "You liked this 💙" : "You disliked this 😬");
                    })
                    .catch(() => {
                        showToast("Failed to react to comment 💔", true);
                    });
                }, 1000); // 1 second delay
            },

            // ✅ Toasts
            toastMessage: '',
            toastError: false,
            toastVisible: false,

            _showToast(message, isError = false) {
                this.toastVisible = false;
                this.toastMessage = message;
                this.toastError = isError;
                setTimeout(() => {
                    this.toastVisible = true;
                    setTimeout(() => this.toastVisible = false, 3000);
                }, 10);
            }
        }));
    });
</script>

<script>
function showToast(message, isError = false) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.style.backgroundColor = isError ? '#e53935' : '#38a169';

    toast.classList.remove('opacity-0', 'pointer-events-none');
    toast.classList.add('opacity-100');

    setTimeout(() => {
        toast.classList.remove('opacity-100');
        toast.classList.add('opacity-0', 'pointer-events-none');
    }, 3000);
}
</script>
@endpush

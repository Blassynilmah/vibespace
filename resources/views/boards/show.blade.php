@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto py-10 px-4" x-data="singleBoard({{ json_encode($board) }})">
    {{-- ⬅️ Back to Home --}}
    <div class="mb-6">
        <a href="{{ route('home') }}#board-{{ $board['id'] }}"
           class="text-sm text-blue-500 hover:underline">← Back to Feed</a>
    </div>

    {{-- 🔹 Board Header --}}
    <div class="mb-6">
        <h1 class="text-3xl font-bold mb-2 text-pink-600" x-text="board.title"></h1>
        <p class="text-gray-600 text-sm mb-2">
            By <a :href="'/space/' + board.user.username" class="text-blue-500 hover:underline" x-text="'@' + board.user.username"></a>
        </p>
        <div class="flex items-center gap-2 mb-2">
            <template x-if="board.mood">
                <span class="text-xs font-medium px-3 py-1 rounded-full capitalize"
                      :class="{
                        'bg-green-100 text-green-700': board.mood === 'relaxed',
                        'bg-yellow-100 text-yellow-700': board.mood === 'craving',
                        'bg-red-100 text-red-700': board.mood === 'hyped',
                        'bg-purple-100 text-purple-700': board.mood === 'obsessed'
                    }"
                      x-text="moods[board.mood] + ' ' + board.mood.charAt(0).toUpperCase() + board.mood.slice(1) + ' Vibes'">
                </span>
            </template>
        </div>
        <p class="text-gray-500 text-sm italic" x-text="board.description"></p>
    </div>

    {{-- 🌐 Media Preview --}}
    <div id="media-preview-{{ $board->id }}" class="w-full h-full mb-8"></div>

    {{-- 💥 Reactions --}}
    <div class="flex flex-wrap gap-2 mb-6">
        <template x-for="(emoji, mood) in moods" :key="mood">
            <button @click.prevent="react(mood)"
                    class="px-3 py-1 text-xs rounded-full font-medium flex items-center gap-1 transition-all"
                    :class="[
                        mood === 'relaxed' ? 'bg-green-100 text-green-700' :
                        mood === 'craving' ? 'bg-yellow-100 text-yellow-700' :
                        mood === 'hyped' ? 'bg-red-100 text-red-700' :
                        mood === 'obsessed' ? 'bg-purple-100 text-purple-700' : '',
                        board.user_reacted_mood === mood ? 'ring-2 ring-purple-500' : ''
                    ]">
                <span x-text="emoji"></span>
                <span class="capitalize" x-text="mood"></span>
                <span x-text="board[mood + '_count'] ?? 0" class="text-pink-500 text-[0.75rem]"></span>
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
                        <p class="font-semibold text-sm text-blue-600" x-text="'@' + comment.user.username"></p>
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

        Alpine.data('singleBoard', (raw) => ({
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

            moods: {
                relaxed: "😌",
                craving: "🤤",
                hyped: "🔥",
                obsessed: "🫠"
            },

            // ✅ Reaction
            react(mood) {
                if (this.board.user_reacted_mood === mood) {
                    showToast("You already picked this mood 💅");
                    return;
                }

                showToast("Reacting...");

                setTimeout(() => {
                    fetch('/reaction', {
                        method: 'POST',
                        headers: this._headers(),
                        body: JSON.stringify({
                            mood_board_id: this.board.id,
                            mood: mood
                        }),
                    })
                    .then(res => res.ok ? res.json() : Promise.reject())
                    .then(() => {
                        const prev = this.board.user_reacted_mood;
                        if (prev && prev !== mood) this.board[prev + '_count']--;
                        this.board[mood + '_count']++;
                        this.board.user_reacted_mood = mood;
                        showToast("Mood updated 💖");
                    })
                    .catch(() => {
                        showToast("Failed to react 😢", true);
                    });
                }, 3000);
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
                }, 3000);
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
                }, 3000);
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('media-preview-{{ $board->id }}');
    const mediaPath = @json($board->image ?? $board->video);
    if (!mediaPath || !container) return;

    const fullPath = mediaPath.startsWith('http') ? mediaPath : `/storage/${mediaPath}`;
    const ext = fullPath.split('.').pop().toLowerCase();

    if (['mp4', 'webm', 'ogg'].includes(ext)) {
        const wrapper = document.createElement('div');
        wrapper.className = "relative max-w-[75%] mx-auto rounded-2xl overflow-hidden group border-2 border-pink-500 shadow-md";

        const video = document.createElement('video');
        video.src = fullPath;
        video.playsInline = true;
        video.preload = "metadata";
        video.className = "w-full h-auto max-h-[100vh] object-cover rounded-2xl";
        video.muted = true;
        video.autoplay = true;
        video.loop = true;

        let lastTap = 0;
        let tapTimeout;

        video.addEventListener('click', (e) => {
            const now = new Date().getTime();
            const tapX = e.offsetX;
            const isLeft = tapX < video.offsetWidth / 2;
            const doubleTap = now - lastTap < 300;

            if (doubleTap) {
                clearTimeout(tapTimeout);
                if (isLeft) {
                    video.currentTime = Math.max(0, video.currentTime - 10);
                    flashSeek(video, '⏪ -10s');
                } else {
                    video.currentTime = Math.min(video.duration, video.currentTime + 10);
                    flashSeek(video, '+10s ⏩');
                }
            } else {
                tapTimeout = setTimeout(() => {
                    if (video.paused) video.play();
                    else video.pause();
                }, 250);
            }

            lastTap = now;
        });

        const muteBtn = document.createElement('button');
        muteBtn.innerHTML = getMuteIcon(true);
        muteBtn.className = "absolute top-2 right-2 bg-black/60 text-white rounded-full p-1 z-10";
        muteBtn.addEventListener('click', () => {
            video.muted = !video.muted;
            muteBtn.innerHTML = getMuteIcon(video.muted);
        });

        const progress = document.createElement('input');
        progress.type = 'range';
        progress.min = 0;
        progress.max = 100;
        progress.step = 0.1;
        progress.value = 0;
        progress.className = "absolute bottom-0 left-0 w-full h-1 appearance-none z-10 cursor-pointer";
        progress.style.height = '4px';
        progress.style.background = 'linear-gradient(to right, #ec4899 0%, #ec4899 0%, #ddd 0%, #ddd 100%)';

        video.addEventListener('timeupdate', () => {
            const val = (video.currentTime / video.duration) * 100 || 0;
            progress.value = val;
            progress.style.background = `linear-gradient(to right, #ec4899 0%, #ec4899 ${val}%, #ddd ${val}%, #ddd 100%)`;
        });

        progress.addEventListener('input', () => {
            video.currentTime = (progress.value / 100) * video.duration;
        });

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    video.play().catch(() => {});
                } else {
                    video.pause();
                }
            });
        }, { threshold: 0.5 });

        observer.observe(video);

        wrapper.appendChild(video);
        wrapper.appendChild(progress);
        wrapper.appendChild(muteBtn);
        container.appendChild(wrapper);
    } else if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
        const img = document.createElement('img');
        img.src = fullPath;
        img.alt = "Moodboard Image";
        img.className = "w-full h-auto object-cover rounded-2xl border-2 border-pink-500 shadow";
        container.appendChild(img);
    }

    function flashSeek(video, label) {
        const flash = document.createElement('div');
        flash.textContent = label;
        flash.className = "absolute inset-0 flex items-center justify-center text-white text-xl font-bold bg-black/50 animate-pulse z-20";
        video.parentElement.appendChild(flash);
        setTimeout(() => flash.remove(), 500);
    }

    function getMuteIcon(muted) {
        return muted
            ? `<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="white" viewBox="0 0 24 24"><path d="M5 9v6h4l5 5V4l-5 5H5z"/></svg>`
            : `<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="white" viewBox="0 0 24 24"><path d="M5 9v6h4l5 5V4l-5 5H5zm14.5 3c0-1.77-.77-3.36-2-4.47v8.94c1.23-1.11 2-2.7 2-4.47z"/></svg>`;
    }
});
</script>
@endpush

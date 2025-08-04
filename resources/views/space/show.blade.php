@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4" 
     x-data="vibeProfile('{{ $username }}', {{ $viewerId ?? 'null' }}, {{ json_encode($isFollowing) }}, {{ $followerCount }})" 
     x-init="init()">

    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-sm font-medium text-pink-600 hover:underline mb-6">
        ← Back to Home
    </a>

    {{-- 🧍‍♂️ Profile Header --}}
    <div class="bg-white rounded-xl shadow p-6 flex flex-col sm:flex-row sm:items-center justify-between mb-8">
        <div class="flex items-center gap-4" x-show="user">
            <!-- 🖼 Profile Picture -->
            <img
                :src="user?.profile_picture 
                    ? ('/storage/' + user.profile_picture)
                    : '/storage/moodboard_images/Screenshot 2025-07-14 032412.png'"
                alt="Profile Picture"
                class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover border-2 border-pink-500">

            <!-- 🧑 Username & Join Date -->
            <div>
                <h2 class="text-2xl font-bold text-gray-800">
                    @<span x-text="user?.username"></span>
                </h2>
                <p class="text-sm text-gray-500" x-text="'Joined ' + new Date(user?.joined_at).toLocaleDateString()"></p>
            </div>
        </div>

        <!-- 👤 Profile Actions -->
        <div class="flex gap-2" x-show="user">
            <!-- Show Edit Profile if it's the viewer's own profile -->
            <template x-if="user && viewerId === user.id">
                <button type="button"
                    @click="Alpine.store('modal').username = user.username; Alpine.store('modal').showEditModal = true"
                    class="bg-pink-600 hover:bg-pink-700 text-white text-xs sm:text-sm px-3 sm:px-4 py-1.5 sm:py-2 rounded-md flex items-center gap-1 sm:gap-2 transition-all">
                    ✏️ Edit Profile
                </button>
            </template>

            <!-- Show Message, Follow, Tip if it's someone else's profile -->
            <template x-if="viewerId && user && viewerId !== user.id">
                <div class="flex flex-col items-end">
                    <div class="flex gap-2 mb-2">
                        <!-- 💬 Message Button -->
                        <button 
                            @click="window.location.href = `/messages?receiver_id=${user.id}`"
                            class="bg-blue-500 hover:bg-blue-600 text-white text-xs sm:text-sm px-3 sm:px-4 py-1.5 sm:py-2 rounded-md transition-all">
                            💬 Message
                        </button>

                        <!-- ➕ Follow Button -->
                        <button 
                            :disabled="followClicks >= 5"
                            @click="toggleFollow"
                            x-text="isFollowing ? '✔️ Following' : '➕ Follow'"
                            :class="[
                                'text-sm px-4 py-2 rounded-lg transition',
                                isFollowing 
                                    ? 'bg-green-500 hover:bg-green-600 text-white' 
                                    : 'bg-gray-200 hover:bg-gray-300 text-gray-800',
                                followClicks >= 5 ? 'opacity-50 cursor-not-allowed' : ''
                            ]">
                        </button>

                        <!-- 💸 Tip Button -->
                        <button class="bg-yellow-400 hover:bg-yellow-500 text-white text-xs sm:text-sm px-3 sm:px-4 py-1.5 sm:py-2 rounded-md transition-all">
                            💸 Tip
                        </button>
                    </div>

                    <!-- 👥 Follower & Following Counts -->
                    <div class="flex gap-6 text-sm text-gray-600">
                        <div><strong x-text="followerCount"></strong> Followers</div>
                        <div><strong>{{ $user->following_count }}</strong> Following</div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- 🎭 MoodBoards Header --}}
    <h3 class="text-xl font-semibold mb-4" x-show="user">
        🎭 MoodBoards by <span class="text-black font-bold">@<span x-text="user?.username"></span></span>
    </h3>

    {{-- 🎨 MoodBoards List --}}
    <div class="space-y-6" x-show="user">
        <template x-for="board in filteredBoards" :key="board.id">
            <!-- 🧠 Board Card -->
            <div class="bg-white rounded-2xl shadow hover:shadow-lg transition-all p-4 sm:p-5 group">

                <!-- 🧠 Title + Mood -->
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div class="flex items-center gap-2 min-w-0">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-800 truncate" x-text="board.title"></h3>
                        <template x-if="board.latest_mood">
                            <span class="text-[0.65rem] sm:text-xs font-medium px-2 py-0.5 rounded-full capitalize"
                                :class="{
                                    'bg-blue-100 text-blue-700': board.latest_mood === 'relaxed',
                                    'bg-orange-100 text-orange-700': board.latest_mood === 'craving',
                                    'bg-pink-100 text-pink-700': board.latest_mood === 'hyped',
                                    'bg-purple-100 text-purple-700': board.latest_mood === 'obsessed'
                                }"
                                x-text="moods[board.latest_mood] + ' ' + board.latest_mood.charAt(0).toUpperCase() + board.latest_mood.slice(1)">
                            </span>
                        </template>
                    </div>

                    <!-- 👤 Avatar -->
                    <div class="flex items-center gap-2">
                        <img
                            :src="board.user?.profile_picture 
                                ? '/storage/' + board.user.profile_picture 
                                : '/storage/moodboard_images/Screenshot 2025-07-14 032412.png'" 
                            alt="User Avatar" 
                            class="w-8 h-8 sm:w-12 sm:h-12 rounded-full object-cover border border-gray-300">
                            <a :href="'/space/' + board.user.username" class="text-blue-500 hover:underline" x-text="'@' + board.user.username"></a>                    </div>
                </div>

                <!-- 🕒 Timestamp -->
                <div class="text-[0.65rem] sm:text-xs text-gray-400 mt-1" x-text="'Posted ' + timeSince(board.created_at)"></div>

                <!-- 📖 Description -->
                <p class="text-gray-600 text-sm sm:text-base mt-2 line-clamp-3" x-text="board.description"></p>

                <!-- 🎞 Media Preview -->
                <div class="mt-4 max-w-[400px] max-h-[600px] rounded-lg overflow-hidden mx-auto" 
                     :id="'media-preview-' + board.id">
                </div>

                <!-- ❤️ Reactions -->
                <div class="flex flex-wrap gap-2 p-4 border-t mt-4">
                    <template x-for="(emoji, mood) in moods" :key="mood">
                        <button
                            @click.prevent="react(board.id, mood)"
                            class="px-2 sm:px-3 py-0.5 text-[0.65rem] sm:text-xs rounded-full font-medium flex items-center gap-1 transition-all"
                            :class="[
                                board.user_reacted_mood === mood ? 'ring-2 ring-offset-1 ring-pink-400' : '', 
                                mood === 'relaxed' && 'bg-green-100 text-green-700', 
                                mood === 'craving' && 'bg-yellow-100 text-yellow-700', 
                                mood === 'hyped' && 'bg-red-100 text-red-700', 
                                mood === 'obsessed' && 'bg-purple-100 text-purple-700'
                            ]">
                            <span x-text="emoji"></span>
                            <span class="capitalize" x-text="mood"></span>
                            <span x-text="getReactionCount(board, mood)" class="text-pink-500 text-[0.6rem] sm:text-[0.75rem]"></span>
                        </button>
                    </template>
                </div>

                <!-- 💬 Comments -->
                <div class="mt-4 px-4">
                    <div class="flex items-center gap-2 mb-2">
                        <input type="text" x-model="board.newComment" placeholder="Type a comment..." 
                            class="flex-1 px-3 py-1.5 rounded-full border border-gray-300 text-xs sm:text-sm">                        
                        <button
                            @click="postComment(board)"
                            :disabled="isSendDisabled(board)"
                           class="bg-blue-500 text-white px-4 py-2 rounded disabled:opacity-40 disabled:cursor-not-allowed">
                            Send
                        </button>
                    </div>
                    <div class="text-xs text-gray-500 flex justify-between">
                        <span x-text="(board.comment_count ?? 0) + ' comments'"></span>
                        <a :href="'/boards/' + board.id" class="text-pink-600 hover:underline text-sm font-medium">
                            → View Board
                        </a>
                    </div>
                </div>
            </div>
        </template>

        <!-- 💤 No Boards Yet -->
        <div class="text-gray-400 italic text-sm" x-show="filteredBoards.length === 0 && user">
            This user hasn’t posted any moodboards yet.
        </div>
    </div>

    {{-- 🔽 Load More --}}
    <div class="mt-6 flex justify-center" x-show="!allLoaded && filteredBoards.length">
        <button @click="loadBoards" class="bg-gray-800 text-white px-6 py-2 rounded-full hover:bg-gray-700">
            Load More
        </button>
    </div>

    {{-- ✅ Toast Box --}}
    <div id="toastBox" class="fixed bottom-6 right-6 z-1000 hidden">
        <div id="toastMessage" class="px-4 py-2 rounded shadow-lg text-white bg-green-500 text-sm font-medium"></div>
    </div>

</div>

{{-- ✏️ Edit Profile Modal --}}
<div x-data="profileEditor()" x-show="showEditModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white p-6 rounded-xl shadow-lg w-full max-w-md relative">
        <h2 class="text-lg font-semibold mb-4 text-gray-800">Edit Profile</h2>

        {{-- Username Update --}}
        <label class="block mb-1 text-sm font-medium">Username</label>
        <input 
            type="text" 
            x-model="username" 
            class="w-full px-4 py-2 border rounded mb-2"
            autocomplete="off"
        >
        <button 
            @click="updateUsername" 
            class="bg-pink-600 text-white px-4 py-2 rounded mb-4 hover:bg-pink-700"
        >
            Update Username
        </button>

        {{-- Profile Picture Update --}}
        <label class="block mb-1 text-sm font-medium">Profile Picture</label>
        <input 
            type="file" 
            @change="previewImage" 
            accept="image/*" 
            class="mb-2"
        >
        <template x-if="preview">
            <img :src="preview" class="w-24 h-24 rounded-full object-cover mb-2">
        </template>
        <button 
            @click="updateProfilePicture" 
            class="bg-blue-600 text-white px-4 py-2 rounded mb-4 hover:bg-blue-700"
        >
            Update Picture
        </button>

        {{-- Password Update --}}
        <label class="block mb-1 text-sm font-medium">Current Password</label>
        <input 
            type="password" 
            x-model="oldPassword" 
            class="w-full px-4 py-2 border rounded mb-2"
            autocomplete="new-password"
        >

        <label class="block mb-1 text-sm font-medium">New Password</label>
        <input 
            type="password" 
            x-model="newPassword" 
            class="w-full px-4 py-2 border rounded mb-2"
            autocomplete="new-password"
        >

        <label class="block mb-1 text-sm font-medium">Confirm New Password</label>
        <input 
            type="password" 
            x-model="confirmPassword" 
            class="w-full px-4 py-2 border rounded mb-2"
            autocomplete="new-password"
        >

        <button 
            @click="updatePassword" 
            class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"
        >
            Update Password
        </button>

        {{-- Close Button --}}
        <button 
            @click="showEditModal = false" 
            class="absolute top-2 right-3 text-gray-500 hover:text-black text-xl"
        >
            &times;
        </button>
    </div>
</div>





</div>
@endsection


@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('modal', Alpine.reactive({
        showEditModal: false,
        username: '',
    }));

    Alpine.data('profileEditor', () => ({
        preview: null,
        pictureFile: null,
        oldPassword: '',
        newPassword: '',
        confirmPassword: '',

         get username() {
            return Alpine.store('modal').username;
        },
        set username(val) {
            Alpine.store('modal').username = val;
        },
        async updateUsername() {
            try {
                const res = await fetch('/profile/update-username', {
                    method: 'POST',
                    headers: this._headers(),
                    body: JSON.stringify({ username: this.username }),
                });
                const data = await res.json();
                if (!res.ok) throw data;
                this.toast('Username updated ✅');
            } catch (err) {
                this.toast(err.message || 'Failed to update username 😢', 'error');
            }
        },

        async updateProfilePicture() {
            if (!this.pictureFile) return;

            const formData = new FormData();
            formData.append('profile_picture', this.pictureFile);

            try {
                const res = await fetch('/profile/update-picture', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this._csrf() },
                    body: formData
                });
                const data = await res.json();
                if (!res.ok) throw data;
                this.toast('Picture updated 📸');
            } catch (err) {
                this.toast(err.message || 'Failed to update picture 😞', 'error');
            }
        },

        async updatePassword() {
            if (this.newPassword !== this.confirmPassword) {
                return this.toast('Passwords do not match 🔐', 'error');
            }

            try {
                const res = await fetch('/profile/update-password', {
                    method: 'POST',
                    headers: this._headers(),
                    body: JSON.stringify({
                        old_password: this.oldPassword,
                        new_password: this.newPassword,
                        new_password_confirmation: this.confirmPassword
                    }),
                });
                const data = await res.json();
                if (!res.ok) throw data;
                this.oldPassword = this.newPassword = this.confirmPassword = '';
                this.toast('Password updated 🔒');
            } catch (err) {
                this.toast(err.message || 'Password update failed', 'error');
            }
        },

        previewImage(e) {
            const file = e.target.files[0];
            if (file) {
                this.pictureFile = file;
                this.preview = URL.createObjectURL(file);
            }
        },

        toast(msg, type = 'success') {
            const toast = document.getElementById('toastBox');
            const text = document.getElementById('toastMessage');
            text.textContent = msg;
            text.className = `px-4 py-2 rounded text-white text-sm font-medium shadow-lg ${type === 'error' ? 'bg-red-500' : 'bg-green-500'}`;
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3000);
        },

        _headers() {
            return {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this._csrf(),
            };
        },

        _csrf() {
            return document.querySelector('meta[name=csrf-token]').getAttribute('content');
        },

        get showEditModal() {
            return Alpine.store('modal').showEditModal;
        },
        set showEditModal(value) {
            Alpine.store('modal').showEditModal = value;
        },
    }));

    Alpine.data('vibeProfile', (username, viewerId, initialIsFollowing, initialFollowerCount) => ({
        user: null,
        boards: [],
        page: 1,
        allLoaded: false,
        viewerId,
        isFollowing: initialIsFollowing ?? false, // ✅ load accurate follow state
        followerCount: initialFollowerCount ?? 0, // ✅ load accurate follower count
        followClicks: 0,
        clickResetTimer: null,

        moods: {
            relaxed: "😌",
            craving: "🤤",
            hyped: "🔥",
            obsessed: "🫠"
        },

        init() {
            this.loadBoards();
        },

        openEditModal() {
            this.username = this.user.username;
            Alpine.store('modal').showEditModal = true;
        },

        get filteredBoards() {
            return this.boards;
        },

        async loadBoards() {
            if (this.allLoaded) return;

            try {
                const res = await fetch(`/api/users/${username}/boards?page=${this.page}`);
                if (!res.ok) throw new Error('Failed to load boards');

                const data = await res.json();

                if (this.page === 1) {
                    this.user = data.user;
                    this.isFollowing = data.user?.is_following ?? false;
                    this.followerCount = data.user?.follower_count ?? 0;

                }


                if (data.boards?.length) {
                    data.boards.forEach(board => {
                        const exists = this.boards.some(b => b.id === board.id);
                        if (!exists) {
                            board.newComment = '';
                            board.comment_count = board.comment_count ?? 0;
                            this.boards.push(board);
                            this.$nextTick(() => this.renderMediaPreview(board));
                        }
                    });
                    this.page++;
                } else {
                    this.allLoaded = true;
                }
            } catch (err) {
                console.error("❌ Failed to load profile:", err);
            }
        },

        async toggleFollow() {
            if (this.followClicks >= 5) return;

            this.followClicks++;

            // Reset counter after 1 min
            if (!this.clickResetTimer) {
                this.clickResetTimer = setTimeout(() => {
                    this.followClicks = 0;
                    this.clickResetTimer = null;
                }, 60000);
            }

            try {
                const res = await fetch(`/follow/${this.user.id}`, {
                    method: 'POST',
                    headers: this._headers()
                });

                const data = await res.json();
                if (!res.ok) throw data;

                if (!res.ok) throw new Error("Failed to follow/unfollow");

                // ⏫ Flip the state and update count
                this.isFollowing = !this.isFollowing;
                this.followerCount += this.isFollowing ? 1 : -1;

                // ✅ Optional toast
                this.showToast(this.isFollowing ? "Now following 💕" : "Unfollowed 😔");
            } catch (err) {
                console.error("❌ error:too many entries", err);
                this.showToast("Error. Please try again later.", 'error');
            }
        },

            renderMediaPreview(board, attempts = 0) {
            const container = document.getElementById(`media-preview-${board.id}`);
            if (!container) {
                if (attempts < 5) {
                    setTimeout(() => this.renderMediaPreview(board, attempts + 1), 50);
                } else {
                    console.warn(`❗ Could not find container for board ${board.id} after 5 tries`);
                }
                return;
            }

            const mediaPath = board.image || board.video;
            if (!mediaPath) return;

            const fullPath = mediaPath.startsWith('http') ? mediaPath : `/storage/${mediaPath}`;
            const ext = fullPath.split('.').pop().toLowerCase();

            if (["mp4", "webm", "ogg"].includes(ext)) {
                const wrapper = document.createElement('div');
                wrapper.className = "relative w-full h-full rounded-lg overflow-hidden group";

                if (Alpine.store('videoSettings') === undefined) {
                    Alpine.store('videoSettings', Alpine.reactive({ muted: true }));
                }

                const video = document.createElement('video');
                video.src = fullPath;
                video.playsInline = true;
                video.preload = "metadata";
                video.className = "w-full h-full object-cover rounded-lg";

                let lastTapTime = 0;
                let tapTimeout = null;

                video.addEventListener('click', (e) => {
                    const currentTime = new Date().getTime();
                    const tapX = e.offsetX;
                    const width = video.offsetWidth;
                    const isLeft = tapX < width / 2;
                    const timeDiff = currentTime - lastTapTime;

                    if (tapTimeout) clearTimeout(tapTimeout);

                    if (timeDiff < 300) {
                        if (isLeft) {
                            video.currentTime = Math.max(0, video.currentTime - 10);
                            showSeekFlash('⏪ -10s', video);
                        } else {
                            video.currentTime = Math.min(video.duration, video.currentTime + 10);
                            showSeekFlash('+10s ⏩', video);
                        }
                    } else {
                        tapTimeout = setTimeout(() => {
                            video.paused ? video.play() : video.pause();
                        }, 250);
                    }

                    lastTapTime = currentTime;
                });

                video.muted = Alpine.store('videoSettings').muted;

                const muteBtn = document.createElement('button');
                muteBtn.innerHTML = muteIcon(video.muted);
                muteBtn.className = "absolute top-2 right-2 bg-black/60 text-white rounded-full p-1 z-10";
                muteBtn.addEventListener('click', () => {
                    const newMuted = !video.muted;
                    Alpine.store('videoSettings').muted = newMuted;
                    document.querySelectorAll('video').forEach(v => v.muted = newMuted);
                    muteBtn.innerHTML = muteIcon(newMuted);
                });

                const progress = document.createElement('input');
                progress.type = 'range';
                progress.min = 0;
                progress.max = 100;
                progress.value = 0;
                progress.step = 0.1;
                progress.className = "absolute bottom-0 left-0 w-full h-1 bg-transparent appearance-none z-10 cursor-pointer";
                progress.style.background = 'linear-gradient(to right, #ec4899 0%, #ec4899 0%, #ddd 0%, #ddd 100%)';
                progress.style.height = '4px';

                video.addEventListener('timeupdate', () => {
                    const value = (video.currentTime / video.duration) * 100 || 0;
                    progress.value = value;
                    progress.style.background = `linear-gradient(to right, #ec4899 0%, #ec4899 ${value}%, #ddd ${value}%, #ddd 100%)`;
                });

                progress.addEventListener('input', () => {
                    video.currentTime = (progress.value / 100) * video.duration;
                });

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            video.play().catch(() => {});
                            video.muted = Alpine.store('videoSettings').muted;
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
            } else if (["jpg", "jpeg", "png", "gif", "webp"].includes(ext)) {
                const img = document.createElement('img');
                img.src = fullPath;
                img.alt = "Moodboard Image";
                img.className = "w-full h-full rounded-lg border-2 border-blue-500 object-cover";
                container.appendChild(img);
            }

            function muteIcon(muted) {
                return muted
                    ? `<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="white" viewBox="0 0 24 24"><path d="M5 9v6h4l5 5V4l-5 5H5z"/></svg>`
                    : `<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="white" viewBox="0 0 24 24"><path d="M5 9v6h4l5 5V4l-5 5H5zm14.5 3c0-1.77-.77-3.36-2-4.47v8.94c1.23-1.11 2-2.7 2-4.47z"/></svg>`;
            }

            function showSeekFlash(text, parent) {
                const flash = document.createElement('div');
                flash.textContent = text;
                flash.className = "absolute inset-0 flex items-center justify-center text-white text-xl font-bold bg-black/50 animate-pulse z-20";
                parent.parentElement.appendChild(flash);
                setTimeout(() => flash.remove(), 500);
            }
        },

        react(boardId, mood) {
            const board = this.boards.find(b => b.id === boardId);
            if (!board || board.user_reacted_mood === mood) {
                this.showToast("You already picked this mood 💅", 'error');
                return;
            }

            this.showLoadingToast("Reacting...");

            setTimeout(() => {
                fetch('/reaction', {
                    method: 'POST',
                    headers: this._headers(),
                    body: JSON.stringify({ mood_board_id: boardId, mood: mood }),
                })
                .then(res => res.ok ? res.json() : Promise.reject())
                .then(data => {
                    const newMood = data.mood;
                    const prevMood = data.previous;

                    board.reaction_counts = board.reaction_counts || {};

                    if (prevMood && prevMood !== newMood) {
                        board.reaction_counts[prevMood] = Math.max(0, (board.reaction_counts[prevMood] || 0) - 1);
                        board[prevMood + '_count'] = Math.max(0, (board[prevMood + '_count'] || 0) - 1);
                    }

                    board.reaction_counts[newMood] = (board.reaction_counts[newMood] || 0) + 1;
                    board[newMood + '_count'] = (board[newMood + '_count'] || 0) + 1;

                    board.user_reacted_mood = newMood;
                    board.latest_mood = newMood;

                    this.showToast("Mood updated! 💖");
                })
                .catch(() => this.showToast("Failed to react 💔", 'error'));
            }, 3000);
        },

        getReactionCount(board, mood) {
            return board[mood + '_count'] ?? 0;
        },

        postComment(board) {
            if (!board.newComment.trim()) return;

            this.showLoadingToast("Commenting...");

            setTimeout(() => {
                fetch(`/boards/${board.id}/comments`, {
                    method: 'POST',
                    headers: this._headers(),
                    body: JSON.stringify({ body: board.newComment.trim() }),
                })
                .then(res => res.ok ? res.json() : Promise.reject())
                .then(data => {
                    board.comment_count = (board.comment_count ?? 0) + 1;
                    board.newComment = '';
                    this.showToast("Comment posted! 🎉");
                })
                .catch(() => this.showToast("Comment failed 😢", 'error'));
            }, 3000);
        },

        isSendDisabled(board) {
            return !board.newComment || board.newComment.trim() === '';
        },

        showToast(message = "Done!", type = 'success', delay = 3000) {
            const box = document.getElementById('toastBox');
            const msg = document.getElementById('toastMessage');

            msg.textContent = message;
            msg.className = `px-4 py-2 rounded shadow-lg text-white text-sm font-medium ${type === 'error' ? 'bg-red-500' : 'bg-green-500'}`;

            box.classList.remove('hidden');
            setTimeout(() => {
                box.classList.add('hidden');
            }, delay);
        },

        showLoadingToast(message = "Working...") {
            const box = document.getElementById('toastBox');
            const msg = document.getElementById('toastMessage');

            msg.textContent = message;
            msg.className = `px-4 py-2 rounded shadow-lg text-white text-sm font-medium bg-gray-600`;
            box.classList.remove('hidden');
        },

        timeSince(date) {
            const seconds = Math.floor((new Date() - new Date(date)) / 1000);
            const interval = seconds / 3600;
            if (interval > 24) return `${Math.floor(interval / 24)} days ago`;
            if (interval >= 1) return `${Math.floor(interval)} hrs ago`;
            if (seconds > 60) return `${Math.floor(seconds / 60)} mins ago`;
            return "just now";
        },

        messageUser(id) {
            console.log("📨 Open DM with user:", id);
            // TODO: open message modal
        },

        followUser(id) {
            console.log("➕ Follow user:", id);
            // TODO: send follow request
        },

        tipUser(id) {
            console.log("💸 Tip user:", id);
            // TODO: open tip modal or redirect to tip flow
        },


        _headers() {
            return {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
            };
        },
    }));
});
</script>
@endpush

@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto flex gap-8 px-2 sm:px-4 pb-0" x-data="vibeFeed" x-init="init">
    {{-- Left Sidebar --}}
    <div class="hidden lg:block w-1/5">
        <div class="sticky top-24">
            <h3 class="text-xl font-semibold mb-4">Mood Filters</h3>
            <div class="flex flex-col gap-2 mb-6">
                <template x-for="(emoji, mood) in moods" :key="mood">
                    <button
                        @click="toggleMood(mood)"
                        class="px-3 py-1 rounded-full text-sm font-medium transition-all text-left"
                        :class="selectedMoods.includes(mood) 
                            ? 'bg-pink-500 text-white shadow-md' 
                            : 'bg-gray-100 hover:bg-gray-200 text-gray-800'"
                        x-text="emoji + ' ' + mood.charAt(0).toUpperCase() + mood.slice(1)">
                    </button>
                </template>
            </div>

            <h3 class="text-xl font-semibold mb-4 mt-10">Media Type</h3>
            <div class="flex flex-col gap-2">
                <template x-for="(label, type) in mediaTypes" :key="type">
                    <button
                        @click="toggleMediaType(type)"
                        class="px-4 py-1 rounded-lg text-sm font-semibold transition-all text-left"
                        :class="selectedMediaTypes.includes(type) 
                            ? 'bg-pink-600 text-white shadow-md' 
                            : 'bg-gray-100 hover:bg-gray-200 text-gray-800'"
                        x-text="label">
                    </button>
                </template>
            </div>
        </div>
    </div>

    {{-- Main Feed --}}
    <div class="w-full lg:w-3/5 flex flex-col gap-6">
        <div class="mb-10 bg-gradient-to-r from-pink-500 to-purple-600 text-white p-10 rounded-3xl shadow-xl text-center">
            <h1 class="text-4xl sm:text-5xl font-extrabold mb-3">Welcome to VibeSpace</h1>
            <p class="text-lg sm:text-xl">Drop a mood. Catch a vibe. Connect with your people.</p>
        </div>

        {{-- Sticky Top Bar (Search + Create + Filter) --}}
        <div class="sticky top-0 z-40 bg-gradient-to-r from-pink-500 to-purple-600 text-white shadow-sm px-4 py-3">
            <div class="max-w-4xl mx-auto flex items-center justify-between gap-4">
                
                {{-- Search Input (left) --}}
                <div class="relative flex-1">
                    <input 
                        type="text" 
                        x-model.debounce.300ms="searchQuery" 
                        @input="searchUsers"
                        placeholder="Search users by name..." 
                        class="w-full px-4 py-2 rounded-full border border-white/30 bg-white/20 placeholder-white text-white text-sm focus:outline-none focus:ring-2 focus:ring-white"
                    >
                    <ul 
                        class="absolute z-50 left-0 w-full bg-white border border-gray-200 mt-2 rounded-xl shadow max-h-60 overflow-y-auto text-black"
                        x-show="searchResults.length > 0"
                        @click.outside="searchResults = []"
                    >
                        <template x-for="user in searchResults" :key="user.id">
                            <li 
                                class="px-4 py-2 hover:bg-pink-100 cursor-pointer text-sm"
                                @click="goToProfile(user.username)"
                                x-text="'@' + user.username">
                            </li>
                        </template>
                    </ul>
                </div>

                {{-- Create Button (middle) --}}
                <a href="{{ route('boards.create') }}"
                class="group relative h-10 rounded-full bg-white text-pink-600 overflow-hidden shadow transition-all duration-500 ease-in-out w-10 hover:w-48">
                    <span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-lg transition-opacity duration-300 ease-in-out group-hover:opacity-0">
                        +
                    </span>
                    <span class="pl-10 pr-4 opacity-0 group-hover:opacity-100 transition-opacity duration-500 ease-in-out text-sm whitespace-nowrap">
                        Create moodboard
                    </span>
                </a>

                {{-- Filter Button (right) --}}
                <button
                    @click="showMobileFilters = true"
                    class="lg:hidden group relative h-10 rounded-full bg-white text-pink-600 overflow-hidden shadow transition-all duration-500 ease-in-out w-10 hover:w-40">
                    <span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-lg transition-opacity duration-300 ease-in-out group-hover:opacity-0">
                        🧰
                    </span>
                    <span class="pl-10 pr-4 opacity-0 group-hover:opacity-100 transition-opacity duration-500 ease-in-out text-sm whitespace-nowrap">
                        Filters
                    </span>
                </button>
            </div>
        </div>

        {{-- Mobile Top Nav (Visible on small screens only) --}}
        <div class="lg:hidden sticky top-[60px] z-30 bg-white border-t border-b border-gray-200 shadow-sm">
            <div class="flex justify-around px-2 py-2 text-sm text-gray-700">
                <a href="{{ route('home') }}" class="flex flex-col items-center hover:text-pink-600 transition">
                    🏠
                    <span class="text-xs mt-1">Home</span>
                </a>
                <a href="/messages" class="flex flex-col items-center hover:text-pink-600 transition">
                    💌
                    <span class="text-xs mt-1">Messages</span>
                </a>
                <a href="{{ route('boards.me') }}" class="flex flex-col items-center hover:text-pink-600 transition">
                    💫
                    <span class="text-xs mt-1">Me</span>
                </a>
                <a href="/notifications" class="flex flex-col items-center hover:text-pink-600 transition">
                    🔔
                    <span class="text-xs mt-1">Alerts</span>
                </a>
                <a href="/settings" class="flex flex-col items-center hover:text-pink-600 transition">
                    ⚙️
                    <span class="text-xs mt-1">Settings</span>
                </a>
            </div>
        </div>

        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl sm:text-2xl md:text-3xl font-bold tracking-tight">🔥 Trending MoodBoards</h2>
        </div>

        <template x-for="board in filteredBoards" :key="board.id + '-' + board.created_at">
            <div class="bg-white rounded-2xl shadow hover:shadow-lg transition-all p-4 sm:p-5 group">
                
                {{-- Header --}}
                <div class="flex items-center justify-between gap-2 p-3 sm:p-4 flex-wrap">
                    <div class="flex items-center gap-1 sm:gap-2 min-w-0">
                        <h3 class="text-base sm:text-lg font-semibold" x-text="board.title"></h3>
                        <template x-if="board.latest_mood">
                            <span
                                class="text-[0.65rem] sm:text-xs font-medium px-2 py-0.5 rounded-full capitalize"
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
                    <div class="flex items-center gap-2">
                        <img
                            :src="board.user?.profile_picture 
                                ? '/storage/' + board.user.profile_picture 
                                : '/storage/moodboard_images/Screenshot 2025-07-14 032412.png'" 
                            alt="User Avatar" 
                            class="w-8 h-8 sm:w-12 sm:h-12 rounded-full object-cover border border-gray-300">
                        <a :href="'/space/' + board.user.username"
                        class="text-blue-500 hover:underline text-xs sm:text-sm"
                        x-text="'@' + board.user.username">
                        </a>     
                    </div>
                </div>

                {{-- Timestamp --}}
                <div class="px-3 sm:px-4 text-[0.65rem] sm:text-xs text-gray-400 mb-1" x-text="timeSince(board.created_at)"></div>

                {{-- Description --}}
                <p class="text-gray-600 text-sm sm:text-base px-3 sm:px-4 mb-3 line-clamp-3" x-text="board.description"></p>

                {{-- Media Preview --}}
                <div class="mt-3 mx-auto max-w-[400px] max-h-[600px] rounded-lg overflow-hidden" :id="'media-preview-' + board.id"></div>

                {{-- Reactions --}}
                <div class="flex flex-wrap gap-2 p-3 sm:p-4 border-t mt-auto">
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

                {{-- Comments --}}
                <div class="mt-3 px-3 sm:px-4">
                    <div class="flex items-center gap-2 mb-2">
                        <input type="text"
                            x-model="board.newComment"
                            placeholder="Type a comment..."
                            class="flex-1 px-3 py-1.5 rounded-full border border-gray-300 text-xs sm:text-sm">
                        <button
                            @click="postComment(board)"
                            :disabled="isSendDisabled(board)"
                            class="bg-blue-500 text-white px-3 sm:px-4 py-1.5 rounded text-xs sm:text-sm disabled:opacity-40 disabled:cursor-not-allowed">
                            Send
                        </button>
                    </div>
                    <div class="text-[0.65rem] sm:text-xs text-gray-500">
                        <span x-text="(board.comment_count ?? 0) + ' comments'"></span> • 
                        <a :href="'/boards/' + board.id" class="text-pink-500 hover:underline">View Board</a>
                    </div>
                </div>
            </div>
        </template>

        {{-- Load More Button --}}
        <div class="mt-10 text-center" x-show="!allLoaded">
            <button @click="loadBoards()" class="px-6 py-2 bg-gray-800 text-white rounded-full hover:bg-gray-700">
                Load More
            </button>
        </div>

        <!-- Mobile Filter Drawer -->
        <div 
            class="lg:hidden fixed inset-0 bg-black/30 z-50 flex items-end"
            x-show="showMobileFilters"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-full"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-full"
            style="display: none;"
            @click.self="showMobileFilters = false"
        >
            <div class="w-full bg-white rounded-t-2xl p-4 shadow-xl max-h-[80vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-800">Filters</h3>
                    <button @click="showMobileFilters = false" class="text-gray-500 hover:text-gray-800 text-xl">×</button>
                </div>

                {{-- Mood Filters --}}
                <div class="mb-4">
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Mood</h4>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="(emoji, mood) in moods" :key="mood">
                            <button
                                @click="toggleMood(mood)"
                                class="px-3 py-1 rounded-full text-xs font-medium transition"
                                :class="selectedMoods.includes(mood) 
                                    ? 'bg-pink-500 text-white' 
                                    : 'bg-gray-100 text-gray-800 hover:bg-gray-200'"
                                x-text="emoji + ' ' + mood.charAt(0).toUpperCase() + mood.slice(1)">
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Media Type Filters --}}
                <div>
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Media Type</h4>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="(label, type) in mediaTypes" :key="type">
                            <button
                                @click="toggleMediaType(type)"
                                class="px-3 py-1 rounded-full text-xs font-medium transition"
                                :class="selectedMediaTypes.includes(type) 
                                    ? 'bg-pink-600 text-white' 
                                    : 'bg-gray-100 text-gray-800 hover:bg-gray-200'"
                                x-text="label">
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Toast --}}
        <div id="toastBox" class="fixed bottom-6 right-6 z-50 hidden">
            <div id="toastMessage" class="px-4 py-2 rounded shadow-lg text-white bg-green-500 text-sm font-medium"></div>
        </div>
    </div>

{{-- Right Sidebar Navigation --}}
<div class="hidden lg:block w-1/5">
    <div class="sticky top-24 space-y-4">
        <h3 class="text-xl font-semibold mb-4">Navigation</h3>
        
        <a href="{{ route('home') }}"
           class="block px-4 py-2 rounded-lg font-medium text-sm text-gray-700 hover:bg-pink-100 transition">
           🏠 Home
        </a>

        <a href="/messages"
           class="block px-4 py-2 rounded-lg font-medium text-sm text-gray-700 hover:bg-pink-100 transition">
           💌 Messages
        </a>

        <a href="{{ route('boards.me') }}" class="block px-4 py-2 rounded-lg font-medium text-sm text-gray-700 hover:bg-pink-100 transition">
            💫
            <span class="text-xs mt-1">Me</span>
        </a>

        <a href="/notifications"
           class="block px-4 py-2 rounded-lg font-medium text-sm text-gray-700 hover:bg-pink-100 transition">
           🔔 Notifications
        </a>

        <a href="/settings"
           class="block px-4 py-2 rounded-lg font-medium text-sm text-gray-700 hover:bg-pink-100 transition">
           ⚙️ Settings
        </a>
    </div>
</div>
</div>
@endsection



@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('vibeFeed', () => ({
        boards: [],
        page: 1,
        allLoaded: false,
        selectedMoods: [],
        selectedMediaTypes: [],
        searchQuery: '',
        searchResults: [],
        showMobileFilters: false,

        moods: {
            relaxed: "😌",
            craving: "🤤",
            hyped: "🔥",
            obsessed: "🫠"
        },

        mediaTypes: {
            image: "🖼️ Image",
            video: "🎥 Video",
            text: "📝 Text"
        },

        init() {
            this.page = 1;
            this.boards = [];
            this.allLoaded = false;
            this.loadBoards();
        },

        get filteredBoards() {
            return this.boards.filter(board => {
                const moodMatch = this.selectedMoods.length === 0 || this.selectedMoods.includes(board.latest_mood);
                const hasImage = !!board.image;
                const hasVideo = !!board.video;
                const hasMedia = hasImage || hasVideo;
                let boardType = 'text';
                if (hasImage) boardType = 'image';
                if (hasVideo) boardType = 'video';

                const typeMatch = this.selectedMediaTypes.length === 0 || this.selectedMediaTypes.includes(boardType);

                return moodMatch && typeMatch;
            });
        },

        searchUsers() {
            if (this.searchQuery.trim().length === 0) {
                this.searchResults = [];
                return;
            }

            fetch(`/api/search-users?q=${encodeURIComponent(this.searchQuery)}`)
                .then(res => res.json())
                .then(data => {
                    this.searchResults = data.users;
                })
                .catch(err => {
                    console.error('Search error:', err);
                });
        },

        goToProfile(username) {
            window.location.href = `/space/${username}`;
        },

        toggleMood(mood) {
            const index = this.selectedMoods.indexOf(mood);
            if (index > -1) {
                this.selectedMoods.splice(index, 1);
            } else {
                this.selectedMoods.push(mood);
            }
            this.page = 1;
            this.boards = [];
            this.allLoaded = false;
            this.loadBoards();
        },

        toggleMediaType(type) {
            const index = this.selectedMediaTypes.indexOf(type);
            if (index > -1) {
                this.selectedMediaTypes.splice(index, 1);
            } else {
                this.selectedMediaTypes.push(type);
            }
            this.page = 1;
            this.boards = [];
            this.allLoaded = false;
            this.loadBoards();
        },

        async loadBoards() {
            if (this.allLoaded) return;

            const moodParams = this.selectedMoods.map(m => `moods[]=${m}`).join('&');
            const typeParams = this.selectedMediaTypes.map(t => `types[]=${t}`).join('&');
            const query = `?page=${this.page}&${moodParams}&${typeParams}`;

            const res = await fetch(`/api/boards${query}`);
            const json = await res.json();
            const newBoards = json.data;

            if (newBoards.length === 0) {
                this.allLoaded = true;
                return;
            }

            newBoards.forEach(board => {
                if (!this.boards.some(b => b.id === board.id)) {
                    board.newComment = '';
                    board.comment_count = board.comment_count ?? 0;
                    this.boards.push(board);
                    this.$nextTick(() => {
                        this.renderMediaPreview(board);
                    });
                }
            });

            this.page++;
        },

        renderMediaPreview(board) {
            const container = document.getElementById(`media-preview-${board.id}`);
            if (!container) return;

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

                    if (tapTimeout) {
                        clearTimeout(tapTimeout);
                        tapTimeout = null;
                    }

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
                            if (video.paused) {
                                video.play();
                            } else {
                                video.pause();
                            }
                        }, 250);
                    }

                    lastTapTime = currentTime;
                });

                video.muted = Alpine.store('videoSettings').muted;

                const muteBtn = document.createElement('button');
                muteBtn.innerHTML = Alpine.store('videoSettings').muted ? muteIcon() : unmuteIcon();
                muteBtn.className = "absolute top-2 right-2 bg-black/60 text-white rounded-full p-1 z-10";
                muteBtn.addEventListener('click', () => {
                    const newMuted = !video.muted;
                    Alpine.store('videoSettings').muted = newMuted;
                    document.querySelectorAll('video').forEach(v => v.muted = newMuted);
                    muteBtn.innerHTML = newMuted ? muteIcon() : unmuteIcon();
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

            function muteIcon() {
                return `<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="white" viewBox="0 0 24 24"><path d="M5 9v6h4l5 5V4l-5 5H5z"/></svg>`;
            }

            function unmuteIcon() {
                return `<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="white" viewBox="0 0 24 24"><path d="M5 9v6h4l5 5V4l-5 5H5zm14.5 3c0-1.77-.77-3.36-2-4.47v8.94c1.23-1.11 2-2.7 2-4.47z"/></svg>`;
            }

            function showSeekFlash(text, parent) {
                const flash = document.createElement('div');
                flash.textContent = text;
                flash.className = "absolute inset-0 flex items-center justify-center text-white text-xl font-bold bg-black/50 animate-pulse z-20";
                parent.parentElement.appendChild(flash);

                setTimeout(() => flash.remove(), 500);
            }
        },

        timeSince(date) {
            const seconds = Math.floor((new Date() - new Date(date)) / 1000);
            const interval = seconds / 3600;
            if (interval > 24) return `${Math.floor(interval / 24)} days ago`;
            if (interval >= 1) return `${Math.floor(interval)} hrs ago`;
            if (seconds > 60) return `${Math.floor(seconds / 60)} mins ago`;
            return "just now";
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

        _headers() {
            return {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
            };
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
        }
    }));
});
</script>
@endpush



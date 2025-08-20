@extends('layouts.app')

@push('scripts')
<script>
    window.rawUser = {
        ...@json($user),
        joined_at: "{{ $user->created_at->toISOString() }}",
        follower_count: {{ $followerCount }},
        following_count: {{ $followingCount }},
        is_following: @json($isFollowing)
    };

window.rawBoards = @json($formattedBoards);
</script>
@endpush
@section('content')
<div class="max-w-4xl mx-auto px-4" 
    x-data="vibeProfile('{{ $user->username }}', {{ $viewerId ?? 'null' }}, {{ json_encode($isFollowing) }}, {{ $followerCount }}, {{ $followingCount }}, window.rawUser, window.rawBoards)"
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
                        <div><strong x-text="followingCount"></strong> Following</div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- 🧭 Tab Navigation --}}
<div class="flex gap-4">
    <button @click="activeTab = 'favBoards'"
        :class="activeTab === 'favBoards' ? 'text-gray-800 font-semibold border-b-2 border-pink-500' : 'text-gray-400'"
        class="text-base sm:text-lg pb-1 transition">
        Favourite Boards
    </button>

    <button @click="activeTab = 'favTeasers'"
        :class="activeTab === 'favTeasers' ? 'text-gray-800 font-semibold border-b-2 border-pink-500' : 'text-gray-400'"
        class="text-base sm:text-lg pb-1 transition">
        Favourite Teasers
    </button>

    <button @click="activeTab = 'savedMoodboards'"
        :class="activeTab === 'savedMoodboards' ? 'text-gray-800 font-semibold border-b-2 border-pink-500' : 'text-gray-400'"
        class="text-base sm:text-lg pb-1 transition">
        Saved Moodboards
    </button>

    <button @click="activeTab = 'likedTeasers'"
        :class="activeTab === 'likedTeasers' ? 'text-gray-800 font-semibold border-b-2 border-pink-500' : 'text-gray-400'"
        class="text-base sm:text-lg pb-1 transition">
        Liked Teasers
    </button>
</div>

    <template x-if="activeTab === 'favBoards'">
        {{-- 🎨 MoodBoards List --}}
        <div class="space-y-6 flex flex-col gap-6 md:gap-8 z-0 mt-3" x-show="user">
            <template x-for="board in filteredBoards" :key="board.id + '-' + board.created_at">
                <div class="relative bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-lg transition-all duration-300 group overflow-hidden" style="transition: box-shadow .25s ease, transform .18s ease;" x-data="{ expanded: false }">
                    <div 
                    class="relative flex flex-col items-start p-3 sm:p-4 lg:p-6"
                    :class="board.files?.length ? 'md:grid md:grid-cols-5 md:gap-6' : 'md:flex md:flex-col'"
                    >
                        <!-- User Info, Title, Description (Top on mobile, right on desktop) -->
                        <div class="order-1 md:order-2 md:col-span-2 flex flex-col w-full mb-3 md:mb-0">
                            <!-- User Info -->
                            <div class="flex items-start gap-3 mb-2 shrink-0">
                                <img
                                    :src="board.user?.profile_picture
                                        ? '/storage/' + board.user.profile_picture
                                        : '/storage/moodboard_images/Screenshot 2025-07-14 032412.png'"
                                    alt="User Avatar"
                                    class="w-10 h-10 sm:w-12 sm:h-12 rounded-full border-2 border-pink-300 dark:border-pink-500 object-cover"
                                    style="box-shadow: 0 2px 6px rgba(0,0,0,0.08);"
                                >
                                <div class="flex flex-wrap items-center text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                                    <template x-if="board.latest_mood">
                                        <span
                                            class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full"
                                            :class="{
                                                'bg-blue-100 text-blue-700': board.latest_mood === 'excited',
                                                'bg-orange-100 text-orange-700': board.latest_mood === 'happy',
                                                'bg-pink-100 text-pink-700': board.latest_mood === 'chill',
                                                'bg-purple-100 text-purple-700': board.latest_mood === 'thoughtful',
                                                'bg-teal-100 text-teal-700': board.latest_mood === 'sad',
                                                'bg-amber-100 text-amber-700': board.latest_mood === 'flirty',
                                                'bg-indigo-100 text-indigo-700': board.latest_mood === 'mindblown',
                                                'bg-yellow-100 text-yellow-700': board.latest_mood === 'love',
                                            }"
                                            style="backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); box-shadow: 0 1px 0 rgba(0,0,0,0.05);"
                                        >
                                            <span x-text="moods[board.latest_mood]"></span>
                                            <span x-text="board.latest_mood.charAt(0).toUpperCase() + board.latest_mood.slice(1)"></span>
                                            <span>Vibes</span>
                                        </span>
                                    </template>

                                    <div>
                                        <a 
                                            :href="'/space/' + board.user.username" 
                                            class="hover:underline font-medium text-blue-600 text-xs sm:text-sm"
                                            x-text="'@' + board.user.username">
                                        </a>

                                        <span class="mx-1">•</span>

                                        <span x-text="timeSince(board.created_at)"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col">
                                <h3 class="text-base sm:text-lg font-extrabold bg-gradient-to-r from-pink-500 to-purple-500 bg-clip-text text-transparent mb-1"
                                    x-text="board.title">
                                </h3>
                                <div x-show="board.description" class="text-sm text-black-800 dark:text-black-200 leading-snug">
                                        <p 
                                        x-text="expanded 
                                            ? board.description 
                                            : (board.files && board.files.length 
                                                ? board.description.split(' ').slice(0, 20).join(' ') + (board.description.split(' ').length > 20 ? '...' : '') 
                                                : board.description.split(' ').slice(0, 200).join(' ') + (board.description.split(' ').length > 200 ? '...' : '')
                                            )"
                                        class="whitespace-pre-line"
                                    ></p>
                                    <button 
                                        x-show="(!expanded && (board.files && board.description.split(' ').length > 20)) || (!expanded && (!board.files || !board.files.length) && board.description.split(' ').length > 200)" 
                                        @click="expanded = true"
                                        class="mt-1 text-pink-500 hover:underline text-xs font-medium"
                                    >
                                        More
                                    </button>
                                </div>
                            </div>
                                                <!-- Desktop/Tablet reactions/comments -->
                            <div class="hidden md:block">                     
                                <div class="hidden md:grid grid-cols-2 grid-rows-4 gap-3 mt-2 w-full p-2 bg-gray-50 dark:bg-gray-800 rounded-xl shadow-inner">
                                    <template x-for="(emoji, mood) in reactionMoods" :key="mood">
                                        <button
                                            @click.prevent="react(board.id, mood); $el.classList.add('animate-bounce'); setTimeout(()=>$el.classList.remove('animate-bounce'), 500)"
                                            x-data="{ showName: false }"
                                            @mouseenter="showName = true" 
                                            @mouseleave="showName = false"
                                            class="w-full relative rounded-lg flex flex-col items-center justify-center transition-all duration-200 hover:scale-105
                                                px-3 py-2 text-sm font-medium"
                                            :class="[
                                                board.user_reacted_mood === mood ? 'ring-2 ring-offset-1 ring-pink-400 shadow' : 'shadow-sm',
                                                mood === 'fire' && 'bg-red-200 text-red-800',
                                                mood === 'love' && 'bg-rose-300 text-rose-900',
                                                mood === 'funny' && 'bg-yellow-200 text-yellow-800',
                                                mood === 'mind-blown' && 'bg-violet-300 text-violet-900',
                                                mood === 'cool' && 'bg-teal-200 text-teal-800',
                                                mood === 'crying' && 'bg-sky-200 text-sky-800',
                                                mood === 'clap' && 'bg-emerald-200 text-emerald-800',
                                                mood === 'flirty' && 'bg-pink-200 text-pink-800'
                                            ]"
                                            style="backdrop-filter: saturate(160%) blur(8px); -webkit-backdrop-filter: saturate(160%) blur(8px);"
                                        >
                                            <span class="capitalize text-xs font-semibold leading-tight" x-text="mood"></span>
                                            <div class="flex items-center gap-1">
                                                <span x-text="emoji" class="text-lg"></span>
                                                <span class="px-1 rounded-full bg-white/50 text-pink-500 font-semibold text-xs" 
                                                    x-text="getReactionCount(board, mood)">
                                                </span>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                                    <!-- Comments -->
                                <div class="mt-3 md:mt-4 hidden md:block">
                                    <div class="flex items-center gap-2 mb-2 bg-gray-50 dark:bg-gray-800 rounded-lg px-2 py-1 border border-gray-100 dark:border-gray-700 shadow-inner">
                                        <input
                                            type="text"
                                            x-model="board.newComment"
                                            placeholder="Type a comment..."
                                            class="flex-1 bg-transparent focus:outline-none text-xs sm:text-sm text-gray-700 dark:text-gray-200 placeholder-gray-400"
                                            @keydown.enter.prevent="postComment(board)"
                                        >
                                        <button
                                            @click.prevent="postComment(board)"
                                            class="text-pink-500 hover:text-pink-600 transition-colors text-xs sm:text-sm font-medium"
                                        >
                                            Post
                                        </button>
                                    </div>
                                    <div class="mt-2 space-y-2 max-h-32 overflow-y-auto pr-1">
                                        <div class="text-xs text-gray-500 flex justify-between">
                                            <span x-text="(board.comment_count ?? 0) + ' comments'"></span>
                                            <a :href="'/boards/' + board.id" class="text-pink-600 hover:underline text-sm font-medium">
                                                → View Board
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Media (Middle on mobile, left on desktop) -->
                        <div class="order-2 md:order-1 md:col-span-3 w-full">
                            <template x-if="board.files?.length">
                                <div class="md:col-span-3">
                                    <div
                                        class="mt-3 w-full mx-auto aspect-[9/12] min-h-[220px] rounded-xl overflow-hidden flex items-center justify-center relative z-0 bg-gray-50 dark:bg-gray-800 shadow-inner"
                                        x-data="{ currentIndex: 0 }"
                                    >
                                        <!-- 🔢 File count -->
                                        <template x-if="board.files.length > 1">
                                            <div class="absolute top-2 right-3 bg-black/60 text-white text-xs px-2 py-0.5 rounded-full z-10">
                                                <span x-text="`${currentIndex + 1} / ${board.files.length}`"></span>
                                            </div>
                                        </template>

                                        <!-- 📸 Media Preview -->
                                        <div class="flex items-center justify-center w-full h-full">
                                            <template x-if="board.files[currentIndex].type === 'image'">
                                                <img
                                                    :src="board.files[currentIndex].path"
                                                    alt="Preview"
                                                    class="max-h-full max-w-full object-contain transition-transform duration-300 group-hover:scale-[1.03] cursor-pointer"
                                                    @click="previewBoardFile = board.files[currentIndex]; showBoardPreviewModal = true"
                                                />
                                            </template>
                                            <template x-if="board.files[currentIndex].type === 'video'">
                                                <video
                                                    :src="board.files[currentIndex].path"
                                                    playsinline
                                                    preload="metadata"
                                                    muted
                                                    autoplay
                                                    loop
                                                    class="max-h-full max-w-full object-contain rounded-xl transition-transform duration-300 group-hover:scale-[1.02] cursor-pointer"
                                                    @click="previewBoardFile = board.files[currentIndex]; showBoardPreviewModal = true"
                                                ></video>
                                            </template>
                                        </div>

                                        <!-- ⬅ Prev Arrow -->
                                        <button
                                            x-show="board.files.length > 1"
                                            @click="if (currentIndex > 0) currentIndex--"
                                            :disabled="currentIndex === 0"
                                            class="absolute left-2 bg-white dark:bg-gray-700 bg-opacity-80 dark:bg-opacity-70 rounded-full p-1.5 shadow hover:bg-opacity-100 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                            style="top: 50%; transform: translateY(-50%);"
                                        >◀</button>

                                        <!-- ➡ Next Arrow -->
                                        <button
                                            x-show="board.files.length > 1"
                                            @click="if (currentIndex < board.files.length - 1) currentIndex++"
                                            :disabled="currentIndex === board.files.length - 1"
                                            class="absolute right-2 bg-white dark:bg-gray-700 bg-opacity-80 dark:bg-opacity-70 rounded-full p-1.5 shadow hover:bg-opacity-100 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                            style="top: 50%; transform: translateY(-50%);"
                                        >▶</button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Reactions & Comments (Bottom on mobile) -->
                        <div class="order-3 md:order-2 md:col-span-2 flex flex-col mt-3 md:mt-0 w-full block md:hidden">
                            <!-- Reactions -->
                            <div class="flex flex-wrap gap-2 sm:grid sm:grid-cols-2 sm:gap-3 mt-2 w-full">
                                <template x-for="(emoji, mood) in reactionMoods" :key="mood">
                                    <button
                                        @click.prevent="react(board.id, mood); $el.classList.add('animate-bounce'); setTimeout(()=>$el.classList.remove('animate-bounce'), 500)"
                                        x-data="{ showName: false }"
                                        @mouseenter="showName = true" 
                                        @mouseleave="showName = false"
                                        class="w-full relative rounded-lg flex flex-col items-center justify-center transition-all duration-200 hover:scale-105
                                            px-3 py-2 text-sm font-medium"
                                        :class="[
                                            board.user_reacted_mood === mood ? 'ring-2 ring-offset-1 ring-pink-400 shadow' : 'shadow-sm',
                                            mood === 'fire' && 'bg-red-200 text-red-800',
                                            mood === 'love' && 'bg-rose-300 text-rose-900',
                                            mood === 'funny' && 'bg-yellow-200 text-yellow-800',
                                            mood === 'mind-blown' && 'bg-violet-300 text-violet-900',
                                            mood === 'cool' && 'bg-teal-200 text-teal-800',
                                            mood === 'crying' && 'bg-sky-200 text-sky-800',
                                            mood === 'clap' && 'bg-emerald-200 text-emerald-800',
                                            mood === 'flirty' && 'bg-pink-200 text-pink-800'
                                        ]"
                                        style="backdrop-filter: saturate(160%) blur(8px); -webkit-backdrop-filter: saturate(160%) blur(8px);"
                                    >
                                        <span class="capitalize text-xs font-semibold leading-tight" x-text="mood"></span>
                                        <div class="flex items-center gap-1">
                                            <span x-text="emoji" class="text-lg"></span>
                                            <span class="px-1 rounded-full bg-white/50 text-pink-500 font-semibold text-xs" 
                                                x-text="getReactionCount(board, mood)">
                                            </span>
                                        </div>
                                    </button>
                                </template>
                            </div>
                                <!-- Comments -->
                            <div class="mt-3 md:mt-4 hidden md:block">
                                <div class="flex items-center gap-2 mb-2 bg-gray-50 dark:bg-gray-800 rounded-lg px-2 py-1 border border-gray-100 dark:border-gray-700 shadow-inner">
                                    <input
                                        type="text"
                                        x-model="board.newComment"
                                        placeholder="Type a comment..."
                                        class="flex-1 bg-transparent focus:outline-none text-xs sm:text-sm text-gray-700 dark:text-gray-200 placeholder-gray-400"
                                        @keydown.enter.prevent="postComment(board)"
                                    >
                                    <button
                                        @click.prevent="postComment(board)"
                                        class="text-pink-500 hover:text-pink-600 transition-colors text-xs sm:text-sm font-medium"
                                    >
                                        Post
                                    </button>
                                </div>
                                <div class="mt-2 space-y-2 max-h-32 overflow-y-auto pr-1">
                                    <div class="text-xs text-gray-500 flex justify-between">
                                        <span x-text="(board.comment_count ?? 0) + ' comments'"></span>
                                        <a :href="'/boards/' + board.id" class="text-pink-600 hover:underline text-sm font-medium">
                                            → View Board
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- 💤 No Boards Yet -->
            <div class="text-gray-400 italic text-sm" x-show="filteredBoards.length === 0 && user">
                This user hasn’t added any favourites yet.
            </div>
        </div>

        {{-- 🔽 Load More --}}
        <div class="mt-6 flex justify-center" x-show="!allLoaded && filteredBoards.length">
            <button @click="loadFavoritedBoards" class="bg-gray-800 text-white px-6 py-2 rounded-full hover:bg-gray-700">
                Load More
            </button>
        </div>
    </template>

    <template x-if="activeTab === 'favTeasers'">
        <div> <!-- Render Favourite Teasers --> </div>
    </template>

    <template x-if="activeTab === 'likedMoodboards'">
        <div> <!-- Render Liked Moodboards --> </div>
    </template>

    <template x-if="activeTab === 'likedTeasers'">
        <div> <!-- Render Liked Teasers --> </div>
    </template>

    <template x-if="boards.length === 0 && !allLoaded">
        <div class="text-center py-6 text-gray-400">Loading...</div>
    </template>

        
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

    Alpine.data('vibeProfile', (username, viewerId, initialIsFollowing, initialFollowerCount, initialFollowingCount, rawUser, rawBoards) => ({
        followerCount: initialFollowerCount ?? 0,
        followingCount: initialFollowingCount ?? 0,
        rawUser,
        rawBoards,
        user: null,
        boards: [],
        page: 1,
        allLoaded: false,
        viewerId,
        isFollowing: initialIsFollowing ?? false, // ✅ load accurate follow state
        followerCount: initialFollowerCount ?? 0, // ✅ load accurate follower count
        followClicks: 0,
        clickResetTimer: null,
        activeTab: 'favBoards',
        pageSize: 10,

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

        _csrf() {
            return document.querySelector('meta[name=csrf-token]').getAttribute('content');
        },

        loadTabContent() {
            this.page = 1;
            this.allLoaded = false;
            this.boards = [];

            switch (this.activeTab) {
                case 'favBoards':
                    this.loadFavoritedBoards();
                    break;
                case 'favTeasers':
                    this.loadFavoritedTeasers();
                    break;
                case 'savedMoodboards':
                    this.loadSavedBoards(); // ✅ updated to match your new method
                    break;
                case 'likedTeasers':
                    this.loadLikedTeasers();
                    break;
            }
        },

        init() {
            // Enhance rawBoards with reacting + newComment flags
            this.rawBoards = this.rawBoards.map(board => ({
                ...board,
                reacting: false,
                newComment: '', // Optional: for comment input binding
            }));

            this.boards = this.rawBoards;
            this.loadTabContent();
            this.$watch('activeTab', () => this.loadTabContent());
        },

        get filteredBoards() {
            return this.boards;
        },

        hydrateBoards() {
            const slice = this.rawBoards.slice((this.page - 1) * this.pageSize, this.page * this.pageSize);
            console.log("🔍 Boards slice:", slice.map(b => b.id));

            if (this.page === 1) {
                this.user = this.rawUser;
                this.isFollowing = this.rawUser?.is_following ?? false;
                this.followerCount = this.rawUser?.follower_count ?? 0;
                this.followingCount = this.rawUser?.following_count ?? 0;

                console.log("✅ Hydrated user state");
            }

            if (slice.length) {
                slice.forEach(board => {
                    const exists = this.boards.some(b => b.id === board.id);
                    if (!exists) {
                        let files = [];

                        let imgs = board.images || board.image;
                        if (typeof imgs === "string") {
                            try { imgs = JSON.parse(imgs); } catch {}
                        }

                        if (Array.isArray(imgs)) {
                            files.push(...imgs.map(path => ({
                                path: path.startsWith('http') ? path : `/storage/${path.replace(/^\/?storage\//, '')}`,
                                type: "image"
                            })));
                        } else if (typeof imgs === "string") {
                            files.push({
                                path: imgs.startsWith('http') ? imgs : `/storage/${imgs.replace(/^\/?storage\//, '')}`,
                                type: "image"
                            });
                        }

                        if (board.video) {
                            files.push({
                                path: board.video.startsWith('http') ? board.video : `/storage/${board.video.replace(/^\/?storage\//, '')}`,
                                type: "video"
                            });
                        }

                        const seen = new Set();
                        files = files.filter(f => {
                            if (seen.has(f.path)) return false;
                            seen.add(f.path);
                            return true;
                        });

                        board.files = files;
                        board.newComment = '';
                        board.comment_count = board.comment_count ?? 0;

                        this.boards.push(board);
                        this.$nextTick(() => this.renderMediaPreview(board));
                    }
                });

                console.log("📥 Boards after hydration:", this.boards.map(b => b.id));
                this.page++;
            } else {
                console.log("🏁 No more boards to load. Marking allLoaded = true");
                this.allLoaded = true;
            }
        },

        async loadFavoritedBoards() {
            if (this.allLoaded) {
                console.log("✅ All favorited boards already loaded. Skipping fetch.");
                return;
            }

            console.log("🚀 loadFavoritedBoards triggered");

            try {
                const res = await fetch(`/api/favorited-boards?username=${this.rawUser?.username}`, {
                    headers: { 'X-CSRF-TOKEN': this._csrf() }
                });
                const data = await res.json();
                if (!res.ok) throw data;

                this.rawUser = data.user;
                this.rawBoards = data.boards || [];

                this.hydrateBoards();
            } catch (err) {
                console.error("❌ Failed to hydrate favorited boards:", err);
            }
        },

        async loadSavedBoards() {
            if (this.allLoaded) {
                console.log("✅ All saved boards already loaded. Skipping fetch.");
                return;
            }

            console.log("🚀 loadSavedBoards triggered");

            try {
                const res = await fetch(`/api/saved-boards?username=${this.rawUser?.username}`, {
                    headers: { 'X-CSRF-TOKEN': this._csrf() }
                });
                const data = await res.json();
                if (!res.ok) throw data;

                this.rawUser = data.user;
                this.rawBoards = data.boards || [];

                this.hydrateBoards();
            } catch (err) {
                console.error("❌ Failed to hydrate saved boards:", err);
            }
        },

        async toggleFollow() {
            if (this.followClicks >= 5) {
                console.warn("🚫 Follow click limit reached. Ignoring request.");
                return;
            }

            this.followClicks++;
            console.log(`🔁 Follow click #${this.followClicks}`);

            // Reset counter after 1 minute
            if (!this.clickResetTimer) {
                console.log("⏳ Starting click reset timer (60s)");
                this.clickResetTimer = setTimeout(() => {
                    this.followClicks = 0;
                    this.clickResetTimer = null;
                    console.log("✅ Click counter reset");
                }, 60000);
            }

            try {
                console.log(`📡 Sending follow toggle request for user ID: ${this.user.id}`);
                const res = await fetch(`/follow/${this.user.id}`, {
                    method: 'POST',
                    headers: this._headers()
                });

                const data = await res.json();
                console.log("📥 Response received:", data);

                if (!res.ok) {
                    console.error("❌ Server responded with error status", data);
                    throw new Error("Failed to follow/unfollow");
                }

                // ⏫ Flip the state and update count
                this.isFollowing = data.isFollowing;
                this.followerCount = data.followerCount;

                console.log(`🎯 Follow state updated: isFollowing = ${this.isFollowing}`);
                console.log(`📊 New follower count: ${this.followerCount}`);

                // ✅ Optional toast
                this.showToast(this.isFollowing ? "Now following 💕" : "Unfollowed 😔");
            } catch (err) {
                console.error("🔥 Follow toggle failed", err);
                this.showToast("Error. Please try again later.", 'error');
            }
        },

        openEditModal() {
            this.username = this.user.username;
            Alpine.store('modal').showEditModal = true;
        },

        get filteredBoards() {
            return this.boards;
        },

        renderMediaPreview(board, attempts = 0) {
            const container = document.getElementById(`media-preview-${board.id}`);
            if (!container) {
                if (attempts < 5) {
                    setTimeout(() => this.renderMediaPreview(board, attempts + 1), 50);
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
            if (!board) return this.showToast("Board not found", 'error');
            if (board.user_reacted_mood === mood) {
                this.showToast("You already picked this mood 💅", 'error');
                return;
            }

            board.reacting = true;
            this.showLoadingToast("Reacting...");

            setTimeout(() => {
                fetch('/reaction', {
                    method: 'POST',
                    headers: this._headers(),
                    body: JSON.stringify({ mood_board_id: boardId, mood }),
                })
                .then(res => res.ok ? res.json() : Promise.reject())
                .then(data => {
                    const newMood = data.mood;
                    const prevMood = data.previous;

                    if (prevMood && prevMood !== newMood) {
                        board[prevMood + '_count'] = Math.max(0, (board[prevMood + '_count'] || 0) - 1);
                    }
                    board[newMood + '_count'] = (board[newMood + '_count'] || 0) + 1;
                    board.user_reacted_mood = newMood;

                    this.showToast("Mood updated! 💖");
                })
                .catch(() => this.showToast("Failed to react 💔", 'error'))
                .finally(() => {
                    board.reacting = false;
                });
            }, 1000);
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

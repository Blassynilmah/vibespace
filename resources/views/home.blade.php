@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto flex gap-8 px-2 sm:px-4 pb-0" x-data="vibeFeed" x-init="init">
    {{-- Left Sidebar --}}
    <div class="hidden lg:block w-1/5">
        <div class="sticky top-24">
            <h3 class="text-xl font-semibold mb-4">Mood Filters</h3>
                <div class="mb-4">
                    <div 
                        style="
                            max-height: 170px;           /* set your desired height */
                            overflow-y: auto;            /* enable vertical scroll if needed */
                            -ms-overflow-style: none;    /* hide scrollbar in IE/Edge */
                            scrollbar-width: none;       /* hide scrollbar in Firefox */
                            display: flex;
                            flex-direction: column;
                        "
                    >
                        <template x-for="(emoji, mood) in moods" :key="mood">
                            <button
                                @click="toggleMood(mood)"
                                class="ml-0 mb-2 px-3 py-1.5 rounded-full text-sm font-medium transition text-left"
                                :class="selectedMoods.includes(mood) 
                                    ? 'bg-pink-500 text-white' 
                                    : 'bg-gray-100 text-gray-800 hover:bg-gray-200'"
                                x-text="emoji + ' ' + mood.charAt(0).toUpperCase() + mood.slice(1)">
                            </button>
                        </template>
                    </div>
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

        {{-- Mobile Top Nav (Visible on small screens only) --}}
        <div class="lg:hidden sticky top-0 z-[999] bg-gradient-to-r from-pink-500 to-purple-600 shadow-md border-t border-white/20 border-b">
            <div class="flex justify-around px-3 py-2 text-white text-sm">
                <!-- Home -->
                <a href="{{ route('home') }}"
                class="flex flex-col items-center text-yellow-300 font-semibold transition duration-300 ease-in-out">
                    🏠
                    <span class="text-[11px] mt-1 tracking-wide">Home</span>
                </a>

                <!-- Messages -->
                <a href="/messages"
                class="flex flex-col items-center transition duration-300 ease-in-out hover:text-yellow-300">
                    💌
                    <span class="text-[11px] mt-1 tracking-wide">Messages</span>
                </a>

                <!-- Me -->
                <a href="{{ route('boards.me') }}"
                class="flex flex-col items-center transition duration-300 ease-in-out hover:text-yellow-300">
                    💫
                    <span class="text-[11px] mt-1 tracking-wide">Me</span>
                </a>

                <!-- Alerts -->
                <a href="/notifications"
                class="flex flex-col items-center transition duration-300 ease-in-out hover:text-yellow-300">
                    🔔
                    <span class="text-[11px] mt-1 tracking-wide">Alerts</span>
                </a>

                <!-- Settings -->
                <a href="/settings"
                class="flex flex-col items-center transition duration-300 ease-in-out hover:text-yellow-300">
                    ⚙️
                    <span class="text-[11px] mt-1 tracking-wide">Settings</span>
                </a>
            </div>
        </div>

        <div class="flex items-center justify-between mb-6 flex-wrap gap-2">
            <!-- Title -->
            <h2
            class="font-bold tracking-tight text-gray-800"
            style="
                font-size: clamp(1.1rem, 2.5vw + 0.5rem, 2rem);
            "
            >
            🔥 Trending MoodBoards
            </h2>


            <!-- Action Buttons -->
            <div class="ml-auto flex flex-row items-center gap-2 flex-wrap sm:flex-nowrap">
            
                <!-- Create Button -->
                <a href="{{ route('boards.create') }}"
                class="flex items-center justify-center h-9 w-9 rounded-full bg-white text-pink-600 shadow transition duration-300 ease-in-out">
                <span class="text-base">+</span>
                </a>

                <!-- Filter Button -->
                <button @click="showMobileFilters = true"
                        class="flex items-center justify-center h-9 w-9 rounded-full bg-white text-pink-600 shadow transition duration-300 ease-in-out lg:hidden">
                <span class="text-base">🧰</span>
                </button>

                <!-- Search Button -->
                <button @click="showSearch = true"
                        class="flex items-center justify-center h-9 w-9 rounded-full bg-white text-pink-600 shadow transition duration-300 ease-in-out">
                <span class="text-base">🔍</span>
                </button>
            </div>
        </div>

        <div class="flex flex-col gap-6 md:gap-8 z-0 mt-3">
            @foreach($items as $item)
                @if($item->type === 'board')
                    <template x-for="board in filteredBoards" :key="board.id + '-' + board.created_at">
                        <div class="relative bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-lg transition-all duration-300 group overflow-hidden" style="transition: box-shadow .25s ease, transform .18s ease;">
                            <div 
                            class="relative flex flex-col items-start p-3 sm:p-4 lg:p-6"
                            :class="board.files?.length ? 'md:grid md:grid-cols-5 md:gap-6' : 'md:flex md:flex-col'"
                            >

                            <!-- 💾 Save Button -->
                            <div class="absolute top-3 right-3 z-10">
                                <button
                                    @click.prevent="toggleSaveById(board.id)"
                                    :disabled="board.saving"
                                    :class="[
                                        'px-3 py-1 rounded-full text-xs font-semibold transition-all',
                                        board.is_saved
                                            ? 'bg-green-100 text-green-700 hover:bg-green-200'
                                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
                                        board.saving ? 'opacity-50 cursor-not-allowed' : ''
                                    ]"
                                >
                                    <span x-text="board.is_saved ? '✔️ Saved' : '💾 Save'"></span>
                                </button>
                            </div>
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
                                                <template x-if="board.user">
                                                    <a 
                                                        :href="`/space/${board.user.username}-${board.user.id}`" 
                                                        class="hover:underline font-medium text-blue-600 text-xs sm:text-sm"
                                                        :title="`View ${board.user.username}'s profile`"
                                                        :aria-label="`View profile of ${board.user.username}`"
                                                        x-text="'@' + board.user.username">
                                                    </a>
                                                </template>


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
                                                x-text="board.expanded 
                                                    ? (board.description || '') 
                                                    : (board.files && board.files.length 
                                                        ? (board.description ? board.description.split(' ').slice(0, 20).join(' ') + (board.description.split(' ').length > 20 ? '...' : '') : '') 
                                                        : (board.description ? board.description.split(' ').slice(0, 200).join(' ') + (board.description.split(' ').length > 200 ? '...' : '') : '')
                                                    )"
                                                class="whitespace-pre-line"
                                            ></p>
                                            <button 
                                                x-show="(!board.expanded && (board.files && board.description && board.description.split(' ').length > 20))
                                                    || (!board.expanded && (!board.files || !board.files.length) && board.description && board.description.split(' ').length > 200)"
                                                @click="board.expanded = true"
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
                                            <div class="text-xs text-gray-500 flex justify-between">
                                                <span x-text="(board.comment_count ?? 0) + ' comments'"></span>
                                                <a :href="'/boards/' + board.id" class="text-pink-600 hover:underline text-sm font-medium">
                                                    → View Board
                                                </a>
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

                                <!-- Reactions & Comments (Bottom on mobile, right on desktop) -->
                                <div class="order-3 md:order-2 md:col-span-2 flex flex-col mt-3 md:mt-0 w-full block md:hidden">
                                    <!-- Reactions -->
                                    <div class="flex flex-wrap gap-2 sm:grid sm:grid-cols-2 sm:gap-3 mt-2 w-full">
                                        <template x-for="(emoji, mood) in reactionMoods" :key="mood">
                                            <button
                                                @click.prevent="react(board.id, mood); $el.classList.add('animate-bounce'); setTimeout(()=>$el.classList.remove('animate-bounce'), 500)"
                                                x-data="{ showName: false }"
                                                @mouseenter="showName = true" 
                                                @mouseleave="showName = false"
                                                class="flex flex-col items-center justify-center transition-all duration-200 hover:scale-105
                                                    px-1 sm:px-2 py-0.5 sm:py-1 text-xs sm:text-sm font-medium rounded-lg"
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
                                                <!-- Mood name (desktop only) -->
                                                <span class="hidden sm:block capitalize text-[0.65rem] sm:text-[0.7rem] font-semibold leading-tight" x-text="mood"></span>
                                                <!-- Emoji + Counter -->
                                                <div class="flex items-center gap-1">
                                                    <span x-text="emoji" class="text-lg sm:text-xl"></span>
                                                    <span class="px-1 rounded-full bg-white/50 text-pink-500 font-semibold text-[0.6rem]" 
                                                        x-text="getReactionCount(board, mood)">
                                                    </span>
                                                </div>
                                                <!-- Tooltip for mobile -->
                                                <div 
                                                    x-show="showName && window.innerWidth < 640" 
                                                    class="absolute -top-6 left-1/2 -translate-x-1/2 bg-black text-white text-[0.6rem] rounded px-2 py-0.5 shadow opacity-90">
                                                    <span x-text="mood"></span>
                                                </div>
                                            </button>
                                        </template>
                                    </div>
                                    <!-- Comments -->
                                    <div class="mt-3 md:mt-4">
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
                @elseif($item->type === 'teaser')
                    <template>
                        <div class="overflow-y-auto snap-y snap-mandatory h-screen scroll-smooth">
                            <!-- Loading State -->
                            <template x-if="loadingTeasers">
                                <div class="text-center text-gray-400 py-8">Loading your teasers...</div>
                            </template>

                            <!-- Empty State -->
                            <template x-if="!loadingTeasers && (!teasers || !teasers.length)">
                                <div class="text-center text-gray-400 py-8">You haven’t added any teasers yet.</div>
                            </template>

                            <!-- Teaser Tiles -->
                            <template x-for="teaser in teasers" :key="teaser.id">
                                <div
                                    class="snap-center h-screen flex flex-col mb-20 mt-40 lg:flex-row bg-white border-2 border-blue-400 shadow-md hover:shadow-lg transition-all duration-300 overflow-hidden rounded-2xl h-screen"
                                    :class="{
                                        'h-[90vh]': window.innerWidth < 768,
                                        'md:h-[90vh]': window.innerWidth >= 768 && window.innerWidth < 1024,
                                        'lg:h-[80vh]': window.innerWidth >= 1024
                                    }"
                                    >
                                    <!-- Video Section -->
                                    <div
                                    class="relative w-full h-full lg:w-1/2"
                                    :class="{
                                        'h-[60vh]': window.innerWidth < 768,
                                        'md:h-[70vh]': window.innerWidth >= 768 && window.innerWidth < 1024,
                                        'lg:h-full': window.innerWidth >= 1024
                                    }"
                                    >
                                    <video
                                        :src="'/storage/' + teaser.video"
                                        :autoplay="currentPlayingTeaserId === teaser.id"
                                        :muted="false"
                                        playsinline
                                        loop
                                        tabindex="0"
                                    class="w-full h-full object-cover bg-black rounded-2xl"
                                        x-ref="'videoEl' + teaser.id"
                                        @play="handlePlay(teaser.id)"
                                        @pause="handlePause(teaser.id)"
                                        @click="togglePlay($refs['videoEl' + teaser.id])"
                                        @mousedown="startFastForward($refs['videoEl' + teaser.id])"
                                        @mouseup="stopFastForward($refs['videoEl' + teaser.id])"
                                        @touchstart="startFastForward($refs['videoEl' + teaser.id])"
                                        @touchend="stopFastForward($refs['videoEl' + teaser.id])"
                                    ></video>

                                    <!-- Mobile Overlay -->
                                    <div class="absolute bottom-0 left-0 w-full bg-gradient-to-t from-black/80 to-transparent text-white p-4 md:hidden rounded-b-2xl">
                                        <div class="text-sm font-semibold mb-1">@<span x-text="teaser.username"></span></div>
                                        <div class="text-xs text-pink-300 mb-1" x-text="teaser.hashtags"></div>
                                        <div class="text-xs mb-1" x-text="teaser.description"></div>
                                        <div class="flex items-center gap-2 text-xs text-gray-200">
                                        <span x-text="timeSince(teaser.created_at)"></span>
                                        <span>•</span>
                                        <span x-text="getRemainingTime(teaser.expires_on)"></span>
                                        </div>
                                    </div>
                                    </div>

                                    <!-- Info Section (Desktop) -->
                                    <div class="hidden lg:flex flex-1 flex-col justify-between p-8">
                                    <div>
                                        <div class="flex items-center gap-2 mb-2">
                                        <span class="font-semibold text-pink-600">@<span x-text="teaser.username"></span></span>
                                        <span class="text-xs text-gray-400" x-text="timeSince(teaser.created_at)"></span>
                                        </div>
                                        <div class="mb-2">
                                        <span class="inline-block bg-pink-100 text-pink-700 rounded-full px-2 py-0.5 text-xs font-medium" x-text="teaser.hashtags"></span>
                                        </div>
                                        <div class="text-sm text-gray-700 mb-2" x-text="teaser.description"></div>
                                    </div>
                                    <div class="flex flex-wrap gap-4 text-xs text-gray-500 mt-2">
                                        <div>
                                        <span class="font-semibold">Time Remaining:</span>
                                        <span x-text="getRemainingTime(teaser.expires_on)"></span>
                                        </div>
                                        <div>
                                        <span class="font-semibold">Duration:</span>
                                        <span x-text="teaser.expires_after ? teaser.expires_after + ' hrs' : '—'"></span>
                                        </div>
                                        <div>
                                        <span class="font-semibold">Created:</span>
                                        <span x-text="new Date(teaser.created_at).toLocaleString()"></span>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                @endif
            @endforeach
        </div>

        {{-- 🔽 Load More --}}
        <div class="mt-6 flex justify-center" x-show="!allLoaded && filteredBoards.length">
            <button
                @click="loadBoards"
                :disabled="loading"
                class="bg-gray-800 text-white px-6 py-2 rounded-full hover:bg-gray-700 disabled:opacity-50"
            >
                <span x-show="!loading">Load More</span>
                <span x-show="loading">Loading...</span>
            </button>
        </div>

        <div class="mt-6 flex justify-center text-gray-500" x-show="allLoaded && filteredBoards.length">
            <span>🎉 You’ve reached the end of the feed</span>
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

                <div class="mb-4">
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Mood</h4>
                    <div 
                        class="flex flex-wrap gap-2 max-w-xs overflow-x-auto scrollbar-hide"
                    >
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

        mediaTypes: {
            image: "🖼️ Image",
            video: "🎥 Video",
            text: "📝 Text"
        },

        init() {
            console.log("Alpine vibeFeed initialized");
            this.page = 1;
            this.boards = [];
            this.allLoaded = false;
            this.loadBoards();
        },

        async loadBoards() {
        if (this.loading || this.allLoaded) return;
        this.loading = true;

        const perPage = this.page === 1 ? 20 : 10;
        const url = `/api/boards/latest?page=${this.page}&per_page=${perPage}`;

        try {
            const res = await fetch(url, {
            credentials: 'include',
            headers: { 'Accept': 'application/json' }
            });

            const contentType = res.headers.get('content-type') || '';
            if (!res.ok) {
            const text = await res.text();
            console.error(`HTTP ${res.status} on ${url}`, text.slice(0, 500));
            throw new Error(`Request failed: ${res.status}`);
            }
            if (!contentType.includes('application/json')) {
            const text = await res.text();
            console.error('Expected JSON, got HTML/text:', text.slice(0, 500));
            throw new Error('Non-JSON response received');
            }

            const json = await res.json();

            if (!json.data || json.data.length === 0) {
            this.allLoaded = true;
            return;
            }

            const newBoards = json.data.map(board => {
            let files = [];
            let imgs = board.images ?? board.image;

            if (typeof imgs === 'string') {
                try { imgs = JSON.parse(imgs); } catch {}
            }

            if (Array.isArray(imgs)) {
                files.push(...imgs.map(path => ({
                path: path.startsWith('http') ? path : `/storage/${path.replace(/^\/?storage\//, '')}`,
                type: 'image'
                })));
            } else if (typeof imgs === 'string' && imgs) {
                files.push({
                path: imgs.startsWith('http') ? imgs : `/storage/${imgs.replace(/^\/?storage\//, '')}`,
                type: 'image'
                });
            }

            if (board.video) {
                const v = board.video;
                files.push({
                path: v.startsWith('http') ? v : `/storage/${v.replace(/^\/?storage\//, '')}`,
                type: 'video'
                });
            }

            const seen = new Set();
            files = files.filter(f => {
                if (seen.has(f.path)) return false;
                seen.add(f.path);
                return true;
            });

            return {
                ...board,
                files,
                newComment: '',
                comment_count: board.comment_count ?? 0,
                is_saved: !!board.is_saved,
                expanded: false,
                saving: false
            };
            });

            console.group(`Loaded ${newBoards.length} boards (page ${this.page})`);
            newBoards.forEach(b => {
            console.log(`Board #${b.id}${b.title ? ` (${b.title})` : ''} → is_saved: ${b.is_saved}`);
            });
            console.groupEnd();

            this.boards.push(...newBoards);
            this.page += 1;

            // Trust the paginator
            this.allLoaded = !json.next_page_url;

        } catch (error) {
            console.error('Failed to load boards', error);
        } finally {
            this.loading = false;
        }
        },

        toggleSaveById(boardId) {
            // Find the board from existing state
            const board =
                this.boards.find(b => b.id === boardId) ||
                (this.filteredBoards && Array.isArray(this.filteredBoards)
                    ? this.filteredBoards.find(b => b.id === boardId)
                    : null);

            if (!board) {
                console.warn(`Board not found for toggleSave:`, boardId);
                return;
            }

            board.saving = true;
            console.log(`🔄 Toggling save state for board ${board.id}...`);

            fetch('/moodboards/toggle-save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ mood_board_id: board.id })
            })
            .then(response => {
                console.log(`📡 Received response for board ${board.id}:`, response);
                if (!response.ok) {
                    throw new Error(`❌ Network response was not ok. Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log(`✅ Save toggle successful for board ${board.id}:`, data);
                board.is_saved = data.is_saved;
                this.showToast(data.is_saved ? '💾 Saved to your vibe vault!' : '❌ Removed from saved');
            })
            .catch(error => {
                console.error(`🚨 Error toggling save for board ${board.id}:`, error);
                this.showToast('⚠️ Something went wrong while saving', 'error');
            })
            .finally(() => {
                board.saving = false;
                console.log(`🧹 Save toggle complete for board ${board.id}`);
            });
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

        moodKey(m) {
        return m.replace(/-/g, '_'); // 'mind-blown' -> 'mind_blown'
        },

        getReactionCount(board, mood) {
        return board[this.moodKey(mood) + '_count'] ?? 0;
        },

        react(boardId, mood) {
        const board = this.boards.find(b => b.id === boardId);
        if (!board) { this.showToast("Board not found", 'error'); return; }
        if (board.user_reacted_mood === mood) { this.showToast("You already picked this mood 💅", 'error'); return; }

        this.showLoadingToast("Reacting...");

        setTimeout(() => {
            fetch('/reaction', {
            method: 'POST',
            headers: this._headers(), // must include Content-Type: application/json + CSRF
            body: JSON.stringify({ mood_board_id: boardId, mood }),
            })
            .then(async res => res.ok ? res.json() : Promise.reject(await res.json()))
            .then(data => {
            const newMood = data.mood;
            const prevMood = data.previous;

            if (prevMood && prevMood !== newMood) {
                const kPrev = this.moodKey(prevMood) + '_count';
                board[kPrev] = Math.max(0, (board[kPrev] || 0) - 1);
            }

            const kNew = this.moodKey(newMood) + '_count';
            board[kNew] = (board[kNew] || 0) + 1;

            board.user_reacted_mood = newMood; // do NOT touch latest_mood

            this.showToast("Mood updated! 💖");
            })
            .catch(err => this.showToast(err?.error || "Failed to react 💔", 'error'));
        }, 1000);
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



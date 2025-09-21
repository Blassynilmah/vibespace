@extends('layouts.app')

@section('content')
<div x-data="meBoards()">

<div
  :class="(showRenameModal || showDeleteModal || showCopyModal || showMoveModal || showDeleteFilesModal || uploadProgressModal || showCreateListModal) ? 'filter blur-sm pointer-events-none select-none' : ''"
>
<div class="max-w-7xl mx-auto flex gap-8 px-2 sm:px-4 pb-8 pt-4">     
     
    <!-- 📁 Left Sidebar -->
    <div class="hidden lg:block w-1/5">
        <template x-if="activeTab === 'moodboards'">
            <div class="hidden lg:block">
                <div class="sticky top-24 mt-24">

                    <!-- 😎 Mood Filters -->
                    <h3 class="text-xl font-semibold mb-4">Mood Filters</h3>
                    <div class="mb-4">
                        <div 
                            style="
                                max-height: 170px;           /* match homepage height */
                                overflow-y: auto;            /* vertical scroll */
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

                    <!-- 📺 Media Type -->
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
        </template>

        <style>
            /* Hide scrollbars for WebKit browsers too */
            [style*="overflow-y: auto"]::-webkit-scrollbar {
                display: none;
            }
        </style>
        <template x-if="activeTab === 'files'">
            <div class="display-none border border-gray-200 rounded-xl overflow-hidden flex flex-col h-full">

                <!-- 📌 Sticky New List Button -->
                <div class="sticky top-1 z-10 bg-white border-b border-gray-200 p-3">
                    <button @click="showCreateListModal = true"
                        class="w-full px-3 py-2 rounded bg-pink-500 text-white text-xs font-semibold hover:bg-pink-600 transition">
                        ➕ New List
                    </button>
                </div>

                <!-- 📂 Scrollable File Lists -->
                <div class="overflow-y-auto px-3 pt-2 pb-6 space-y-3" style="max-height: calc(100vh - 11rem)">
                    
                    <!-- All Media -->
                    <div 
                        @click="activeList = 'all'; activeListId = 'all'; loadUserFiles(true)"
                        class="p-3 border border-gray-300 rounded-md cursor-pointer group hover:border-pink-500 transition"
                        :class="{ 'border-pink-500 ring-1 ring-pink-300': activeList === 'all' }"
                    >
                        <h4 class="text-sm font-semibold text-gray-700 mb-1 group-hover:text-pink-600 transition">
                            All Media
                        </h4>
                        <div class="flex items-center gap-3 text-xs text-gray-600">
                            <span>🖼️ <span x-text="imageCount"></span></span>
                            <span>🎬 <span x-text="videoCount"></span></span>
                        </div>
                        <hr class="mt-3 border-t border-gray-200" />
                    </div>

                    <!-- User-Created Lists -->
                    <template x-for="list in fileLists" :key="list.id">
                        <div
                            @click="activeList = list.id; activeListId = list.id; loadUserFiles(true)"
                            class="relative p-3 border border-gray-300 rounded-md cursor-pointer group hover:border-pink-500 transition"
                            :class="{ 'border-pink-500 ring-1 ring-pink-300': activeList === list.id }"
                        >
                            <!-- 🔤 List Name -->
                            <h4 class="text-sm font-semibold text-gray-700 mb-1 group-hover:text-pink-600 transition" x-text="list.name"></h4>

                            <!-- 📊 Counts -->
                            <div class="flex items-center gap-3 text-xs text-gray-600">
                                <span>🖼️ <span x-text="list.imageCount"></span></span>
                                <span>🎬 <span x-text="list.videoCount"></span></span>
                            </div>

                            <hr class="mt-3 border-t border-gray-200" />

                            <!-- ⋯ Context Menu Trigger (exclude 'all') -->
                            <template x-if="list.id !== 'all'">
                                <button
                                    @click.stop="openListMenu(list.id)"
                                    class="absolute top-2 right-2 text-gray-400 hover:text-pink-500 transition"
                                    title="List actions"
                                >⋯</button>
                            </template>

                            <!-- ⚙️ List Actions Menu -->
                            <template x-if="activeMenu === list.id">
                                <div
                                    class="absolute right-2 top-8 bg-white border border-gray-200 rounded shadow-md z-50 text-sm w-40"
                                    @click.outside="activeMenu = null"
                                >
                                    <button @click="startEditingList(list)" class="block w-full px-4 py-2 text-left hover:bg-pink-50">✏️ Edit Name</button>
                                    <button @click="listToDelete = list; showDeleteModal = true; activeMenu = null" class="block w-full px-4 py-2 text-left text-red-600 hover:bg-red-50">🗑️ Delete List</button>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    {{-- 🎛️ Tabbed Content --}}
    <div class="w-full lg:w-4/5 flex flex-col gap-0">

    <div class="lg:hidden sticky top-0 z-[399] bg-gradient-to-r from-pink-500 to-purple-600 shadow-md border-b border-white/20">
        <div class="flex justify-around px-3 py-2 text-white text-sm">
            <!-- Home -->
            <a href="{{ route('home') }}"
            class="flex flex-col items-center transition duration-300 ease-in-out hover:text-yellow-300">
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
            class="flex flex-col items-center text-yellow-300 font-semibold transition duration-300 ease-in-out">
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
        
    {{-- 🧭 Tab Navigation --}}
    <div class="flex items-center sticky top-[50px] z-[399] bg-white justify-between px-2 sm:px-0">
        <div class="flex gap-2 sm:gap-4">
            <button
            @click="activeTab = 'moodboards'"
            :class="activeTab === 'moodboards'
                ? 'bg-white text-pink-600 font-semibold shadow rounded-full hover:bg-pink-100'
                : 'text-gray-400'"
            class="px-4 py-2 text-xs sm:text-sm md:text-base transition">
            Moodboards
            </button>

            <button
            @click="activeTab = 'teasers'"
            :class="activeTab === 'teasers'
                ? 'bg-white text-pink-600 font-semibold shadow rounded-full hover:bg-pink-100'
                : 'text-gray-400'"
            class="px-4 py-2 text-xs sm:text-sm md:text-base transition">
            Teasers
            </button>

            <button
            @click="activeTab = 'files'"
            :class="activeTab === 'files'
                ? 'bg-white text-pink-600 font-semibold shadow rounded-full hover:bg-pink-100'
                : 'text-gray-400'"
            class="px-4 py-2 text-xs sm:text-sm md:text-base transition">
            Files
            </button>
        </div>
    </div>

        {{-- 🎨 Moodboards Feed --}}
        <template x-if="activeTab === 'moodboards'">
            <div class="flex flex-col gap-6 md:gap-8 z-0 mt-3" id="moodboards-scroll-container" style="height:80vh; overflow-y:auto;">
                <div class="ml-auto relative flex flex-row items-center gap-2 flex-wrap sm:flex-nowrap">
                    <a href="{{ route('boards.create') }}"
                            class="group relative h-7 rounded-full bg-white text-pink-600 overflow-hidden shadow transition-[width,opacity] duration-700 ease-in-out w-7 sm:hover:w-44 whitespace-nowrap">
                        <span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-lg sm:group-hover:opacity-0">+</span>
                        <span class="pl-10 pr-4 opacity-0 group-hover:opacity-100 text-sm">New Moodboard</span>
                    </a>

                    <button @click="showMobileFilters = true"
                        class="group relative h-7 rounded-full bg-white text-pink-600 overflow-hidden shadow transition-[width,opacity] duration-700 ease-in-out w-7 sm:hover:w-44 whitespace-nowrap">
                        <span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-lg sm:group-hover:opacity-0">🧰</span>
                        <span class="pl-10 pr-4 opacity-0 group-hover:opacity-100 text-sm">Filters</span>
                    </button>
                </div>
                <template x-for="board in filteredBoards" :key="board.id + '-' + board.created_at">
                    <div class="relative bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-lg transition-all duration-300 group overflow-hidden" style="transition: box-shadow .25s ease, transform .18s ease;" x-data="{ expanded: false }">
                        <button
                            @click.prevent="toggleFavorite(board.id)"
                            class="absolute top-3 right-3 z-20 transition bg-white rounded-full p-1 shadow-md"
                            :class="{
                                'text-gray-300 hover:text-gray-400': !board.is_favorited,
                                'text-pink-600 hover:text-pink-700': board.is_favorited
                            }"
                            title="Add to your favorites"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"/>
                            </svg>
                        </button>
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
                                                :href="`/space/${board.user.username}-${board.user.id}`" 
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
                                            x-text="board.expanded 
                                                ? (board.description || '') 
                                                : (board.files && board.files.length 
                                                    ? (board.description ? board.description.split(' ').slice(0, 20).join(' ') + (board.description.split(' ').length > 20 ? '...' : '') : '') 
                                                    : (board.description ? board.description.split(' ').slice(0, 200).join(' ') + (board.description.split(' ').length > 200 ? '...' : '') : '')
                                                )"
                                            class="whitespace-pre-line"
                                        ></p>
                                        <button 
                                            x-show="(!expanded && (board.files && board.description && board.description.split(' ').length > 20)) 
                                                    || (!expanded && (!board.files || !board.files.length) && board.description && board.description.split(' ').length > 200)" 
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
            </div>
        </template>

        <template x-if="activeTab === 'teasers'">
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

        {{-- 📁 Files Tab --}}
        <template x-if="activeTab === 'files'">
            <div class="flex flex-col max-h-[30rem] border-t border-gray-300 bg-white rounded-xl overflow-hidden shadow">

                    <form method="POST" action="{{ route('files.store') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="file" id="fileInput" class="hidden" multiple @change="handleFileSelect($event)">
                    </form>
                <!-- 🎚️ Sticky Filters -->
                <div class="sticky top-0 z-[99] bg-gradient-to-b from-white to-gray-50 border-b border-gray-200 px-4 py-3 flex flex-wrap gap-4 items-center text-sm font-medium text-gray-700 shadow-sm">
                    <button 
                        x-show="!showListPanel"
                        @click="showListPanel = true"
                        class="absolute top-3 left-[-12px] bg-white border rounded-full p-2 text-pink-600 shadow hover:bg-pink-50 transition z-50 lg:hidden"
                        title="Open lists"
                    >
                        ➡️
                    </button>
                    <div class="flex flex-nowrap items-center gap-2 min-w-max hidden lg:block">
                        <!-- 🗂 Media Type Filter -->
                        <button
                            @click="fileTypeFilter = fileTypeFilter === 'image' ? 'all' : 'image'; loadUserFiles(true)"
                            :class="fileTypeFilter === 'image'
                                ? 'bg-purple-500 text-white border border-pink-300 rounded-full shadow'
                                : 'bg-white text-pink-700 border border-pink-300 rounded-full hover:bg-pink-50'"
                            class="px-3 py-1 text-sm font-medium transition filter-pill"
                        >
                            🖼️ Images
                        </button>

                        <button
                            @click="fileTypeFilter = fileTypeFilter === 'video' ? 'all' : 'video'; loadUserFiles(true)"
                            :class="fileTypeFilter === 'video'
                                ? 'bg-rose-500 text-white border border-pink-300 rounded-full shadow'
                                : 'bg-white text-pink-700 border border-pink-300 rounded-full hover:bg-pink-50'"
                            class="px-3 py-1 text-sm font-medium transition filter-pill"
                        >
                            🎬 Videos
                        </button>

                        <button
                            @click="contentTypeFilter = contentTypeFilter === 'safe' ? 'all' : 'safe'; loadUserFiles(true)"
                            :class="contentTypeFilter === 'safe'
                                ? 'bg-green-500 text-white border border-pink-300 rounded-full shadow'
                                : 'bg-white text-pink-700 border border-pink-300 rounded-full hover:bg-pink-50'"
                            class="px-3 py-1 text-sm font-medium transition filter-pill"
                        >
                            ✅ Safe
                        </button>

                        <button
                            @click="contentTypeFilter = contentTypeFilter === 'adult' ? 'all' : 'adult'; loadUserFiles(true)"
                            :class="contentTypeFilter === 'adult'
                                ? 'bg-red-500 text-white border border-pink-300 rounded-full shadow'
                                : 'bg-white text-pink-700 border border-pink-300 rounded-full hover:bg-pink-50'"
                            class="px-3 py-1 text-sm font-medium transition filter-pill"
                        >
                            🔞 Adult
                        </button>

                        <!-- ⏱ Sort Filter -->
                        <button
                            @click="sortOrder = 'latest'; loadUserFiles(true)"
                            :class="sortOrder === 'latest'
                                ? 'bg-amber-500 text-white border border-pink-300 rounded-full shadow'
                                : 'bg-white text-pink-700 border border-pink-300 rounded-full hover:bg-pink-50'"
                            class="px-3 py-1 text-sm font-medium transition filter-pill"
                        >
                            ⬆️ Latest
                        </button>

                        <button
                            @click="sortOrder = 'earliest'; loadUserFiles(true)"
                            :class="sortOrder === 'earliest'
                                ? 'bg-teal-500 text-white border border-pink-300 rounded-full shadow'
                                : 'bg-white text-pink-700 border border-pink-300 rounded-full hover:bg-pink-50'"
                            class="px-3 py-1 text-sm font-medium transition filter-pill"
                        >
                            ⬇️ Earliest
                        </button>
                    </div>
                    <!-- 📥 Bulk Actions -->
                    <div class="ml-auto relative flex flex-row items-center gap-2 flex-wrap sm:flex-nowrap">
                        <button @click="showMobileFilters = true"
                            class="group relative h-7 rounded-full bg-white text-pink-600 overflow-hidden shadow transition-[width,opacity] duration-700 ease-in-out w-7 sm:hover:w-44 whitespace-nowrap">
                            <span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-lg sm:group-hover:opacity-0">🧰</span>
                            <span class="pl-10 pr-4 opacity-0 group-hover:opacity-100 text-sm">Filters</span>
                        </button>

                        <button type="button"
                                @click.prevent="document.getElementById('fileInput').click()"
                                class="group relative h-7 rounded-full bg-white text-pink-600 overflow-hidden shadow transition-[width,opacity] duration-700 ease-in-out w-7 sm:hover:w-44 whitespace-nowrap">
                            <span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-lg sm:group-hover:opacity-0">+</span>
                            <span class="pl-10 pr-4 opacity-0 sm:group-hover:opacity-100 text-sm">New File</span>
                        </button>

                        <div class="relative">
                            <button @click="showBulkMenu = !showBulkMenu"
                                class="bg-white border border-gray-300 rounded-lg px-0.5 py-1 text-sm hover:bg-pink-50 flex items-center gap-2"
                                title="Bulk actions"
                            >
                                <!-- Vertical Dots SVG -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 20 20" stroke="currentColor">
                                    <circle cx="10" cy="4" r="1.5"/>
                                    <circle cx="10" cy="10" r="1.5"/>
                                    <circle cx="10" cy="16" r="1.5"/>
                                </svg>
                                <span class="text-xs text-gray-500" x-show="selectedFileIds.length">
                                    (<span x-text="selectedFileIds.length"></span>)
                                </span>
                            </button>
                            <div
                                x-show="showBulkMenu"
                                @click.outside="showBulkMenu = false"
                                class="absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded shadow-md z-[1999] text-sm"
                                x-transition
                                style="display: none;"
                            >
                                <button
                                    @click="openCopyModal"
                                    class="block w-full px-4 py-2 hover:bg-pink-50 text-left"
                                    :disabled="selectedFileIds.length === 0"
                                    :class="{ 'opacity-50 cursor-not-allowed': selectedFileIds.length === 0 }"
                                >📄 Copy files</button>

                                <!-- 📂 Move -->
                                <button
                                    @click="openMoveModal"
                                    class="block w-full px-4 py-2 hover:bg-pink-50 text-left"
                                    :disabled="selectedFileIds.length === 0 || activeListId === 'all'"
                                    :class="{ 
                                        'opacity-50 cursor-not-allowed': selectedFileIds.length === 0 || activeListId === 'all',
                                        'text-gray-400': activeListId === 'all'
                                    }"
                                >📂 Move files</button>

                                <!-- 🗑️ Delete or Remove -->
                                <button
                                    @click="openDeleteModal"
                                    class="block w-full px-4 py-2 hover:bg-red-50 text-left text-red-600"
                                    :disabled="selectedFileIds.length === 0"
                                    :class="{ 'opacity-50 cursor-not-allowed': selectedFileIds.length === 0 }"
                                >
                                    <span x-text="activeListId === 'all' ? '🗑️ Delete Files' : '🗑️ Remove Files'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 📂 Scrollable File Grid -->
                <div class="overflow-y-auto flex-1 min-h-[28rem] relative">
                    <template x-if="loadingFiles">
                        <div class="absolute inset-0 flex items-center justify-center text-gray-500 text-sm font-medium">
                            ⏳ Loading your files...
                        </div>
                    </template>

                    <div class="px-2 py-4 sm:px-4 md:px-6 lg:px-8 grid gap-3 sm:gap-4"
                        style="grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));">
                        <template x-for="(file, index) in filteredFiles" :key="index">
                            <div
                                @click="if (!$event.target.closest('.no-preview')) focusedFile = file; fullPreviewOpen = true"
                                class="aspect-square bg-white border border-gray-300 rounded-xl overflow-hidden relative hover:scale-[1.05] transition shadow-sm group"
                            >
                                <!-- ✅ Select File Checkbox -->
                                <div class="absolute top-1 left-1 no-preview" @click.stop>
                                    <input type="checkbox"
                                        :value="file.id"
                                        :checked="selectedFileIds.includes(file.id)"
                                        @change="toggleFileSelection(file.id)"
                                        class="w-4 h-4 text-pink-600 rounded focus:ring-pink-500 border-gray-300 z-50 relative"
                                    />
                                </div>
                                <!-- 🎬 Video -->
                                <template x-if="file.filename.match(/\.(mp4|mov|avi|webm)$/i)">
                                    <video :src="file.path" muted playsinline preload="metadata" class="w-full h-full object-cover"></video>
                                </template>

                                <!-- 🖼️ Image -->
                                <template x-if="file.filename.match(/\.(jpg|jpeg|png|gif|webp)$/i)">
                                    <img :src="file.path" class="w-full h-full object-cover" alt="">
                                </template>

                                <!-- ❓ Unknown -->
                                <template x-if="!file.filename.match(/\.(jpg|jpeg|png|gif|webp|mp4|mov|avi|webm)$/i)">
                                    <div class="flex items-center justify-center h-full text-2xl text-gray-500">❓</div>
                                </template>

                                <!-- 📌 Badge -->
                                <template x-if="file.filename.match(/\.(mp4|mov|avi|webm)$/i)">
                                    <div class="absolute top-1 right-1 bg-black/70 text-white text-[0.6rem] px-1 rounded shadow-sm">🎬</div>
                                </template>

                                <template x-if="file.filename.match(/\.(jpg|jpeg|png|gif|webp)$/i)">
                                    <div class="absolute top-1 right-1 bg-black/70 text-white text-[0.6rem] px-1 rounded shadow-sm">🖼️</div>
                                </template>
                            </div>
                        </template>
                        <!-- 🕵️ Intersection Trigger -->
                        <div 
                            x-intersect:enter="if (!loadingFiles && hasMoreFiles) loadUserFiles()" 
                            class="h-6">
                        </div>
                        <template x-if="!hasMoreFiles">
                            <div class="text-center py-3 text-sm text-gray-500">
                                ✅ You’ve reached the end!
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>


<template x-if="previewModal">
    <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-[1999]">
        <div class="bg-white rounded-xl p-6 w-[700px] max-w-[90%] max-h-[90vh] overflow-y-auto flex flex-col items-center gap-6 relative">
            <h2 class="text-lg font-semibold text-gray-800">Preview File</h2>

            <!-- File Preview with count overlay -->
            <div class="relative w-full flex items-center justify-center">
                <!-- Image Preview -->
                <template x-if="selectedFiles[previewIndex]?.type?.startsWith('image/')">
                    <img :src="URL.createObjectURL(selectedFiles[previewIndex])"
                        class="rounded max-h-[400px] object-contain w-full" />
                </template>
                <!-- Video Preview -->
                <template x-if="selectedFiles[previewIndex]?.type?.startsWith('video/')">
                    <video :src="URL.createObjectURL(selectedFiles[previewIndex])"
                        controls
                        class="rounded max-h-[400px] object-contain w-full"></video>
                </template>
                <!-- File count overlay -->
                <div class="absolute bottom-2 left-1/2 -translate-x-1/2 bg-black/70 text-white text-xs px-3 py-1 rounded-full shadow-lg font-semibold z-10">
                    <span x-text="(previewIndex + 1) + ' / ' + selectedFiles.length"></span>
                </div>
            </div>

            <!-- File name input with extension separated -->
            <div class="w-full flex items-center gap-2">
                <template x-if="selectedFiles[previewIndex]">
                    <div class="flex items-center w-full">
                        <input
                            type="text"
                            class="border px-3 py-1.5 rounded-l w-full text-sm"
                            :value="fileNameInputs[previewIndex] ? fileNameInputs[previewIndex].replace(/\.[^/.]+$/, '') : selectedFiles[previewIndex].name.replace(/\.[^/.]+$/, '')"
                            @input="fileNameInputs[previewIndex] = $event.target.value.replace(/\.[^/.]+$/, '') + getFileExtension(selectedFiles[previewIndex].name)"
                            placeholder="Enter new file name (optional)"
                        >
                        <span class="border border-l-0 px-2 py-1.5 rounded-r text-sm bg-gray-100 text-gray-600 select-none">
                            <span x-text="getFileExtension(selectedFiles[previewIndex]?.name)"></span>
                        </span>
                    </div>
                </template>
            </div>

            <!-- Content Type Select -->
            <div class="flex gap-2 text-sm font-medium">
                <button :class="contentTypes[previewIndex] === 'safe' ? 'bg-green-500 text-white' : 'bg-gray-100'"
                        @click="contentTypes[previewIndex] = 'safe'"
                        class="px-3 py-1 rounded">
                    ✅ Safe
                </button>
                <button :class="contentTypes[previewIndex] === 'adult' ? 'bg-red-500 text-white' : 'bg-gray-100'"
                        @click="contentTypes[previewIndex] = 'adult'"
                        class="px-3 py-1 rounded">
                    🔞 Adult
                </button>
            </div>

            <!-- Select Target List -->
            <div class="w-full">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Add to a custom list (optional)</label>
                <select x-model="selectedListIds[previewIndex]"
                        class="w-full px-3 py-2 border rounded text-sm bg-white">
                    <option value="">— None selected —</option>
                    <template x-for="list in fileLists" :key="list.id">
                        <option :value="list.id" x-text="list.name"></option>
                    </template>
                </select>
            </div>

            <!-- Continue Button -->
            <button @click="previewModal = false; uploadProgressModal = true; submitFiles()"
                    class="mt-4 px-4 py-2 rounded bg-pink-500 text-white hover:bg-pink-600">
                Continue
            </button>

            <!-- Navigation Arrows -->
            <template x-if="selectedFiles.length > 1">
                <button @click="previewIndex = (previewIndex - 1 + selectedFiles.length) % selectedFiles.length"
                        class="absolute left-2 top-1/2 transform -translate-y-1/2 bg-white p-2 rounded-full shadow z-10">
                    ◀️
                </button>
            </template>
            <template x-if="selectedFiles.length > 1">
                <button @click="previewIndex = (previewIndex + 1) % selectedFiles.length"
                        class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-white p-2 rounded-full shadow z-10">
                    ▶️
                </button>
            </template>
        </div>
    </div>
</template>

            <template x-if="fullPreviewOpen">
                <div class="fixed inset-0 bg-black/80 z-[1999] flex items-center justify-center">
                    <!-- ✖️ Close Button -->
                    <button @click="fullPreviewOpen = false"
                            class="absolute top-6 right-6 text-white text-2xl hover:text-pink-300 transition">
                        ×
                    </button>

                    <!-- 🖼️ Image Preview -->
                    <template x-if="focusedFile.filename.match(/\.(jpg|jpeg|png|gif|webp)$/i)">
                        <img :src="focusedFile.path"
                            class="max-w-[90vw] max-h-[90vh] object-contain rounded shadow-lg">
                    </template>

                    <!-- 🎬 Video Preview -->
                    <template x-if="focusedFile.filename.match(/\.(mp4|mov|avi|webm)$/i)">
                        <video :src="focusedFile.path"
                            autoplay
                            controls
                            class="max-w-[90vw] max-h-[90vh] rounded shadow-lg"
                            :muted="false"
                            playsinline
                            preload="metadata"></video>
                    </template>
                </div>
            </template>



    </div>

    {{-- 📌 Sidebar Navigation --}}
    <div class="hidden lg:block w-1/5">
        <div class="sticky top-28 space-y-4">
            <h3 class="text-xl font-semibold mb-4">Navigation</h3>

            <a href="{{ route('home') }}"
               class="block px-4 py-2 rounded-lg font-medium text-sm text-gray-700 hover:bg-pink-100 transition">
               🏠 Home
            </a>
            <a href="/messages"
               class="block px-4 py-2 rounded-lg font-medium text-sm text-gray-700 hover:bg-pink-100 transition">
               💌 Messages
            </a>
            <a href="{{ route('boards.me') }}"
               class="block px-4 py-2 rounded-lg font-medium text-sm text-gray-700 hover:bg-pink-100 transition">
               💫 Me
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

    {{-- 🔔 Toast Container --}}
    <div id="toastBox" class="fixed bottom-6 right-6 z-50 hidden">
        <div id="toastMessage"
             class="px-4 py-2 rounded shadow-lg text-white bg-green-500 text-sm font-medium"></div>
    </div>

    <!-- 📱 Mobile Filter Drawer -->
    <div
        class="lg:hidden fixed inset-0 bg-black/30 z-50 flex items-end"
        x-show="showMobileFilters"
        @click.self="showMobileFilters = false"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-full"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-full"
        style="display: none;"
    >
        <div class="w-full bg-white rounded-t-2xl p-5 shadow-xl max-h-[50vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-bold text-gray-800" x-text="activeTab === 'moodboards' ? 'Mood Filters' : 'File Filters'"></h3>
                <button @click="showMobileFilters = false" class="text-gray-500 hover:text-gray-800 text-xl">×</button>
            </div>

            <!-- Filters -->
            <template x-if="activeTab === 'moodboards'">
                <div class="space-y-4">
                    <!-- 🧠 Reaction Filters -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-600 mb-2">Reactions</h4>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="(emoji, mood) in moods" :key="mood">
                                <button
                                    @click="toggleMood(mood)"
                                    class="px-3 py-1 rounded-full text-xs font-medium transition"
                                    :class="selectedMoods.includes(mood) 
                                        ? 'bg-pink-500 text-white shadow' 
                                        : 'bg-gray-100 text-gray-800 hover:bg-gray-200'"
                                    x-text="emoji + ' ' + mood.charAt(0).toUpperCase() + mood.slice(1)">
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- 🎞️ Media Type Filters -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-600 mb-2">Media Type</h4>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="(label, type) in mediaTypes" :key="type">
                                <button
                                    @click="toggleMediaType(type)"
                                    class="px-3 py-1 rounded-full text-xs font-medium transition"
                                    :class="selectedMediaTypes.includes(type) 
                                        ? 'bg-pink-600 text-white shadow' 
                                        : 'bg-gray-100 text-gray-800 hover:bg-gray-200'"
                                    x-text="label">
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Filters -->
            <template x-if="activeTab === 'files'">
                <div class="space-y-4">
                    <!-- 🖼️ Type -->
                    <div>
                        <h4 class="text-sm font-semibold mb-2 text-gray-600">File Type</h4>
                        <div class="flex flex-wrap gap-2">
                            <button
                                @click="fileTypeFilter = fileTypeFilter === 'image' ? 'all' : 'image'; loadUserFiles(true)"
                                :class="fileTypeFilter === 'image'
                                    ? 'bg-purple-500 text-white border border-pink-300 rounded-full shadow'
                                    : 'bg-white border border-pink-300 text-pink-700 hover:bg-pink-50 rounded-full'"
                                class="px-3 py-1 text-xs font-medium transition filter-pill"
                            >🖼️ Image</button>
                            <button
                                @click="fileTypeFilter = fileTypeFilter === 'video' ? 'all' : 'video'; loadUserFiles(true)"
                                :class="fileTypeFilter === 'video'
                                    ? 'bg-rose-500 text-white border border-pink-300 rounded-full shadow'
                                    : 'bg-white border border-pink-300 text-pink-700 hover:bg-pink-50 rounded-full'"
                                class="px-3 py-1 text-xs font-medium transition filter-pill"
                            >🎬 Video</button>
                        </div>
                    </div>

                    <!-- 🔐 Content -->
                    <div>
                        <h4 class="text-sm font-semibold mb-2 text-gray-600">Content Type</h4>
                        <div class="flex flex-wrap gap-2">
                            <button
                                @click="contentTypeFilter = contentTypeFilter === 'safe' ? 'all' : 'safe'; loadUserFiles(true)"
                                :class="contentTypeFilter === 'safe'
                                    ? 'bg-green-500 text-white border border-pink-300 rounded-full shadow'
                                    : 'bg-white border border-pink-300 text-pink-700 hover:bg-pink-50 rounded-full'"
                                class="px-3 py-1 text-xs font-medium transition filter-pill"
                            >✅ Safe</button>
                            <button
                                @click="contentTypeFilter = contentTypeFilter === 'adult' ? 'all' : 'adult'; loadUserFiles(true)"
                                :class="contentTypeFilter === 'adult'
                                    ? 'bg-red-500 text-white border border-pink-300 rounded-full shadow'
                                    : 'bg-white border border-pink-300 text-pink-700 hover:bg-pink-50 rounded-full'"
                                class="px-3 py-1 text-xs font-medium transition filter-pill"
                            >🔞 Adult</button>
                        </div>
                    </div>

                    <!-- 🕰️ Sort -->
                    <div>
                        <h4 class="text-sm font-semibold mb-2 text-gray-600">Sort</h4>
                        <div class="flex flex-wrap gap-2">
                            <button
                                @click="sortOrder = 'latest'; loadUserFiles(true)"
                                :class="sortOrder === 'latest'
                                    ? 'bg-amber-500 text-white border border-pink-300 rounded-full shadow'
                                    : 'bg-white border border-pink-300 text-pink-700 hover:bg-pink-50 rounded-full'"
                                class="px-3 py-1 text-xs font-medium transition filter-pill"
                            >⬆️ Latest</button>
                            <button
                                @click="sortOrder = 'earliest'; loadUserFiles(true)"
                                :class="sortOrder === 'earliest'
                                    ? 'bg-teal-500 text-white border border-pink-300 rounded-full shadow'
                                    : 'bg-white border border-pink-300 text-pink-700 hover:bg-pink-50 rounded-full'"
                                class="px-3 py-1 text-xs font-medium transition filter-pill"
                            >⬇️ Earliest</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<div 
  x-show="showListPanel"
   x-cloak
  @click.outside="showListPanel = false"
  class="fixed top-[5%] left-0 w-[80vw] h-[90vh] z-[999] bg-white shadow-xl rounded-r-2xl overflow-y-auto border-r border-gray-300 transition-all"
  x-transition:enter="transform ease-out duration-300"
  x-transition:enter-start="-translate-x-full"
  x-transition:enter-end="translate-x-0"
  x-transition:leave="transform ease-in duration-200"
  x-transition:leave-start="translate-x-0"
  x-transition:leave-end="-translate-x-full"
>
  <!-- ⬅️ Close Button (always rendered inside the panel) -->
  <button
    @click="showListPanel = false"
    class="absolute top-3 right-3 bg-white border rounded-full p-2 text-pink-600 shadow hover:bg-pink-50 transition z-50"
    title="Close list tools"
  >
    ⬅️
  </button>
  <!-- 🧰 Your list editing content goes here -->
  <div class="p-4">
    <h2 class="text-lg font-bold text-pink-600">🗂 My lists</h2>

        <!-- 📌 Sticky New List Button -->
    <div class="sticky top-1 z-10 bg-white border-b border-gray-200 p-3">
        <button @click="showCreateListModal = true; showListPanel = false"
            class="w-full px-3 py-2 rounded bg-pink-500 text-white text-xs font-semibold hover:bg-pink-600 transition">
            ➕ New List
        </button>
    </div>
            <!-- 📂 Scrollable File Lists -->
        <div class="overflow-y-auto px-3 pt-2 pb-6 space-y-3" style="max-height: calc(100vh - 11rem)">
            
            <!-- All Media -->
            <div 
                @click="activeList = 'all'; activeListId = 'all'; loadUserFiles(true); showListPanel = false"
                class="p-3 border border-gray-300 rounded-md cursor-pointer group hover:border-pink-500 transition"
                :class="{ 'border-pink-500 ring-1 ring-pink-300': activeList === 'all' }"
            >
                <h4 class="text-sm font-semibold text-gray-700 mb-1 group-hover:text-pink-600 transition">
                    All Media
                </h4>
                <div class="flex items-center gap-3 text-xs text-gray-600">
                    <span>🖼️ <span x-text="imageCount"></span></span>
                    <span>🎬 <span x-text="videoCount"></span></span>
                </div>
                <hr class="mt-3 border-t border-gray-200" />
            </div>

            <!-- User-Created Lists -->
            <template x-for="list in fileLists" :key="list.id">
                <div
                    @click="activeList = list.id; activeListId = list.id; loadUserFiles(true); showListPanel = false"
                    class="relative p-3 border border-gray-300 rounded-md cursor-pointer group hover:border-pink-500 transition"
                    :class="{ 'border-pink-500 ring-1 ring-pink-300': activeList === list.id }"
                >
                    <!-- 🔤 List Name -->
                    <h4 class="text-sm font-semibold text-gray-700 mb-1 group-hover:text-pink-600 transition" x-text="list.name"></h4>

                    <!-- 📊 Counts -->
                    <div class="flex items-center gap-3 text-xs text-gray-600">
                        <span>🖼️ <span x-text="list.imageCount"></span></span>
                        <span>🎬 <span x-text="list.videoCount"></span></span>
                    </div>

                    <hr class="mt-3 border-t border-gray-200" />

                    <!-- ⋯ Context Menu Trigger (exclude 'all') -->
                    <template x-if="list.id !== 'all'">
                        <button
                            @click.stop="openListMenu(list.id)"
                            class="absolute top-2 right-2 text-gray-400 hover:text-pink-500 transition"
                            title="List actions"
                        >⋯</button>
                    </template>

                    <!-- ⚙️ List Actions Menu -->
                    <template x-if="activeMenu === list.id">
                        <div
                            class="absolute right-2 top-8 bg-white border border-gray-200 rounded shadow-md z-50 text-sm w-40"
                            @click.outside="activeMenu = null"
                        >
                            <button @click="startEditingList(list); showListPanel = false" class="block w-full px-4 py-2 text-left hover:bg-pink-50">✏️ Edit Name</button>
                            <button @click="listToDelete = list; showDeleteModal = true; activeMenu = null; showListPanel = false" class="block w-full px-4 py-2 text-left text-red-600 hover:bg-red-50">🗑️ Delete List</button>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    <!-- Put interactive list components, inputs, filters, etc. here -->
  </div>
</div>
</div>
                                <template x-if="showRenameModal">
                                    <div class="fixed inset-0 flex items-center z-1000 justify-center bg-black/40">
                                        <div class="bg-white p-6 rounded-xl shadow-md w-full max-w-sm">
                                            <h2 class="text-lg font-semibold text-gray-800 mb-4">✏️ Rename List</h2>
                                            
                                            <input 
                                                type="text"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-pink-500 focus:border-pink-500"
                                                x-model="listEditName"
                                                placeholder="Enter new name"
                                            />

                                            <div class="mt-5 flex justify-end gap-2">
                                                <button @click="cancelRename" class="px-3 py-1 text-sm rounded bg-gray-100 hover:bg-gray-200">Cancel</button>
                                                <button @click="submitRename" class="px-3 py-1 text-sm rounded bg-pink-600 text-white hover:bg-pink-700">Save</button>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="uploadProgressModal">
                                    <div class="fixed inset-0 bg-black/60 flex items-center justify-center z-50">
                                        <div class="bg-white p-6 rounded-lg w-[400px] text-center flex flex-col items-center gap-4">
                                            <h2 class="text-lg font-semibold">Uploading Files...</h2>
                                            <div class="text-sm text-gray-600">
                                                <span x-text="uploadCount + ' of ' + selectedFiles.length + ' uploaded'"></span>
                                            </div>
                                            <div class="w-full bg-gray-300 h-3 rounded overflow-hidden">
                                                <div class="h-full bg-pink-500 transition-all"
                                                    :style="`width: ${(uploadCount / selectedFiles.length) * 100}%`"></div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="showDeleteModal">
                                    <div class="fixed inset-0 z-1000 flex items-center justify-center bg-black/40">
                                        <div class="bg-white p-6 rounded-xl shadow-md w-full max-w-sm">
                                            <h2 class="text-lg font-semibold text-gray-800 mb-4">⚠️ Confirm Delete</h2>
                                            <p class="text-sm text-gray-600 mb-4">
                                                Are you sure you want to delete <strong x-text="listToDelete?.name"></strong>?<br/>
                                                All list references will be removed, but files will remain.
                                            </p>

                                            <div class="flex justify-end gap-2">
                                                <button @click="cancelDelete" class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded">Cancel</button>
                                                <button @click="submitDelete" class="px-3 py-1 text-sm bg-red-600 text-white hover:bg-red-700 rounded">Delete</button>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                                <!-- 🌟 Create List Modal -->
                                <div x-show="showCreateListModal" class="fixed inset-0 bg-black/40 z-1000 flex items-center justify-center" @click.self="showCreateListModal = false" style="display: none;">
                                    <div class="bg-white rounded-lg p-6 w-[90%] max-w-md shadow-xl">
                                        <h3 class="text-lg font-bold text-gray-800 mb-4">Create New List</h3>
                                        <form @submit.prevent="submitNewList">
                                            <input
                                                type="text"
                                                x-model="newListName"
                                                placeholder="List name..."
                                                class="w-full px-3 py-2 rounded border border-gray-300 focus:outline-pink-500 text-sm mb-4"
                                            />
                                            <div class="flex justify-end gap-2">
                                                <button type="button" @click="showCreateListModal = false"
                                                        class="px-3 py-1 rounded text-sm bg-gray-100 hover:bg-gray-200">Cancel</button>
                                                <button type="submit"
                                                        class="px-4 py-1 rounded text-sm bg-pink-500 text-white hover:bg-pink-600">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                <template x-if="showCopyModal">
                                    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center">
                                        <div class="bg-white p-6 rounded-xl shadow-lg max-w-sm w-full">
                                            <h2 class="text-lg font-semibold text-gray-800 mb-3">📄 paste Files to:</h2>
                                            <p class="text-sm text-gray-600 mb-4">
                                                Choose a list to copy <span x-text="selectedFileIds.length"></span> files into.
                                            </p>

                                            <!-- List Dropdown -->
                                            <select x-model="targetListId"
                                                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm mb-4">
                                                <option value="" disabled>Select destination list</option>
                                                <template x-for="list in fileLists.filter(l => l.id !== 'all' && l.id !== activeListId)" :key="list.id">
                                                    <option :value="list.id" x-text="list.name"></option>
                                                </template>
                                            </select>

                                            <div class="flex justify-end gap-2 mt-5">
                                                <button @click="showCopyModal = false" class="px-3 py-1 rounded bg-gray-100 hover:bg-gray-200 text-sm">Cancel</button>
                                                <button @click="submitCopyToList" class="px-3 py-1 rounded bg-pink-600 text-white hover:bg-pink-700 text-sm">Paste</button>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="showMoveModal">
                                    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center">
                                        <div class="bg-white p-6 rounded-xl shadow-lg max-w-sm w-full">
                                            <h2 class="text-lg font-semibold text-gray-800 mb-3">📂 Move Files to:</h2>
                                            <p class="text-sm text-gray-600 mb-4">
                                                Move <span x-text="selectedFileIds.length"></span> files to a different list.
                                            </p>

                                            <!-- List Dropdown -->
                                            <select x-model="targetListId"
                                                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm mb-4">
                                                <option value="" disabled>Select destination list</option>
                                                <template x-for="list in fileLists.filter(l => l.id !== 'all' && l.id !== activeListId)" :key="list.id">
                                                    <option :value="list.id" x-text="list.name"></option>
                                                </template>
                                            </select>

                                            <div class="flex justify-end gap-2 mt-5">
                                                <button @click="showMoveModal = false" class="px-3 py-1 rounded bg-gray-100 hover:bg-gray-200 text-sm">Cancel</button>
                                                <button @click="submitMoveToList" class="px-3 py-1 rounded bg-pink-600 text-white hover:bg-pink-700 text-sm">Move</button>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="showDeleteFilesModal">
                                    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center">
                                        <div class="bg-white p-6 rounded-xl shadow-lg max-w-sm w-full">
                                            <!-- Dynamic Title -->
                                            <h2 class="text-lg font-semibold text-gray-800 mb-3">
                                                <span x-text="activeListId === 'all' ? '🗑️ Confirm Delete' : '🚫 Remove from List'"></span>
                                            </h2>

                                            <!-- Dynamic Description -->
                                            <p class="text-sm text-gray-600 mb-4">
                                                <template x-if="activeListId === 'all'">
                                                    <span>
                                                        You're about to permanently delete
                                                        <span x-text="selectedFileIds.length"></span>
                                                        files. This cannot be undone.
                                                    </span>
                                                </template>
                                                <template x-if="activeListId !== 'all'">
                                                    <span>
                                                        This will remove
                                                        <span x-text="selectedFileIds.length"></span>
                                                        files from this list only.
                                                    </span>
                                                </template>
                                            </p>

                                            <div class="flex justify-end gap-2 mt-5">
                                                <button @click="showDeleteFilesModal = false"
                                                        class="px-3 py-1 rounded bg-gray-100 hover:bg-gray-200 text-sm">
                                                    Cancel
                                                </button>

                                                <button @click="submitDeleteFiles"
                                                        class="px-3 py-1 rounded text-white text-sm"
                                                        :class="activeListId === 'all' ? 'bg-red-600 hover:bg-red-700' : 'bg-yellow-500 hover:bg-yellow-600'">
                                                    <span x-text="activeListId === 'all' ? 'Delete' : 'Remove'"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Moodboard File Preview Modal -->
                                <template x-if="showBoardPreviewModal">
                                    <div class="fixed inset-0 z-[9999] bg-black/80 flex items-center justify-center">
                                        <button @click="showBoardPreviewModal = false" class="absolute top-6 right-6 text-white text-3xl hover:text-pink-300 transition">×</button>
                                        <template x-if="previewBoardFile && previewBoardFile.type === 'image'">
                                            <img :src="previewBoardFile.path" class="max-w-[95vw] max-h-[95vh] object-contain rounded shadow-2xl" />
                                        </template>
                                        <template x-if="previewBoardFile && previewBoardFile.type === 'video'">
                                            <video :src="previewBoardFile.path" controls autoplay loop class="max-w-[95vw] max-h-[95vh] rounded shadow-2xl"></video>
                                        </template>
                                    </div>
                                </template>
</div>
<style>
    .filter-pill {
        transition: all 0.2s ease;
    }
video::-webkit-media-controls,
video::-webkit-media-controls-panel,
video::-webkit-media-controls-play-button,
video::-webkit-media-controls-timeline,
video::-webkit-media-controls-current-time-display,
video::-webkit-media-controls-fullscreen-button {
    display: none !important;
}

[x-cloak] { display: none !important; }

</style>
@endsection


@push('scripts')
<script>
function getRemainingTime(expiresOn) {
    if (!expiresOn) return '—';
    const now = new Date();
    const expiry = new Date(expiresOn);
    const diffMs = expiry - now;
    if (diffMs <= 0) return 'Expired';

    const mins = Math.floor(diffMs / 60000);
    const hrs = Math.floor(mins / 60);
    const remMins = mins % 60;

    return `${hrs}h ${remMins}m`;
}

document.addEventListener('alpine:init', () => {
    Alpine.data('meBoards', () => ({
        authUser: @json(auth()->user()),
        activeTab: 'moodboards',
            boards: [],
            loading: true,
            selectedMoods: [],
            selectedMediaTypes: [],

            moods: {
                excited: "🔥",
                happy: "😊",
                chill: "😎",
                thoughtful: "🤔",
                sad: "😭",
                flirty: "😏",
                mindblown: "🤯",
                love: "💖",
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
                image: '🖼️ Image',
                video: '🎬 Video',
                text: '📝 Text',
            },

        previewModal: false,
        previewUrl: null,
        selectedFile: null,
        fileNameInput: '',
        uploadProgressModal: false,
        uploading: false,
        uploadProgress: 0,
        showBoardPreviewModal: false,
        previewBoardFile: null,

        userFiles: [],
        fileOffset: 0,
        hasMoreFiles: true,
        previewIndex: 0,
        fileNameInputs: [],
        contentTypes: [],
        fileTypeFilter: 'all',       // 'all', 'image', 'video'
        contentTypeFilter: 'all',    // 'all', 'safe', 'adult'
        sortOrder: 'latest',
        focusedFile: null,
        fullPreviewOpen: false,
        searchQuery: '',
        showMobileFilters: false,
        showCreateListModal: false,
        newListName: '',
        activeList: 'all',
        imageCount: 0,
        videoCount: 0,
        fileLists: [],
        selectedListIds: [],
        activeListId: 'all',
        activeListStats: null,
        activeClass: 'bg-pink-500 text-white shadow',
        inactiveClass: 'bg-white border border-pink-300 text-pink-700 hover:bg-pink-50',
        baseFilterClass: 'px-3 py-1 rounded-full text-sm font-medium border border-gray-300 bg-gray-50 text-gray-700 hover:bg-gray-100 transition duration-150 active:scale-[0.98]',
        activeMenu: null,
        listEditName: '',
        showRenameModal: false,
        editingList: null,
        showDeleteModal: false,
        listToDelete: null,
        selectedFileIds: [],
        showBulkMenu: false,
        showCopyModal: false,
        showMoveModal: false,
        showDeleteFilesModal: false,
        targetListId: null,
        loadingFiles: false,
        showListPanel: false,
        favoriteClicks: 0,
        cooldownActive: false,
        teasers: [],
        loadingTeasers: false,
        teasers: [],
        loadingTeasers: false,
        fastForwardInterval: null,
        showStickyTabs: false,
        currentPlayingTeaserId: null,
        nextPage: 2,
        hasMoreBoards: true,

        // Fetch function
        async fetchTeasers() {
            this.loadingTeasers = true;
            try {
                console.log('[fetchTeasers] Fetching teasers from /my-teasers...');
                const res = await fetch('/my-teasers', {
                    headers: { 'Accept': 'application/json' }
                });

                console.log('[fetchTeasers] Response status:', res.status);

                if (!res.ok) {
                    const text = await res.text().catch(() => '');
                    console.error(`[fetchTeasers] Server responded with error: ${res.status}`, text);
                    throw new Error(`Failed to fetch teasers: ${res.status}`);
                }

                const data = await res.json();
                console.log('[fetchTeasers] Teasers data received:', data);

                this.teasers = data.teasers || [];
            } catch (err) {
                console.error('[fetchTeasers] Failed to fetch teasers:', err);
                this.teasers = [];
                this.showToast('Could not load teasers. Please try again.', 'error');
            } finally {
                this.loadingTeasers = false;
                console.log('[fetchTeasers] Loading state set to false');
            }
        },

        // Determines if a teaser should autoplay
        isCenterStage(teaser) {
            return true; // Replace with actual logic
        },

        handlePlay(id) {
            // Pause all other videos
            this.teasers.forEach(teaser => {
                if (teaser.id !== id) {
                    const ref = this.$refs['videoEl' + teaser.id];
                    if (ref && !ref.paused) ref.pause();
                }
            });
            this.currentPlayingTeaserId = id;
        },

        handlePause(id) {
            if (this.currentPlayingTeaserId === id) {
                this.currentPlayingTeaserId = null;
            }
        },

        togglePlay(videoEl) {
            if (!videoEl) return;
            if (videoEl.paused) {
                videoEl.play();
            } else {
                videoEl.pause();
            }
        },

        // Fast-forward logic
        startFastForward(videoEl) {
            if (!videoEl) return;
            this.fastForwardInterval = setInterval(() => {
                videoEl.currentTime += 0.5;
            }, 100);
        },

        getFileExtension(filename) {
            if (!filename) return '';
            const match = filename.match(/(\.[^/.]+)$/);
            return match ? match[1] : '';
        },

        stopFastForward(videoEl) {
            clearInterval(this.fastForwardInterval);
            this.fastForwardInterval = null;
        },

        toggleFavorite(boardId) {
            // Initialize tracking if not present
            if (!this.favoriteClicksByBoard) this.favoriteClicksByBoard = {};
            if (!this.cooldownByBoard) this.cooldownByBoard = {};

            const clicks = this.favoriteClicksByBoard[boardId] || 0;
            const isCooldown = this.cooldownByBoard[boardId] || false;

            if (isCooldown) {
                this.showToast('Please wait a minute before trying again.', 'error');
                return;
            }

            this.favoriteClicksByBoard[boardId] = clicks + 1;

            if (this.favoriteClicksByBoard[boardId] >= 5) {
                this.cooldownByBoard[boardId] = true;

                setTimeout(() => {
                    this.favoriteClicksByBoard[boardId] = 0;
                    this.cooldownByBoard[boardId] = false;
                }, 60000); // 1 minute
            }

            const payload = { moodboard_id: boardId };

            fetch('/toggle-favorite', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify(payload),
            })
            .then(res => res.json())
            .then(data => {
                const board = this.filteredBoards.find(b => b.id === boardId);

                if (data.success) {
                    if (board) {
                        board.is_favorited = data.favorited;
                    }

                    const message = data.favorited
                        ? 'Added to favorites 💖'
                        : 'Removed from favorites 💔';

                    this.showToast(message, 'success');
                } else {
                    this.showToast(data.message || 'Could not favorite moodboard.', 'error');
                }
            })
            .catch(() => {
                this.showToast('Network error. Please try again.', 'error');
            });
        },

        async loadBoards(page = 1, perPage = 20, append = false) {
            this.loading = true;
            const viewerId = window.auth?.user?.id ?? null;
            const t0 = performance.now();

            console.groupCollapsed('%c[loadBoards] Fetching boards', 'color:#6b7280; font-weight:600');
            console.log('Viewer ID:', viewerId ?? '(unknown)');
            console.log('Page:', page, 'PerPage:', perPage, 'Append:', append);

            try {
                const res = await fetch(`/boards/me?page=${page}&per_page=${perPage}`, {
                    credentials: 'include',
                    headers: { Accept: 'application/json' }
                });
                console.log('GET /api/boards/me', { page, perPage }, '=>', res.status, res.ok ? 'OK' : 'ERROR');

                if (!res.ok) {
                    const text = await res.text().catch(() => '');
                    console.error('Response not OK. Body:', text);
                    throw new Error(`Failed to load boards: ${res.status}`);
                }

                const data = await res.json();
                console.log('[loadBoards] Boards response:', data);

                const normalizePath = (p) => {
                    if (!p) return null;
                    if (p.startsWith('http')) return p;
                    const cleaned = p.replace(/^\/?storage\//, '');
                    return `/storage/${cleaned}`;
                };

                const isFavorite = (b) => Boolean(
                    b.is_favorite ??
                    b.isFavourite ??
                    b.is_favorited ??
                    b.favorite ??
                    b.favorited ??
                    b.pivot?.favorite ??
                    false
                );

                const boards = (Array.isArray(data.moodboards) ? data.moodboards : []).map((board) => {
                    let files = [];

                    // Images
                    let imgs = board.images ?? board.image ?? null;
                    if (typeof imgs === 'string') {
                        try { imgs = JSON.parse(imgs); } catch { /* keep as string */ }
                    }

                    if (Array.isArray(imgs)) {
                        files.push(...imgs.map(path => ({ path: normalizePath(path), type: 'image' })));
                    } else if (typeof imgs === 'string' && imgs.trim() !== '') {
                        files.push({ path: normalizePath(imgs), type: 'image' });
                    }

                    // Video
                    if (board.video) {
                        files.push({ path: normalizePath(board.video), type: 'video' });
                    }

                    // Dedupe
                    const seen = new Set();
                    files = files.filter(f => {
                        if (!f?.path) return false;
                        if (seen.has(f.path)) return false;
                        seen.add(f.path);
                        return true;
                    });

                    return {
                        ...board,
                        files,
                        favorite: isFavorite(board),
                        newComment: '',
                        comment_count: board.comment_count ?? 0,
                    };
                });

                if (append && page > 1) {
                    console.log(`[loadBoards] Appending ${boards.length} boards to existing list`);
                    this.boards.push(...boards);
                } else {
                    console.log(`[loadBoards] Setting boards list to ${boards.length} boards`);
                    this.boards = boards;
                }

                // Track pagination state
                this.nextPage = data.next_page_url ? page + 1 : null;
                this.hasMoreBoards = !!data.next_page_url;
                console.log('[loadBoards] nextPage:', this.nextPage, 'hasMoreBoards:', this.hasMoreBoards);

                // Debug info
                console.table(boards.map(b => ({
                    id: b.id,
                    favorite: b.favorite,
                    files: b.files.length,
                    viewerId
                })));

                boards.forEach(b => {
                    console.groupCollapsed(`[Board #${b.id}] ${b.title ?? ''}`.trim());
                    console.log('Viewer ID:', viewerId ?? '(unknown)');
                    console.log('Favorite:', b.favorite);
                    console.log('Files:', b.files);
                    console.groupEnd();
                });

            } catch (error) {
                console.error('[loadBoards] Failed to load boards:', error);
            } finally {
                const t1 = performance.now();
                console.log(`[loadBoards] Finished in ${(t1 - t0).toFixed(0)} ms`);
                console.groupEnd();
                this.loading = false;
            }
        },

        get filteredBoards() {
            return this.boards.filter(board => {
                const moodMatch = this.selectedMoods.length === 0 || this.selectedMoods.includes(board.latest_mood);
                const typeMatch = this.selectedMediaTypes.length === 0 || this.selectedMediaTypes.includes(board.media_type);
                const searchMatch = this.searchQuery.trim() === '' || (
                    board.title?.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                    board.description?.toLowerCase().includes(this.searchQuery.toLowerCase())
                );
                return moodMatch && typeMatch && searchMatch;
            });
        },

        async handleScroll() {
            console.groupCollapsed('[handleScroll] Scroll event triggered');
            if (this.loading) {
                console.log('[handleScroll] Already loading, aborting.');
                console.groupEnd();
                return;
            }
            if (!this.hasMoreBoards) {
                console.log('[handleScroll] No more boards to load, aborting.');
                console.groupEnd();
                return;
            }

            const scrollContainer = document.getElementById('moodboards-scroll-container');
            if (!scrollContainer) {
                console.error('[handleScroll] Scroll container not found!');
                console.groupEnd();
                return;
            }

            const scrollPosition = scrollContainer.scrollTop + scrollContainer.clientHeight;
            const threshold = scrollContainer.scrollHeight - 100;

            console.log('[handleScroll] scrollPosition:', scrollPosition, 'threshold:', threshold);

            if (scrollPosition >= threshold) {
                console.log('[handleScroll] Near bottom, loading next page:', this.nextPage);
                await this.loadBoards(this.nextPage, 10, true);
            } else {
                console.log('[handleScroll] Not near bottom, no action.');
            }
            console.groupEnd();
        },

        async refreshUserFilesView() {
            console.log("🔄 Refreshing user file view...");
            this.fileOffset = 0;
            this.hasMoreFiles = true;
            this.userFiles = [];

            await this.loadUserFiles(true);
            console.log("📊 Counts refreshed after file reload");
        },
        
        updateFileCounts(data) {
            this.imageCount = data.imageCount ?? 0;
            this.videoCount = data.videoCount ?? 0;

            console.log("📊 File summary updated:", {
                imageCount: this.imageCount,
                videoCount: this.videoCount,
                totalCount: this.imageCount + this.videoCount
            });
        },

        openCopyModal() {
            if (this.selectedFileIds.length === 0) return;
            this.targetListId = '';
            this.showCopyModal = true;
            this.showBulkMenu = false;
        },

        openMoveModal() {
            if (this.selectedFileIds.length === 0) return;
            this.targetListId = '';
            this.showMoveModal = true;
            this.showBulkMenu = false;
        },

        openDeleteModal() {
            if (this.selectedFileIds.length === 0) return;
            this.showDeleteFilesModal = true;
            this.showBulkMenu = false;
        },

        toggleFileSelection(id) {
            if (this.selectedFileIds.includes(id)) {
                this.selectedFileIds = this.selectedFileIds.filter(f => f !== id);
            } else {
                this.selectedFileIds.push(id);
            }
        },

        async submitCopyToList() {
            console.log("🚀 Attempting to copy files...");
            console.log("🔍 Selected File IDs:", this.selectedFileIds);
            console.log("🎯 Target List ID:", this.targetListId);

            if (!this.targetListId || this.selectedFileIds.length === 0) {
                console.warn("⚠️ Copy aborted: missing targetListId or selected files");
                return;
            }

            try {
                console.log("📡 Sending request to attach endpoint...");
                const res = await fetch(`/file-lists/${this.targetListId}/attach`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ file_ids: this.selectedFileIds }),
                });

                console.log("📬 Response received:", res);
                if (!res.ok) {
                    console.error("❌ Server responded with error status:", res.status);
                    throw new Error("Failed to copy");
                }

                console.log("✅ Files successfully copied to list", this.targetListId);

                // Cleanup and UI reset
                console.log("🧹 Resetting UI state after copy...");
                this.showCopyModal = false;
                this.targetListId = null;
                this.selectedFileIds = [];

                // 🔄 Refresh file view
                this.refreshUserFilesView();
                console.log("🎉 Copy flow complete");

            } catch (err) {
                console.error("🔥 Error during copy flow:", err);
            }
        },

        async submitMoveToList() {
            console.log("🚚 Starting move flow...");
            console.log("📦 Selected File IDs:", this.selectedFileIds);
            console.log("🎯 Target List ID:", this.targetListId);
            console.log("📂 Current List ID:", this.activeListId);

            if (!this.targetListId || this.selectedFileIds.length === 0) {
                console.warn("⚠️ Move aborted: missing target or files");
                return;
            }

            try {
                console.log("🔗 Attaching files to target list...");
                await fetch(`/file-lists/${this.targetListId}/attach`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ file_ids: this.selectedFileIds })
                });

                console.log("🧹 Detaching files from current list...");
                await fetch(`/file-lists/${this.activeListId}/detach`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ file_ids: this.selectedFileIds })
                });

                console.log("✅ Files successfully moved");

                // 🧼 Clean up modal and selection state
                this.showMoveModal = false;
                this.targetListId = null;
                this.selectedFileIds = [];

                // 🔄 Refresh file view immediately
                this.refreshUserFilesView();
                console.log("🎉 Move flow complete");

            } catch (err) {
                console.error("🔥 Error during move flow:", err);
            }
        },

        async submitDeleteFiles() {
            console.log("🗑️ Starting delete flow...");
            console.log("📂 Active List ID:", this.activeListId);
            console.log("🧺 Selected File IDs:", this.selectedFileIds);

            if (this.selectedFileIds.length === 0) {
                console.warn("⚠️ Delete aborted: no files selected");
                return;
            }

            try {
                const res = await fetch(`/user-files/delete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ 
                        file_ids: this.selectedFileIds,
                        list_id: this.activeListId
                    }),
                });

                console.log("📬 Response received:", res);
                if (!res.ok) {
                    console.error("❌ Server responded with error status:", res.status);
                    throw new Error("Delete failed");
                }

                if (this.activeListId === 'all') {
                    console.log("🧨 Files were fully deleted from DB");
                } else {
                    console.log("🧹 File entries removed from list view only");
                }

                // 🧼 Cleanup UI
                this.userFiles = this.userFiles.filter(f => !this.selectedFileIds.includes(f.id));
                this.selectedFileIds = [];
                this.showDeleteFilesModal = false;

                // 🔄 Refresh file view
                this.refreshUserFilesView();
                console.log("🎉 Delete flow complete");

            } catch (err) {
                console.error("🔥 Error during delete flow:", err);
            }
        },

        startEditingList(list) {
            console.log("📝 Starting edit for", list.name);
            this.editingList = list;
            this.listEditName = list.name;
            this.showRenameModal = true;
            this.activeMenu = null;
        },

        cancelRename() {
            this.editingList = null;
            this.listEditName = '';
            this.showRenameModal = false;
        },

        async submitRename() {
            console.log("✏️ Attempting to rename list...");
            console.log("🆕 New name:", this.listEditName);
            console.log("🗂️ Editing list ID:", this.editingList.id);

            if (!this.listEditName.trim()) {
                console.warn("⚠️ Rename aborted: name is empty");
                return;
            }

            try {
                console.log("📡 Sending PUT request to rename endpoint...");
                const res = await fetch(`/file-lists/${this.editingList.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ name: this.listEditName.trim() }),
                });

                console.log("📬 Response received:", res);
                if (!res.ok) {
                    console.error("❌ Server responded with error status:", res.status);
                    throw new Error("Failed to rename");
                }

                // 🧠 Update local list name
                this.editingList.name = this.listEditName.trim();
                this.showRenameModal = false;

                console.log("✅ Successfully renamed list:", this.editingList.id);

                // 🔄 Refresh view to reflect new name
                this.refreshUserFilesView();
                console.log("🎉 Rename flow complete");

            } catch (err) {
                console.error("🔥 Error during rename flow:", err);
            }
        },

        cancelDelete() {
            this.listToDelete = null;
            this.showDeleteModal = false;
        },

        async submitDelete() {
            console.log("🗑️ Starting list deletion flow...");
            console.log("📂 List to delete:", this.listToDelete);

            try {
                console.log("📡 Sending DELETE request to list endpoint...");
                const res = await fetch(`/file-lists/${this.listToDelete.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                console.log("📬 Response received:", res);
                if (!res.ok) {
                    console.error("❌ Server responded with error status:", res.status);
                    throw new Error("Failed to delete");
                }

                // 🧼 Update local list state
                this.fileLists = this.fileLists.filter(list => list.id !== this.listToDelete.id);
                console.log("🧹 Removed from local list state:", this.listToDelete.id);

                // 🧭 Reset active list if needed
                if (this.activeListId === this.listToDelete.id) {
                    this.activeListId = 'all';
                    this.activeList = 'all';
                    console.log("🔄 Active list reset to 'all'");
                }

                // 🔄 Refresh file view immediately
                this.refreshUserFilesView();

                // ✅ Cleanup modal and selection
                this.listToDelete = null;
                this.showDeleteModal = false;
                console.log("🎉 List deletion complete");

            } catch (err) {
                console.error("🔥 Error during list deletion:", err);
            }
        },

        openListMenu(id) {
            this.activeMenu = this.activeMenu === id ? null : id;
        },

        init() {
            // Optionally, load the default tab's data
            if (this.activeTab === 'moodboards') this.loadBoards();
            if (this.activeTab === 'teasers') this.fetchTeasers();
            if (this.activeTab === 'files') {
                this.loadFileLists();
                this.loadUserFiles();
            }

            const scrollContainer = document.getElementById('moodboards-scroll-container');
            if (scrollContainer) {
                scrollContainer.addEventListener('scroll', this.handleScroll.bind(this));
                console.log('[init] Scroll handler attached to moodboards-scroll-container');
            }

            // Watch for tab changes
            this.$watch('activeTab', (tab) => {
                if (tab === 'moodboards') this.loadBoards();
                if (tab === 'teasers') this.fetchTeasers();
                if (tab === 'files') {
                    this.loadFileLists();
                    this.loadUserFiles(true); // true = reset
                }
            });
        },

        // Fetch function
       async fetchTeasers() {
            this.loadingTeasers = true;
            try {
                console.log('[fetchTeasers] Fetching teasers from /my-teasers...');
                const res = await fetch('/my-teasers', {
                    headers: { 'Accept': 'application/json' }
                });

                console.log('[fetchTeasers] Response status:', res.status);

                if (!res.ok) {
                    const text = await res.text().catch(() => '');
                    console.error(`[fetchTeasers] Server responded with error: ${res.status}`, text);
                    throw new Error(`Failed to fetch teasers: ${res.status}`);
                }

                const data = await res.json();
                console.log('[fetchTeasers] Teasers data received:', data);

                this.teasers = data.teasers || [];
            } catch (err) {
                console.error('[fetchTeasers] Failed to fetch teasers:', err);
                this.teasers = [];
                this.showToast('Could not load teasers. Please try again.', 'error');
            } finally {
                this.loadingTeasers = false;
                console.log('[fetchTeasers] Loading state set to false');
            }
        },

        async loadFileLists() {
            const res = await fetch('/file-lists');
            const lists = await res.json();
            this.fileLists = [];

            for (const list of lists) {
                const statsRes = await fetch(`/file-lists/${list.id}/stats`);
                const stats = await statsRes.json();

                this.fileLists.push({
                    ...list,
                    imageCount: stats.imageCount,
                    videoCount: stats.videoCount,
                    totalCount: stats.totalCount,
                });

                console.log(`📁 ${list.name}: ${stats.totalCount} files (${stats.imageCount} images, ${stats.videoCount} videos)`);
            }
        },

        async loadActiveListStats() {
            if (this.activeListId !== 'all') {
                const res = await fetch(`/file-lists/${this.activeListId}/stats`);
                this.activeListStats = await res.json();
                console.log("📊 Active List Stats:", this.activeListStats);
            } else {
                this.activeListStats = null;
            }
        },

        async submitNewList() {
            console.log("🆕 Starting list creation flow...");
            console.log("📄 New list name:", this.newListName);

            if (!this.newListName.trim()) {
                console.warn("⚠️ Create aborted: name is empty");
                return;
            }

            try {
                console.log("📡 Sending POST request to /file-lists...");
                const res = await fetch('/file-lists', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ name: this.newListName })
                });

                console.log("📬 Response received:", res);
                if (!res.ok) {
                    console.error("❌ Server responded with error status:", res.status);
                    throw new Error('Request failed');
                }

                const newList = await res.json();
                this.fileLists.push(newList);
                console.log("✅ New list created and added locally:", newList);

                this.newListName = '';
                this.showCreateListModal = false;

                // 🔄 Refresh files to reflect new list state
                this.refreshUserFilesView();
                console.log("🎉 List creation flow complete");

            } catch (err) {
                console.error("🔥 Error creating list:", err);
            }
        },

        searchFilesOrBoards() {
            const query = this.searchQuery.trim().toLowerCase();

            if (this.activeTab === 'files') {
                this.filteredFiles = this.userFiles.filter(file => {
                    return file.filename?.toLowerCase().includes(query);
                });
            }

            if (this.activeTab === 'moodboards') {
                this.filteredBoards = this.boards.filter(board => {
                    const titleMatch = board.title?.toLowerCase().includes(query);
                    const descMatch = board.description?.toLowerCase().includes(query);
                    return titleMatch || descMatch;
                });
            }
        },

        toggleMood(mood) {
            if (this.selectedMoods.includes(mood)) {
                this.selectedMoods = this.selectedMoods.filter(m => m !== mood);
            } else {
                this.selectedMoods.push(mood);
            }
        },

        toggleMediaType(type) {
            if (this.selectedMediaTypes.includes(type)) {
                this.selectedMediaTypes = this.selectedMediaTypes.filter(t => t !== type);
            } else {
                this.selectedMediaTypes.push(type);
            }
        }, 

        loadUserFiles: async function(reset = false) {
            this.loadingFiles = true;

            if (reset) {
                this.userFiles = [];
                this.fileOffset = 0;
                this.hasMoreFiles = true;
            }

            if (!this.hasMoreFiles) {
                console.log("📦 No more files to load. Skipping fetch.");
                return;
            }

            const params = new URLSearchParams({
                offset: this.fileOffset,
                limit: 20,
                type: this.fileTypeFilter,       // 'all', 'image', 'video'
                content: this.contentTypeFilter, // 'all', 'safe', 'adult'
                sort: this.sortOrder             // 'latest', 'earliest'
            });

            const isAllMode = this.activeListId === 'all' || !this.activeListId;
            const endpoint = isAllMode
                ? `/user-files?${params.toString()}`
                : `/file-lists/${this.activeListId}/items?${params.toString()}`;

            console.log(`🔍 Fetching: ${endpoint}`);

            try {
                const startTime = performance.now();
                const res = await fetch(endpoint);

                if (!res.ok) throw new Error(`Server responded with ${res.status}`);

                const data = await res.json();
                const elapsed = Math.round(performance.now() - startTime);
                const newFiles = data.files || data.items || [];

                console.log(`✅ Success! Loaded ${newFiles.length} files in ${elapsed}ms`);

                // 📊 Summary only in "all" mode
                if ('imageCount' in data && 'videoCount' in data) {
                    this.updateFileCounts(data);
                }

                this.userFiles.push(...newFiles);
                this.fileOffset += newFiles.length;
                this.loadingFiles = false;

                if (newFiles.length < 20) {
                    this.hasMoreFiles = false;
                    console.log("⛔ End of file stream reached.");
                }

            } catch (e) {
                console.error("❌ Error fetching user files:", e);
                this.hasMoreFiles = false;
                console.error("❌ Error:", e);
                this.loadingFiles = false;
            }
        },

        handleFileSelect(event) {
            const files = Array.from(event.target.files);
            this.fileNameInputs = files.map(file => file.name);
            this.previewIndex = 0;
            this.previewModal = true;
            this.selectedFiles = Array.from(event.target.files);
            this.fileNameInputs = this.selectedFiles.map(f => f.name);
            this.contentTypes = this.selectedFiles.map(() => null); // initialize empty
            this.selectedListIds = this.selectedFiles.map(() => null);
        },

        previewNext() {
            if (this.previewIndex < this.selectedFiles.length - 1) {
                this.previewIndex += 1;
            } else {
                this.previewModal = false;
                this.showTypeModal = true; // optional: show confirmation before upload
            }
        },

        get filteredFiles() {
        let files = this.userFiles.filter(file => {
            const matchesType =
            this.fileTypeFilter === 'all' ||
            (this.fileTypeFilter === 'image' && file.path.match(/\.(jpg|jpeg|png|gif|webp)$/i)) ||
            (this.fileTypeFilter === 'video' && file.path.match(/\.(mp4|mov|avi|webm)$/i));

            const matchesContent =
                this.contentTypeFilter === 'all' ||
                file.content_type === this.contentTypeFilter;

            return matchesType && matchesContent;
        });

        if (this.sortOrder === 'latest') {
            files.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        } else {
            files.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        }

        return files;
        },

        cancelUpload() {
            this.previewModal = false;
            this.showTypeModal = false;
            this.previewUrl = null;
            this.selectedFile = null;
        },

        removeCurrentPreviewFile() {
            this.selectedFiles.splice(this.previewIndex, 1);
            this.fileNameInputs.splice(this.previewIndex, 1);

            // Adjust previewIndex safely
            if (this.previewIndex >= this.selectedFiles.length) {
                this.previewIndex = Math.max(0, this.selectedFiles.length - 1);
            }

            // If no files left, cancel modal
            if (!this.selectedFiles.length) {
                this.cancelUpload();
            }
        },

        async submitFiles() {
            console.log("🚀 Starting file upload process...");
            this.uploadCount = 0;

            for (let i = 0; i < this.selectedFiles.length; i++) {
                const file = this.selectedFiles[i];
                const listId = this.selectedListIds[i];
                const filename = this.fileNameInputs[i] || file.name;
                const contentType = this.contentTypes[i] || 'safe';

                console.log(`📤 [${i + 1}/${this.selectedFiles.length}] Uploading file: ${filename}`);
                console.log(`🧾 Content type: ${contentType}`);
                if (listId) console.log(`📁 List to attach: ID ${listId}`);
                else console.log(`📂 No list selected for this file.`);

                const formData = new FormData();
                formData.append('file', file);
                formData.append('filename', filename);
                formData.append('content_type', contentType); // safe/adult
                // Determine file_type: image, video, audio, or other
                let type = 'other';
                if (file.type.startsWith('image/')) type = 'image';
                else if (file.type.startsWith('video/')) type = 'video';
                else if (file.type.startsWith('audio/')) type = 'audio';
                console.log(`📦 Detected file_type for ${filename}:`, type);
                formData.append('file_type', type);
                if (listId) formData.append('list_id', listId); // 👈 include in upload if supported

                try {
                    const uploadRes = await fetch("{{ route('files.store') }}", {
                        method: "POST",
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: formData,
                    });

                    const result = await uploadRes.json();
                    const uploadedFileId = result?.file_id;

                    if (!uploadedFileId) {
                        console.warn(`❌ Upload failed for: ${filename} (no file_id returned)`);
                        continue;
                    }

                    console.log(`✅ File uploaded: ID ${uploadedFileId}`);

                    // 📎 Optional: Attach file to list (if backend requires separate request)
                    if (listId && !formData.has('list_id')) {
                        console.log(`📎 Attaching file ID ${uploadedFileId} to list ID ${listId}...`);
                        const attachRes = await fetch(`/file-lists/${listId}/attach`, {
                            method: "POST",
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ file_id: uploadedFileId })
                        });

                        if (attachRes.ok) {
                            console.log(`🔗 Successfully attached to list ${listId}`);
                        } else {
                            console.warn(`⚠️ Failed to attach to list ${listId}`);
                        }
                    }

                    this.uploadCount += 1;
                } catch (err) {
                    console.error(`🚨 Upload failed for ${filename}:`, err);
                }
            }

            console.log(`🎉 Upload complete. Total files uploaded: ${this.uploadCount}`);
            this.uploadProgressModal = false;
            this.cancelUpload();
            this.loadUserFiles();
            this.showToast("Files uploaded ✅");
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
            return String(m).replace(/-/g, '_'); 
        },

        getReactionCount(board, mood) {
            return board[this.moodKey(mood) + '_count'] ?? 0;
        },

        react(boardId, mood) {
            const board = this.boards.find(b => b.id === boardId);
            if (!board) return this.showToast("Board not found", 'error');
            if (board.user_reacted_mood === mood) {
                this.showToast("You already picked this mood 💅", 'error');
                return;
            }

            this.showLoadingToast("Reacting...");

            fetch('/reaction', {
                method: 'POST',
                headers: this._headers(),
                body: JSON.stringify({ mood_board_id: boardId, mood })
            })
            .then(res => res.ok ? res.json() : Promise.reject())
            .then(data => {
                const newMood = data.mood;
                const prevMood = data.previous;

                board.reaction_counts = board.reaction_counts || {};

                if (prevMood && prevMood !== newMood) {
                const prevKey = this.moodKey(prevMood) + '_count';
                board.reaction_counts[prevMood] = Math.max(0, (board.reaction_counts[prevMood] || 0) - 1);
                board[prevKey] = Math.max(0, (board[prevKey] || 0) - 1);
                }

                const newKey = this.moodKey(newMood) + '_count';
                board.reaction_counts[newMood] = (board.reaction_counts[newMood] || 0) + 1;
                board[newKey] = (board[newKey] || 0) + 1;

                board.user_reacted_mood = newMood;

                this.showToast("Mood updated! 💖");
            })
            .catch(() => this.showToast("Failed to react 💔", 'error'));
        },

        postComment(board) {
            if (!board.newComment.trim()) return;

            this.showLoadingToast("Commenting...");

            fetch(`/boards/${board.id}/comments`, {
                method: 'POST',
                headers: this._headers(),
                body: JSON.stringify({ body: board.newComment.trim() })
            })
            .then(res => res.ok ? res.json() : Promise.reject())
            .then(() => {
                board.comment_count += 1;
                board.newComment = '';
                this.showToast("Comment posted! 🎉");
            })
            .catch(() => this.showToast("Comment failed 😢", 'error'));
        },

        isSendDisabled(board) {
            return !board.newComment || board.newComment.trim() === '';
        },

        renderMediaPreview(board) {
            const container = document.getElementById(`media-preview-${board.id}`);
            if (!container) return;

            const mediaPath = board.image || board.video;
            if (!mediaPath) return;

            const fullPath = mediaPath.startsWith('http') ? mediaPath : `/storage/${mediaPath}`;
            const ext = fullPath.split('.').pop().toLowerCase();

            if (["mp4", "webm", "ogg"].includes(ext)) {
                const video = document.createElement('video');
                video.src = fullPath;
                video.playsInline = true;
                video.preload = "metadata";
                video.className = "w-full h-full object-cover rounded-lg";
                video.muted = true;
                video.autoplay = true;
                video.loop = true;

                container.appendChild(video);
            } else if (["jpg", "jpeg", "png", "gif", "webp"].includes(ext)) {
                const img = document.createElement('img');
                img.src = fullPath;
                img.alt = "Moodboard Image";
                img.className = "w-full h-full rounded-lg border-2 border-blue-500 object-cover";
                container.appendChild(img);
            }
        },

        _headers() {
            return {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
            };
        },

        showToast(message = "Done!", type = 'success', delay = 3000) {
            const box = document.getElementById('toastBox');
            const msg = document.getElementById('toastMessage');

            msg.textContent = message;
            msg.className = `px-4 py-2 rounded shadow-lg text-white text-sm font-medium ${type === 'error' ? 'bg-red-500' : 'bg-green-500'}`;

            box.classList.remove('hidden');
            setTimeout(() => box.classList.add('hidden'), delay);
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
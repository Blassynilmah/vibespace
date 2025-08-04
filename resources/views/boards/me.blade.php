@extends('layouts.app')

@section('content')
<div x-data="meBoards()" x-init="loadBoards">


<div class="max-w-7xl mx-auto flex gap-8 px-2 sm:px-4 pb-8 pt-4">     
     
    <!-- 📁 Left Sidebar -->
        <div class="hidden lg:block w-1/5 pr-2">
            <template x-if="activeTab === 'moodboards'">
                <div class="hidden lg:block w-1/5">
                    <div class="sticky top-24">

                        <!-- 😎 Mood Filters -->
                        <h3 class="text-base font-semibold mb-3 text-gray-700">Mood Filters</h3>
                        <div class="flex flex-col gap-1.5 mb-6">
                            <template x-for="(emoji, mood) in moods" :key="mood">
                                <button
                                    @click="toggleMood(mood)"
                                    class="w-full px-3 py-1.5 rounded-lg text-xs font-medium transition-all inline-flex items-center gap-2 text-left"
                                    :class="selectedMoods.includes(mood) 
                                        ? 'bg-pink-500 text-white shadow-sm' 
                                        : 'bg-gray-100 hover:bg-gray-200 text-gray-800'">
                                    <span x-text="emoji"></span>
                                    <span x-text="mood.charAt(0).toUpperCase() + mood.slice(1)"></span>
                                </button>
                            </template>
                        </div>

                        <!-- 📺 Media Type -->
                        <h3 class="text-base font-semibold mb-3 mt-8 text-gray-700">Media Type</h3>
                        <div class="flex flex-col gap-1.5">
                            <template x-for="(label, type) in mediaTypes" :key="type">
                                <button
                                    @click="toggleMediaType(type)"
                                    class="w-full px-3 py-1.5 rounded-lg text-xs font-semibold transition-all inline-flex items-center gap-2 text-left"
                                    :class="selectedMediaTypes.includes(type) 
                                        ? 'bg-pink-600 text-white shadow-sm' 
                                        : 'bg-gray-100 hover:bg-gray-200 text-gray-800'">
                                    <span x-text="label.split(' ')[0]"></span>
                                    <span x-text="label.split(' ').slice(1).join(' ')"></span>
                                </button>
                            </template>
                        </div>

                    </div>
                </div>
            </template>

            <template x-if="activeTab === 'files'">
                <div class="border border-gray-200 rounded-xl overflow-hidden flex flex-col h-full">

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

        <div class="sticky top-0 z-99 bg-gradient-to-r from-pink-500 to-purple-600 px-4 py-3 shadow-sm text-white">
        <div class="flex items-center gap-3 justify-between">
            <!-- 🔍 Search -->
            <input type="text" x-model.debounce.300ms="searchQuery" @input="searchFilesOrBoards" :placeholder="activeTab === 'moodboards' ? 'Search your moodboards...' : 'Search your content...'" class="flex-1 px-4 py-2 rounded-full border border-white/30 bg-white/20 placeholder-white text-sm text-white focus:outline-none"/>
            <!-- ➕ Create Button -->
            <template x-if="activeTab === 'moodboards'">
                <a href="{{ route('boards.create') }}"
                        class="group relative h-10 rounded-full bg-white text-pink-600 overflow-hidden shadow transition-[width,opacity] duration-700 ease-in-out w-10 hover:w-44 whitespace-nowrap">
                    <span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-lg group-hover:opacity-0">+</span>
                    <span class="pl-10 pr-4 opacity-0 group-hover:opacity-100 text-sm">New Moodboard</span>
                </a>
            </template>

            <template x-if="activeTab === 'files'">
                <button type="button"
                        @click.prevent="document.getElementById('fileInput').click()"
                        class="group relative h-10 rounded-full bg-white text-pink-600 overflow-hidden shadow transition-[width,opacity] duration-700 ease-in-out w-10 hover:w-44 whitespace-nowrap">
                    <span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-lg group-hover:opacity-0">+</span>
                    <span class="pl-10 pr-4 opacity-0 group-hover:opacity-100 text-sm">New File</span>
                </button>
            </template>

            <!-- 📱 Mobile: Always show filter button -->
            <div class="lg:hidden">
                <button @click="showMobileFilters = true"
                    class="group relative h-10 rounded-full bg-white text-pink-600 overflow-hidden shadow transition-[width,opacity] duration-700 ease-in-out w-10 hover:w-44 whitespace-nowrap">
                    <span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-lg group-hover:opacity-0">🧰</span>
                    <span class="pl-10 pr-4 opacity-0 group-hover:opacity-100 text-sm">Filters</span>
                </button>
            </div>

            <!-- 💻 Desktop: Show only for moodboards -->
            <template x-if="activeTab === 'moodboards'">
                <div class="hidden lg:block">
                    <button @click="showMobileFilters = true"
                        class="group relative h-10 rounded-full bg-white text-pink-600 overflow-hidden shadow transition-[width,opacity] duration-700 ease-in-out w-10 hover:w-44 whitespace-nowrap">
                        <span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-lg group-hover:opacity-0">🧰</span>
                        <span class="pl-10 pr-4 opacity-0 group-hover:opacity-100 text-sm">Filters</span>
                    </button>
                </div>
            </template>
        </div>
    </div>

    <div class="lg:hidden sticky top-[60px] z-60 bg-white border-t border-b border-gray-200 shadow-sm">
        <div class="flex justify-around px-2 py-2 text-sm text-gray-700">
            <a href="{{ route('home') }}" class="flex flex-col items-center hover:text-pink-600">🏠<span class="text-xs mt-1">Home</span></a>
            <a href="/messages" class="flex flex-col items-center hover:text-pink-600">💌<span class="text-xs mt-1">Messages</span></a>
            <a href="{{ route('boards.me') }}" class="flex flex-col items-center text-pink-600 font-semibold">💫<span class="text-xs mt-1">Me</span></a>
            <a href="/notifications" class="flex flex-col items-center hover:text-pink-600">🔔<span class="text-xs mt-1">Alerts</span></a>
            <a href="/settings" class="flex flex-col items-center hover:text-pink-600">⚙️<span class="text-xs mt-1">Settings</span></a>
        </div>
    </div>
        
        {{-- 🧭 Tab Navigation --}}
        <div class="flex items-center justify-between px-2 sm:px-0">
            <div class="flex gap-4">
                <button
                    @click="activeTab = 'moodboards'"
                    :class="activeTab === 'moodboards' ? 'text-gray-800 font-semibold border-b-2 border-pink-500' : 'text-gray-400'"
                    class="text-base sm:text-lg pb-1 transition">
                    💖 My Moodboards
                </button>

                <button
                    @click="activeTab = 'files'"
                    :class="activeTab === 'files' ? 'text-gray-800 font-semibold border-b-2 border-pink-500' : 'text-gray-400'"
                    class="text-base sm:text-lg pb-1 transition">
                    📁 My Files
                </button>
            </div>
        </div>

        {{-- 🎨 Moodboards Feed --}}
        <template x-if="activeTab === 'moodboards'">
            <div class="flex flex-col gap-8">
                <template x-for="board in filteredBoards" :key="board.id + '-' + board.created_at">
                    <div class="bg-white rounded-2xl shadow hover:shadow-lg transition-all p-4 sm:p-5 group">

                        {{-- Header --}}
                        <div class="flex items-center justify-between gap-2 p-3 sm:p-4 flex-wrap">
                            <div class="flex items-center gap-1 sm:gap-2 min-w-0">
                                <h3 class="text-base sm:text-lg font-semibold" x-text="board.title"></h3>
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

                            <div class="flex items-center gap-2">
                                <img :src="board.user?.profile_picture
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
                        <div class="px-3 sm:px-4 text-[0.65rem] sm:text-xs text-gray-400 mb-1"
                             x-text="timeSince(board.created_at)"></div>

                        {{-- Description --}}
                        <p class="text-gray-600 text-sm sm:text-base px-3 sm:px-4 mb-3 line-clamp-3"
                           x-text="board.description"></p>

                        {{-- Media Preview --}}
                        <div class="mt-3 mx-auto max-w-[400px] max-h-[600px] rounded-lg overflow-hidden"
                             :id="'media-preview-' + board.id"></div>

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

                <template x-if="!loading && !boards.length">
                    <div class="text-center text-gray-400 mt-10">No moodboards found 🫠</div>
                </template>

                <template x-if="loading">
                    <div class="text-center text-gray-500 mt-10 animate-pulse">Loading your vibes...</div>
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
                <div class="hidden lg:block sticky top-0 z-10 bg-gradient-to-b from-white to-gray-50 border-b border-gray-200 px-4 py-3 flex flex-wrap gap-4 items-center text-sm font-medium text-gray-700 shadow-sm">

                    <div class="flex flex-nowrap items-center gap-2 min-w-max">
                        <!-- 🗂 Media Type Filter -->
                        <button
                            @click="fileTypeFilter = 'all'; loadUserFiles(true)"
                            :class="fileTypeFilter === 'all' ? 'bg-sky-500 text-white shadow' : baseFilterClass"
                            class="filter-pill">📂 All</button>

                        <button
                            @click="fileTypeFilter = 'image'; loadUserFiles(true)"
                            :class="fileTypeFilter === 'image' ? 'bg-purple-500 text-white shadow' : baseFilterClass"
                            class="filter-pill">🖼️ Images</button>

                        <button
                            @click="fileTypeFilter = 'video'; loadUserFiles(true)"
                            :class="fileTypeFilter === 'video' ? 'bg-rose-500 text-white shadow' : baseFilterClass"
                            class="filter-pill">🎬 Videos</button>

                        <!-- 🔐 Content Type Filter -->
                        <button
                            @click="contentTypeFilter = 'all'; loadUserFiles(true)"
                            :class="contentTypeFilter === 'all' ? 'bg-indigo-500 text-white shadow' : baseFilterClass"
                            class="filter-pill">🌐 All</button>

                        <button
                            @click="contentTypeFilter = 'safe'; loadUserFiles(true)"
                            :class="contentTypeFilter === 'safe' ? 'bg-green-500 text-white shadow' : baseFilterClass"
                            class="filter-pill">✅ Safe</button>

                        <button
                            @click="contentTypeFilter = 'adult'; loadUserFiles(true)"
                            :class="contentTypeFilter === 'adult' ? 'bg-red-500 text-white shadow' : baseFilterClass"
                            class="filter-pill">🔞 Adult</button>

                        <!-- ⏱ Sort Filter -->
                        <button
                            @click="sortOrder = 'latest'; loadUserFiles(true)"
                            :class="sortOrder === 'latest' ? 'bg-amber-500 text-white shadow' : baseFilterClass"
                            class="filter-pill">⬆️ Latest</button>

                        <button
                            @click="sortOrder = 'earliest'; loadUserFiles(true)"
                            :class="sortOrder === 'earliest' ? 'bg-teal-500 text-white shadow' : baseFilterClass"
                            class="filter-pill">⬇️ Earliest</button>

                        <!-- 📥 Bulk Actions -->
                        <div class="ml-auto relative">
                            <button @click="showBulkMenu = !showBulkMenu"
                                    class="bg-white border border-gray-300 rounded-lg px-3 py-1 text-sm hover:bg-pink-50 flex items-center gap-2">
                                ⚙️
                                <span class="text-xs text-gray-500" x-show="selectedFileIds.length">(<span x-text="selectedFileIds.length"></span>)</span>
                            </button>
                            <template x-if="showBulkMenu">
                                <div class="absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded shadow-md z-50 text-sm">
                                    <!-- 📄 Copy -->
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
                            </template>
                        </div>
                    </div>
                </div>

                <!-- 📂 Scrollable File Grid -->
                <div class="overflow-y-auto flex-1 min-h-[28rem] relative">
                    <button 
                        @click="showListPanel = true"
                        class="absolute top-3 left-[-12px] bg-white border rounded-full p-2 text-pink-600 shadow hover:bg-pink-50 transition z-50"
                        title="Open list tools"
                        >
                        ➡️
                        </button>
                    <template x-if="loadingFiles">
                        <div class="absolute inset-0 flex items-center justify-center text-gray-500 text-sm font-medium">
                            ⏳ Loading your files...
                        </div>
                    </template>

                    <div class="px-4 py-4 sm:px-6 md:px-8 lg:px-10 flex flex-wrap justify-center gap-4">
                        <template x-for="(file, index) in filteredFiles" :key="index">
                            <div
                                @click="if (!$event.target.closest('.no-preview')) focusedFile = file; fullPreviewOpen = true"
                                class="flex-shrink-0 w-[8rem] h-[8rem] sm:w-[9rem] sm:h-[9rem] md:w-[9.5rem] md:h-[9.5rem] bg-white border border-gray-300 rounded-xl overflow-hidden relative hover:scale-[1.05] transition shadow-sm group"
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
                <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
                    <div class="bg-white rounded-xl p-6 w-[700px] max-w-[90%] max-h-[90vh] overflow-y-auto flex flex-col items-center gap-6 relative">
                        <h2 class="text-lg font-semibold text-gray-800">Preview File</h2>

                        <!-- 🔢 File Summary -->
                        <div class="flex gap-4 text-sm text-gray-700 font-medium">
                            <div class="flex items-center gap-1">
                                🖼️ <span x-text="selectedFiles.filter(f => f.type?.startsWith('image/')).length"></span>
                            </div>
                            <div class="flex items-center gap-1">
                                🎬 <span x-text="selectedFiles.filter(f => f.type?.startsWith('video/')).length"></span>
                            </div>
                            <div class="flex items-center gap-1">
                                📦 <span x-text="selectedFiles.length + ' total'"></span>
                            </div>
                        </div>

                        <!-- 🔐 Content Type Select -->
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

                        <!-- 🎨 File Preview -->
                        <template x-if="selectedFiles[previewIndex]?.type?.startsWith('image/')">
                            <img :src="URL.createObjectURL(selectedFiles[previewIndex])"
                                class="rounded max-h-[400px] object-contain w-full">
                        </template>
                        <template x-if="selectedFiles[previewIndex]?.type?.startsWith('video/')">
                            <video :src="URL.createObjectURL(selectedFiles[previewIndex])"
                                controls class="rounded max-h-[400px] object-contain w-full"></video>
                        </template>

                        <!-- ✏️ Rename Input -->
                        <input type="text" class="border px-3 py-1.5 rounded w-full text-sm"
                            placeholder="Enter new file name (optional)"
                            x-model="fileNameInputs[previewIndex]">

                            <!-- 📁 Select Target List -->
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

                        <!-- ✅ Continue Button -->
                        <button @click="previewModal = false; uploadProgressModal = true; submitFiles()"
                                class="mt-4 px-4 py-2 rounded bg-pink-500 text-white hover:bg-pink-600">
                            Continue
                        </button>
                        <template x-if="selectedFiles.length > 1">
                            <button @click="previewIndex = (previewIndex - 1 + selectedFiles.length) % selectedFiles.length"
                                    class="absolute left-2 top-1/2 transform -translate-y-1/2 bg-white p-2 rounded-full shadow z-10">
                                ◀️
                            </button>
                            <button @click="previewIndex = (previewIndex + 1) % selectedFiles.length"
                                    class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-white p-2 rounded-full shadow z-10">
                                ▶️
                            </button>
                        </template>
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


            <template x-if="fullPreviewOpen">
                <div class="fixed inset-0 bg-black/80 z-50 flex items-center justify-center">
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

            <template x-if="activeTab === 'files'">
                <div class="space-y-4">
                    <!-- 🖼️ Type -->
                    <div>
                        <h4 class="text-sm font-semibold mb-2 text-gray-600">File Type</h4>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="type in ['all','image','video']" :key="type">
                                <button @click="fileTypeFilter = type"
                                    class="px-3 py-1 rounded-full text-xs font-medium transition"
                                    :class="fileTypeFilter === type
                                        ? 'bg-pink-500 text-white shadow'
                                        : 'bg-white border border-pink-300 text-pink-700 hover:bg-pink-50'">
                                    <span x-text="type === 'all' ? 'All' : type === 'image' ? '🖼️ Image' : '🎬 Video'"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- 🔐 Content -->
                    <div>
                        <h4 class="text-sm font-semibold mb-2 text-gray-600">Content Type</h4>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="ctype in ['all','safe','adult']" :key="ctype">
                                <button @click="contentTypeFilter = ctype"
                                    class="px-3 py-1 rounded-full text-xs font-medium transition"
                                    :class="contentTypeFilter === ctype
                                        ? ctype === 'safe'
                                            ? 'bg-green-600 text-white shadow'
                                            : ctype === 'adult'
                                                ? 'bg-red-600 text-white shadow'
                                                : 'bg-pink-500 text-white shadow'
                                        : ctype === 'safe'
                                            ? 'bg-white border border-green-300 text-green-700 hover:bg-green-50'
                                            : ctype === 'adult'
                                                ? 'bg-white border border-red-300 text-red-700 hover:bg-red-50'
                                                : 'bg-white border border-pink-300 text-pink-700 hover:bg-pink-50'">
                                    <span x-text="ctype === 'safe' ? '✅ Safe' : ctype === 'adult' ? '🔞 Adult' : '🌐 All'"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- 🕰️ Sort -->
                    <div>
                        <h4 class="text-sm font-semibold mb-2 text-gray-600">Sort</h4>
                        <div class="flex flex-wrap gap-2">
                            <button @click="sortOrder = 'latest'"
                                    :class="sortOrder === 'latest' ? 'bg-pink-500 text-white shadow' : 'bg-white border border-pink-300 text-pink-700 hover:bg-pink-50'"
                                    class="px-3 py-1 rounded-full text-xs font-medium">⬆️ Latest</button>
                            <button @click="sortOrder = 'earliest'"
                                    :class="sortOrder === 'earliest' ? 'bg-pink-500 text-white shadow' : 'bg-white border border-pink-300 text-pink-700 hover:bg-pink-50'"
                                    class="px-3 py-1 rounded-full text-xs font-medium">⬇️ Earliest</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

    <div 
  x-show="showListPanel"
  @click.outside="showListPanel = false"
  class="fixed top-[5%] left-0 w-[80vw] h-[90vh] z-[100] bg-white shadow-xl rounded-r-2xl overflow-y-auto border-r border-gray-300 transition-all"
  x-transition:enter="transform ease-out duration-300"
  x-transition:enter-start="-translate-x-full"
  x-transition:enter-end="translate-x-0"
  x-transition:leave="transform ease-in duration-200"
  x-transition:leave-start="translate-x-0"
  x-transition:leave-end="-translate-x-full"
>
  <!-- 🧰 Your list editing content goes here -->
  <div class="p-4">
    <h2 class="text-lg font-bold text-pink-600">🗂 My lists</h2>

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
    <!-- Put interactive list components, inputs, filters, etc. here -->
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
</div>
<style>
    .filter-pill {
        transition: all 0.2s ease;
    }
</style>
@endsection


@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('meBoards', () => ({
        activeTab: 'moodboards',
            boards: [],
            loading: true,
            selectedMoods: [],
            selectedMediaTypes: [],

            moods: {
                relaxed: "😌",
                craving: "🤤",
                hyped: "🔥",
                obsessed: "🫠"
            },

            mediaTypes: {
                image: '🖼️ Image',
                video: '🎬 Video',
            },

        previewModal: false,
        previewUrl: null,
        selectedFile: null,
        fileNameInput: '',
        uploadProgressModal: false,
        uploading: false,
        uploadProgress: 0,

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
            this.targetListId = null;
            this.showCopyModal = true;
            this.showBulkMenu = false;
        },

        openMoveModal() {
            if (this.selectedFileIds.length === 0) return;
            this.targetListId = null;
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
            this.loadBoards();
            this.loadUserFiles()
            this.loadFileLists();
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
                formData.append('content_type', contentType);
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

        async loadBoards() {
            try {
                const res = await fetch('/api/boards/me');
                const data = await res.json();

                this.boards = data.map(board => ({
                    ...board,
                    newComment: '',
                    comment_count: board.comment_count ?? 0
                }));

                setTimeout(() => {
                    this.boards.forEach(board => this.renderMediaPreview(board));
                }, 0);
            } catch (error) {
                console.error("Failed to load boards", error);
            } finally {
                this.loading = false;
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

        getReactionCount(board, mood) {
            return board[mood + '_count'] ?? 0;
        },

        react(boardId, mood) {
            const board = this.boards.find(b => b.id === boardId);
            if (!board || board.user_reacted_mood === mood) {
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
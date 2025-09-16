@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto flex gap-8 px-2 sm:px-4 pb-0" x-data="vibeFeed" x-init="init">
    <!-- Global Loading Spinner Overlay -->
<div 
    x-show="initialLoading"
    style="position: fixed; inset: 0; z-index: 9999; background: rgba(255,255,255,0.85); display: flex; align-items: center; justify-content: center;"
    x-transition.opacity
>
    <div class="flex flex-col items-center">
        <span class="animate-spin text-6xl text-pink-500">⏳</span>
        <span class="mt-4 text-lg font-semibold text-pink-600">Loading...</span>
    </div>
</div>
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
                                :disabled="loading"
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
                        :disabled="loading"
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
            <template x-for="item in filteredBoards" :key="item.type + '-' + item.id + '-' + item.created_at">  
                <div x-show="item.type === 'teaser'" class="feed-tile">
                    <div class="snap-center flex flex-col lg:flex-row bg-white border-2 border-blue-400 shadow-md hover:shadow-lg transition-all duration-300 overflow-hidden rounded-2xl" ...>

                        <!-- Video Section -->
                        <div x-show="item && item.id" class="relative w-full h-[70vh] lg:w-1/2"
                            :class="{
                                'h-[35vh]': window.innerWidth < 768,
                                'md:h-[40vh]': window.innerWidth >= 768 && window.innerWidth < 1024,
                                'lg:h-[45vh]': window.innerWidth >= 1024
                            }"
                        >
                            <template x-if="item.teaser_mood">
                                <div class="absolute top-3 left-3 z-20">
                                    <span
                                        class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold shadow text-white"
                                        :class="{
                                            'bg-orange-600': item.teaser_mood === 'hype',
                                            'bg-yellow-500': item.teaser_mood === 'funny',
                                            'bg-purple-600': item.teaser_mood === 'shock',
                                            'bg-pink-600': item.teaser_mood === 'love'
                                        }"
                                        x-text="{
                                            hype: '🔥 Hype',
                                            funny: '😂 Funny',
                                            shock: '😲 Shock',
                                            love: '❤️ Cute/Love'
                                        }[item.teaser_mood] || item.teaser_mood"
                                    ></span>
                                </div>
                            </template>

                            <video
                                x-show="!item.teaserError"
                                :src="item.video"
                                playsinline
                                data-teaser
                                loop
                                tabindex="0"
                                class="w-full h-full object-cover bg-black rounded-2xl"
                                @loadeddata="item.videoLoaded = true"
                                @play="handlePlay(item.id)"
                                @pause="handlePause(item.id)"
                                @click="togglePlay($event.target)"
                                @mousedown="startFastForward($event.target)"
                                @mouseup="stopFastForward($event.target)"
                                @touchstart="startFastForward($event.target)"
                                @touchend="stopFastForward($event.target)"
                            ></video>

                            <!-- Teaser Reactions Vertical Bar -->
                            <div class="absolute bottom-6 right-4 flex flex-col items-center gap-3 z-30">
                                <template x-for="reaction in ['fire','love','boring']" :key="reaction">
                                    <button
                                        @click.prevent="reactToTeaser(item.id, reaction)"
                                        class="flex flex-col items-center justify-center bg-white/80 hover:bg-pink-100 rounded-full shadow p-2 transition"
                                        :class="{
                                            'ring-2 ring-pink-400': item.user_teaser_reaction === reaction
                                        }"
                                    >
                                        <span x-text="{
                                            fire: '🔥',
                                            love: '❤️',
                                            boring: '😐'
                                        }[reaction]"></span>
                                        <span class="text-xs font-semibold text-gray-700" x-text="item[reaction + '_count'] || 0"></span>
                                    </button>
                                </template>
                                    <button
                                        @click="openTeaserComments(item)"
                                        class="mt-2 flex flex-col items-center justify-center bg-white/80 hover:bg-pink-100 rounded-full shadow p-2 transition"
                                        title="View Comments"
                                    >
                                        <span>💬</span>
                                        <span class="text-xs font-semibold text-gray-700" x-text="item.comment_count || 0"></span>
                                    </button>
                            </div>

                            <!-- Save Button for Teaser -->
                            <div class="absolute top-3 right-3 z-30">
                                <button
                                    @click.prevent="toggleSaveTeaser(item)"
                                    :disabled="item.saving"
                                    :class="[
                                        'px-3 py-1 rounded-full text-xs font-semibold transition-all',
                                        item.is_saved
                                            ? 'bg-green-100 text-green-700 hover:bg-green-200'
                                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
                                        item.saving ? 'opacity-50 cursor-not-allowed' : ''
                                    ]"
                                >
                                    <span x-text="item.is_saved ? '✔️ Saved' : '💾 Save'"></span>
                                </button>
                            </div>

                            <!-- Teaser Comments Modal -->
                            <template x-if="showTeaserComments && activeTeaserComments && activeTeaserComments.id === item.id">
                                <div class="absolute left-0 bottom-0 w-full h-1/2 bg-white/95 rounded-b-2xl z-40 flex flex-col shadow-2xl"
                                    style="backdrop-filter: blur(8px);">
                                    <!-- Input Field -->
                                    <div class="p-3 border-b flex items-center gap-2">
                                        <input
                                            x-model="activeTeaserComments.newComment"
                                            @keydown.enter.prevent="postTeaserComment(activeTeaserComments)"
                                            type="text"
                                            placeholder="Add a comment..."
                                            class="flex-1 px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-pink-400 text-sm"
                                        >
                                        <button
                                            @click="postTeaserComment(activeTeaserComments)"
                                            class="bg-pink-500 text-white px-4 py-2 rounded-lg font-semibold text-sm hover:bg-pink-600 transition"
                                            :disabled="!activeTeaserComments.newComment || activeTeaserComments.newComment.trim() === ''"
                                        >Send</button>
                                    </div>
                                        <!-- Close Button -->
                                        <button @click="closeTeaserComments"
                                            class="absolute top-2 right-3 text-gray-500 hover:text-pink-500 text-2xl font-bold z-50">×
                                        </button>
                                    <!-- Comments List -->
                                    <div class="flex-1 overflow-y-auto p-3 space-y-3">
                                        <template x-for="comment in (activeTeaserComments.comments || [])" :key="comment.id">
                                            <div class="bg-gray-100 rounded-lg px-3 py-2 shadow text-sm">
                                                <div class="font-semibold text-pink-600 mb-1" x-text="comment.user.username"></div>
                                                <div x-text="comment.body"></div>
                                                <div class="text-xs text-gray-400 mt-1" x-text="timeSince(comment.created_at)"></div>
                                            </div>
                                        </template>
                                        <template x-if="!activeTeaserComments.comments || activeTeaserComments.comments.length === 0">
                                            <div class="text-gray-400 text-center mt-6">No comments yet. Be the first!</div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <div x-show="!item.videoLoaded" class="absolute inset-0 flex items-center justify-center z-20">
                                <span class="animate-spin text-3xl text-white">⏳</span>
                            </div>

                            <template x-if="item.teaserError">
                                <div class="absolute inset-0 flex items-center justify-center bg-black/80 text-white text-xl font-bold">
                                    teaser error
                                </div>
                            </template>

                            <!-- Mobile Overlay -->
                            <div class="absolute bottom-0 left-0 w-full bg-gradient-to-t from-black/80 to-transparent text-white p-4 md:hidden rounded-b-2xl">
                                <div class="text-sm font-semibold mb-1">@<span x-text="item.username"></span></div>
                                <div class="text-xs text-pink-300 mb-1" x-text="item.hashtags"></div>
                                <div class="text-xs mb-1" x-text="item.description"></div>
                                <div class="flex items-center gap-2 text-xs text-gray-200">
                                    <span x-text="timeSince(item.created_at)"></span>
                                    <span>•</span>
                                    <span x-text="getRemainingTime(item.expires_on)"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Info Section (Desktop) -->
                        <div class="hidden lg:flex flex-1 flex-col justify-between p-8">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="font-semibold text-pink-600">@<span x-text="item.username"></span></span>
                                    <span class="text-xs text-gray-400" x-text="timeSince(item.created_at)"></span>
                                </div>
                                <div class="mb-2">
                                    <span class="inline-block bg-pink-100 text-pink-700 rounded-full px-2 py-0.5 text-xs font-medium" x-text="item.hashtags"></span>
                                </div>
                                <div class="text-sm text-gray-700 mb-2" x-text="item.description"></div>
                            </div>
                            <div class="flex flex-wrap gap-4 text-xs text-gray-500 mt-2">
                                <div>
                                    <span class="font-semibold">Time Remaining:</span>
                                    <span x-text="getRemainingTime(item.expires_on)"></span>
                                </div>
                                <div>
                                    <span class="font-semibold">Duration:</span>
                                    <span x-text="item.expires_after ? item.expires_after + ' hrs' : '—'"></span>
                                </div>
                                <div>
                                    <span class="font-semibold">Created:</span>
                                    <span x-text="new Date(item.created_at).toLocaleString()"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div x-show="item.type === 'board'"  class="feed-tile">
                    <div class="relative bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-lg transition-all duration-300 group overflow-hidden" style="transition: box-shadow .25s ease, transform .18s ease;">
                        <div 
                        class="relative flex flex-col items-start p-3 sm:p-4 lg:p-6"
                        :class="item.files?.length ? 'md:grid md:grid-cols-5 md:gap-6' : 'md:flex md:flex-col'"
                        >

                        <!-- 💾 Save Button -->
                        <div class="absolute top-3 right-3 z-10">
                            <button
                                @click.prevent="toggleSaveById(item.id)"
                                :disabled="item.saving"
                                :class="[
                                    'px-3 py-1 rounded-full text-xs font-semibold transition-all',
                                    item.is_saved
                                        ? 'bg-green-100 text-green-700 hover:bg-green-200'
                                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
                                    item.saving ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                            >
                                <span x-text="item.is_saved ? '✔️ Saved' : '💾 Save'"></span>
                            </button>
                        </div>
                            <!-- User Info, Title, Description (Top on mobile, right on desktop) -->
                            <div class="order-1 md:order-2 md:col-span-2 flex flex-col w-full mb-3 md:mb-0">
                                <!-- User Info -->
                                <div class="flex items-start gap-3 mb-2 shrink-0">
                                    <img
                                        :src="item.user?.profile_picture
                                            ? '/storage/' + item.user.profile_picture
                                            : '/storage/moodboard_images/Screenshot 2025-07-14 032412.png'"
                                        alt="User Avatar"
                                        class="w-10 h-10 sm:w-12 sm:h-12 rounded-full border-2 border-pink-300 dark:border-pink-500 object-cover"
                                        style="box-shadow: 0 2px 6px rgba(0,0,0,0.08);"
                                    >
                                    <div class="flex flex-wrap items-center text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                                        <template x-if="item.latest_mood">
                                            <span
                                                class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full"
                                                :class="{
                                                    'bg-blue-100 text-blue-700': item.latest_mood === 'excited',
                                                    'bg-orange-100 text-orange-700': item.latest_mood === 'happy',
                                                    'bg-pink-100 text-pink-700': item.latest_mood === 'chill',
                                                    'bg-purple-100 text-purple-700': item.latest_mood === 'thoughtful',
                                                    'bg-teal-100 text-teal-700': item.latest_mood === 'sad',
                                                    'bg-amber-100 text-amber-700': item.latest_mood === 'flirty',
                                                    'bg-indigo-100 text-indigo-700': item.latest_mood === 'mindblown',
                                                    'bg-yellow-100 text-yellow-700': item.latest_mood === 'love',
                                                }"
                                                style="backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); box-shadow: 0 1px 0 rgba(0,0,0,0.05);"
                                            >
                                                <span x-text="moods[item.latest_mood]"></span>
                                                <span x-text="item.latest_mood.charAt(0).toUpperCase() + item.latest_mood.slice(1)"></span>
                                                <span>Vibes</span>
                                            </span>
                                        </template>

                                        <div>
                                            <template x-if="item.user">
                                                <a 
                                                    :href="`/space/${item.user.username}-${item.user.id}`" 
                                                    class="hover:underline font-medium text-blue-600 text-xs sm:text-sm"
                                                    :title="`View ${item.user.username}'s profile`"
                                                    :aria-label="`View profile of ${item.user.username}`"
                                                    x-text="'@' + item.user.username">
                                                </a>
                                            </template>


                                            <span class="mx-1">•</span>

                                            <span x-text="timeSince(item.created_at)"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col">
                                    <h3 class="text-base sm:text-lg font-extrabold bg-gradient-to-r from-pink-500 to-purple-500 bg-clip-text text-transparent mb-1"
                                        x-text="item.title">
                                    </h3>
                                    <!-- Description -->
                                    <div x-show="item.description" class="text-sm text-black-800 dark:text-black-200 leading-snug">
                                        <p 
                                            x-text="item.expanded 
                                                ? (item.description || '') 
                                                : (item.files && item.files.length 
                                                    ? (item.description ? item.description.split(' ').slice(0, 20).join(' ') + (item.description.split(' ').length > 20 ? '...' : '') : '') 
                                                    : (item.description ? item.description.split(' ').slice(0, 200).join(' ') + (item.description.split(' ').length > 200 ? '...' : '') : '')
                                                )"
                                            class="whitespace-pre-line"
                                        ></p>
                                        <!-- More Button -->
                                        <button 
                                            x-show="!item.expanded && (
                                                (item.files && item.description && item.description.split(' ').length > 20) ||
                                                ((!item.files || !item.files.length) && item.description && item.description.split(' ').length > 200)
                                            )"
                                            @click="item.expanded = true"
                                            class="mt-1 text-pink-500 hover:underline text-xs font-medium"
                                        >
                                            More
                                        </button>
                                        <!-- Less Button -->
                                        <button 
                                            x-show="item.expanded && (
                                                (item.files && item.description && item.description.split(' ').length > 20) ||
                                                ((!item.files || !item.files.length) && item.description && item.description.split(' ').length > 200)
                                            )"
                                            @click="item.expanded = false"
                                            class="mt-1 text-pink-500 hover:underline text-xs font-medium"
                                        >
                                            Less
                                        </button>
                                    </div>
                                </div>
                                                    <!-- Desktop/Tablet reactions/comments -->
                                <div class="hidden md:block">                     
                                    <div class="hidden md:grid grid-cols-2 grid-rows-4 gap-3 mt-2 w-full p-2 bg-gray-50 dark:bg-gray-800 rounded-xl shadow-inner">
                                        <template x-for="(emoji, mood) in reactionMoods" :key="mood">
                                            <button
                                                @click.prevent="react(item.id, mood); $el.classList.add('animate-bounce'); setTimeout(()=>$el.classList.remove('animate-bounce'), 500)"
                                                x-data="{ showName: false }"
                                                @mouseenter="showName = true" 
                                                @mouseleave="showName = false"
                                                class="w-full relative rounded-lg flex flex-col items-center justify-center transition-all duration-200 hover:scale-105
                                                    px-3 py-2 text-sm font-medium"
                                                :class="[
                                                    item.user_reacted_mood === mood ? 'ring-2 ring-offset-1 ring-pink-400 shadow' : 'shadow-sm',
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
                                                        x-text="getReactionCount(item, mood)">
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
                                                x-model="item.newComment"
                                                placeholder="Type a comment..."
                                                class="flex-1 bg-transparent focus:outline-none text-xs sm:text-sm text-gray-700 dark:text-gray-200 placeholder-gray-400"
                                                @keydown.enter.prevent="postComment(item)"
                                            >
                                            <button
                                                @click.prevent="postComment(item)"
                                                class="text-pink-500 hover:text-pink-600 transition-colors text-xs sm:text-sm font-medium"
                                            >
                                                Post
                                            </button>
                                        </div>
                                        <div class="text-xs text-gray-500 flex justify-between">
                                            <span x-text="(item.comment_count ?? 0) + ' comments'"></span>
                                            <a :href="'/boards/' + item.id" class="text-pink-600 hover:underline text-sm font-medium">
                                                → View Board
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Media (Middle on mobile, left on desktop) -->
                            <div class="order-2 md:order-1 md:col-span-3 w-full">
                                <template x-if="item.files?.length">
                                    <div class="md:col-span-3">
                                        <div
                                            class="mt-3 w-full mx-auto aspect-[9/12] min-h-[220px] rounded-xl overflow-hidden flex items-center justify-center relative z-0 bg-gray-50 dark:bg-gray-800 shadow-inner"
                                            x-data="{ currentIndex: 0 }"
                                        >
                                            <!-- 🔢 File count -->
                                            <template x-if="item.files.length > 1">
                                                <div class="absolute top-2 right-3 bg-black/60 text-white text-xs px-2 py-0.5 rounded-full z-10">
                                                    <span x-text="`${currentIndex + 1} / ${item.files.length}`"></span>
                                                </div>
                                            </template>

                                            <!-- 📸 Media Preview -->
                                            <div class="flex items-center justify-center w-full h-full">
                                                <template x-if="item.files[currentIndex].type === 'image'">
                                                    <img
                                                        :src="item.files[currentIndex].path"
                                                        alt="Preview"
                                                        class="max-h-full max-w-full object-contain transition-transform duration-300 group-hover:scale-[1.03] cursor-pointer"
                                                        @click="previewBoardFile = item.files[currentIndex]; showBoardPreviewModal = true"
                                                    />
                                                </template>
                                                <template x-if="item.files[currentIndex].type === 'video'">
                                                    <div x-show="item && item.id" class="relative w-full h-full flex items-center justify-center">
                                                        <video
                                                            :src="item.files[currentIndex].path"
                                                            playsinline
                                                            preload="metadata"
                                                            data-moodboard
                                                            loop
                                                            class="max-h-full max-w-full object-contain rounded-xl transition-transform duration-300 group-hover:scale-[1.02] cursor-pointer mx-auto"
                                                            @play="teaserPlayStates['board-' + item.id + '-' + currentIndex] = true"
                                                            @pause="teaserPlayStates['board-' + item.id + '-' + currentIndex] = false"
                                                            @click="togglePlay($event.target)"
                                                        ></video>
                                                    </div>
                                                </template>
                                            </div>

                                            <!-- ⬅ Prev Arrow -->
                                            <button
                                                x-show="item.files.length > 1"
                                                @click="if (currentIndex > 0) currentIndex--"
                                                :disabled="currentIndex === 0"
                                                class="absolute left-2 bg-white dark:bg-gray-700 bg-opacity-80 dark:bg-opacity-70 rounded-full p-1.5 shadow hover:bg-opacity-100 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                                style="top: 50%; transform: translateY(-50%);"
                                            >◀</button>

                                            <!-- ➡ Next Arrow -->
                                            <button
                                                x-show="item.files.length > 1"
                                                @click="if (currentIndex < item.files.length - 1) currentIndex++"
                                                :disabled="currentIndex === item.files.length - 1"
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
                                            @click.prevent="react(item.id, mood); $el.classList.add('animate-bounce'); setTimeout(()=>$el.classList.remove('animate-bounce'), 500)"
                                            x-data="{ showName: false }"
                                            @mouseenter="showName = true" 
                                            @mouseleave="showName = false"
                                            class="flex flex-col items-center justify-center transition-all duration-200 hover:scale-105
                                                px-1 sm:px-2 py-0.5 sm:py-1 text-xs sm:text-sm font-medium rounded-lg"
                                            :class="[
                                                item.user_reacted_mood === mood ? 'ring-2 ring-offset-1 ring-pink-400 shadow' : 'shadow-sm',
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
                                                    x-text="getReactionCount(item, mood)">
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
                                            x-model="item.newComment"
                                            placeholder="Type a comment..."
                                            class="flex-1 bg-transparent focus:outline-none text-xs sm:text-sm text-gray-700 dark:text-gray-200 placeholder-gray-400"
                                            @keydown.enter.prevent="postComment(item)"
                                        >
                                        <button
                                            @click.prevent="postComment(item)"
                                            class="text-pink-500 hover:text-pink-600 transition-colors text-xs sm:text-sm font-medium"
                                        >
                                            Post
                                        </button>
                                    </div>
                                    <div class="mt-2 space-y-2 max-h-32 overflow-y-auto pr-1">
                                        <div class="text-xs text-gray-500 flex justify-between">
                                            <span x-text="(item.comment_count ?? 0) + ' comments'"></span>
                                            <a :href="'/boards/' + item.id" class="text-pink-600 hover:underline text-sm font-medium">
                                                → View Board
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
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
                                :disabled="loading"
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
                                :disabled="loading" 
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
        items: [],
        loading: false,
        initialLoading: true,
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
            text: "📝 Text",
            teaser: "🎬 Teaser"
        },
        currentPlayingTeaserId: null,
        teaserPlayStates: {},
        fetchedBoardIds: [],
        fetchedTeaserIds: [],
        teaserReactionClicks: {}, // { [teaserId]: [timestamps] }
        teaserReactionCooldowns: {}, // { [teaserId]: timestamp }
        showTeaserComments: false,
        activeTeaserComments: null,

        init() {
            console.log("Alpine vibeFeed initialized");
            this.initialLoading = true;
            this.page = 1;
            this.items = [];
            this.allLoaded = false;
            this.loadBoards().finally(() => {
                this.initialLoading = false;
                this.$nextTick(() => {
                    console.log('filteredBoards after load:', JSON.parse(JSON.stringify(this.filteredBoards)));
                });
            });
            window.addEventListener('scroll', this.scrollHandler.bind(this));
            window.fb = this.filteredBoards;


            // In your Alpine init() or after loading boards:
            this.$nextTick(() => {
                this.items.forEach(item => {
                    if (item.type === 'teaser') {
                        if (this.teaserPlayStates[item.id] === undefined) {
                            this.teaserPlayStates[item.id] = false;
                        }
                    }
                    if (item.type === 'board' && Array.isArray(item.files)) {
                        item.files.forEach((file, idx) => {
                            if (file.type === 'video') {
                                const key = 'board-' + item.id + '-' + idx;
                                if (this.teaserPlayStates[key] === undefined) {
                                    this.teaserPlayStates[key] = false;
                                }
                            }
                        });
                    }
                });
            });
            this.initializePlayStates();
            this.setupVideoObservers();
        },

        scrollHandler() {
            if (this.loading || this.allLoaded) return;
            this.$nextTick(() => {
                const feed = document.querySelector('.flex.flex-col.gap-6.md\\:gap-8.z-0.mt-3');
                if (!feed) {
                    return;
                }
                const tiles = feed.querySelectorAll('.feed-tile');
                if (tiles.length === 0) {
                    return;
                }

                // Log the index and position of each tile
                tiles.forEach((tile, idx) => {
                    const rect = tile.getBoundingClientRect();
                });

                let lastVisibleIndex = -1;
                for (let i = tiles.length - 1; i >= 0; i--) {
                    const rect = tiles[i].getBoundingClientRect();
                    if (rect.top < window.innerHeight) {
                        lastVisibleIndex = i;
                        break;
                    }
                }

                if (
                    lastVisibleIndex >= 10 &&
                    !this.loading &&
                    !this.allLoaded
                ) {
                    this.loadBoards();
                }
            });
        },

        setupVideoObservers() {
            this.$nextTick(() => {
                // Moodboard videos
                document.querySelectorAll('video[data-moodboard]').forEach(video => {
                    if (video._observer) return; // Prevent double-observing
                    const observer = new IntersectionObserver(entries => {
                        entries.forEach(entry => {
                            if (!entry.isIntersecting && !video.paused) {
                                video.pause();
                            }
                        });
                    }, { threshold: 0.2 });
                    observer.observe(video);
                    video._observer = observer;
                });
                // Teaser videos
                document.querySelectorAll('video[data-teaser]').forEach(video => {
                    if (video._observer) return;
                    const observer = new IntersectionObserver(entries => {
                        entries.forEach(entry => {
                            if (!entry.isIntersecting && !video.paused) {
                                video.pause();
                            }
                        });
                    }, { threshold: 0.2 });
                    observer.observe(video);
                    video._observer = observer;
                });
            });
        },

        initializePlayStates() {
            this.items.forEach(item => {
                if (item.type === 'teaser' && item.id) {
                    if (this.teaserPlayStates[item.id] === undefined) {
                        this.teaserPlayStates[item.id] = false;
                    }
                }
                if (item.type === 'board' && Array.isArray(item.files)) {
                    item.files.forEach((file, idx) => {
                        if (file.type === 'video') {
                            const key = 'board-' + item.id + '-' + idx;
                            if (this.teaserPlayStates[key] === undefined) {
                                this.teaserPlayStates[key] = false;
                            }
                        }
                    });
                }
            });
        },

        isTeaserPlaying(id) {
            if (!id) return false;
            return !!this.teaserPlayStates[id];
        },

        get filteredBoards() {
            return this.items.filter(item => {
                if (item.type === 'teaser') {
                    // Show teasers unless a media type filter is set and "teaser" is NOT included
                    return this.selectedMediaTypes.length === 0 || this.selectedMediaTypes.includes('teaser');
                }
                if (item.type === 'board') {
                    // Mood filter (multiple moods allowed)
                    const moodMatch = this.selectedMoods.length === 0 || this.selectedMoods.includes(item.latest_mood);
                    // Media type filter for boards
                    const mediaMatch = this.selectedMediaTypes.length === 0 || this.selectedMediaTypes.includes(item.media_type);
                    return moodMatch && mediaMatch;
                }
                return false;
            });
        },
        
        toggleMood(mood) {
            if (this.loading) return; // Prevent clicks while loading

            const index = this.selectedMoods.indexOf(mood);
            if (index > -1) {
                this.selectedMoods.splice(index, 1);
            } else {
                this.selectedMoods.push(mood);
            }

            this.page = 1;
            this.items = [];
            this.allLoaded = false;
            this.fetchedBoardIds = [];
            this.fetchedTeaserIds = [];
            this.loadBoards();
            this.$nextTick(() => {
            });
        },

        toggleMediaType(type) {
            if (this.loading) return;

            if (this.selectedMediaTypes[0] === type) {
                this.selectedMediaTypes = [];
            } else {
                this.selectedMediaTypes = [type];
            }

            // 🔥 Reset moods so they don’t conflict
            this.selectedMoods = [];

            this.page = 1;
            this.items = [];
            this.allLoaded = false;
            this.fetchedBoardIds = [];
            this.fetchedTeaserIds = [];
            this.loadBoards();
        },

        normalizeItem(item) {
            if (item.type === 'board') {
                let files = []
                let imgs = item.images ?? item.image

                if (Array.isArray(imgs)) {
                    files.push(...imgs.map(path => ({
                        path: path.startsWith('http') ? path : `/storage/${path.replace(/^\/?storage\//, '')}`,
                        type: 'image'
                    })))
                } else if (typeof imgs === 'string' && imgs) {
                    let parsed = null
                    try { parsed = JSON.parse(imgs) } catch {}
                    if (Array.isArray(parsed)) {
                        files.push(...parsed.map(path => ({
                            path: path.startsWith('http') ? path : `/storage/${path.replace(/^\/?storage\//, '')}`,
                            type: 'image'
                        })))
                    } else {
                        files.push({
                            path: imgs.startsWith('http') ? imgs : `/storage/${imgs.replace(/^\/?storage\//, '')}`,
                            type: 'image'
                        })
                    }
                }

                // remove dupes
                const seen = new Set()
                files = files.filter(f => {
                    if (seen.has(f.path)) return false
                    seen.add(f.path)
                    return true
                })

                return {
                    ...item,
                    files,
                    teaserError: !item.video,
                    videoLoaded: false,
                    newComment: '',
                    comment_count: item.comment_count ?? 0,
                    is_saved: !!item.is_saved,
                    expanded: false,
                    saving: false
                }
            }

            if (item.type === 'teaser') {
                return {
                    ...item,
                    teaserError: !item.video,
                    is_saved: !!item.is_saved,
                    saving: false,
                }
            }

            return item
        },

        async loadBoards() {
            if (this.loading || this.allLoaded) return;
            if (this.page === 1) this.loading = true;

            const url = `/api/boards?moodboards=20&teasers=5`
                + `&exclude_board_ids=${this.fetchedBoardIds.join(',')}`
                + `&exclude_teaser_ids=${this.fetchedTeaserIds.join(',')}`
                + (this.selectedMediaTypes.length ? `&media_types=${this.selectedMediaTypes.join(',')}` : '')
                + (this.selectedMoods.length ? `&moods=${this.selectedMoods.join(',')}` : '');

            try {
                const res = await fetch(url, {
                    credentials: 'include',
                    headers: { 'Accept': 'application/json' }
                });

                const contentType = res.headers.get('content-type') || '';
                if (!res.ok) {
                    const text = await res.text();
                    throw new Error(`Request failed: ${res.status}`);
                }
                if (!contentType.includes('application/json')) {
                    const text = await res.text();
                    throw new Error('Non-JSON response received');
                }

                const json = await res.json();

                // Update fetched IDs
                if (json.sent_board_ids) {
                    this.fetchedBoardIds.push(...json.sent_board_ids.filter(id => !this.fetchedBoardIds.includes(id)));
                }
                if (json.sent_teaser_ids) {
                    this.fetchedTeaserIds.push(...json.sent_teaser_ids.filter(id => !this.fetchedTeaserIds.includes(id)));
                }

                // 🛑 Fix: Set loading to false before returning
                if (!json.data || json.data.length === 0 || json.all_loaded) {
                    this.allLoaded = true;
                    this.loading = false; // <--- Add this line
                    return;
                }

                const newItems = json.data.map(item => this.normalizeItem(item))
                    .filter(newItem => !this.items.some(existing =>
                        existing.type === newItem.type &&
                        existing.id === newItem.id &&
                        existing.created_at === newItem.created_at
                    ));

                console.group(`Loaded ${newItems.length} items (page ${this.page})`)
                newItems.forEach(i => {
                    if (i.type === 'board') {
                        console.log(`Board #${i.id} → files:`, i.files)
                    } else if (i.type === 'teaser') {
                        console.log(`Teaser #${i.id}`)
                    }
                })
                console.groupEnd()

                if (!this.items) this.items = []
                this.items.push(...newItems)

                this.setupVideoObservers();
                this.page += 1;
                this.initializePlayStates();

            } catch (error) {
            } finally {
                this.loading = false;
            }
        },

        handlePlay(id) {
            this.currentPlayingTeaserId = id;
            this.teaserPlayStates[id] = true;
        },

        handlePause(id) {
            if (this.currentPlayingTeaserId === id) {
                this.currentPlayingTeaserId = null;
            }
            this.teaserPlayStates[id] = false;
        },

        togglePlay(videoEl) {
            if (!videoEl) return;

            // Pause all other videos
            document.querySelectorAll('video[data-moodboard], video[data-teaser]').forEach(v => {
                if (v !== videoEl && !v.paused) v.pause();
            });

            videoEl.muted = false;
            if (videoEl.paused) {
                videoEl.play();
            } else {
                videoEl.pause();
            }
        },

        startFastForward(videoEl) {
            if (!videoEl) return;
            videoEl.playbackRate = 2.0;
        },

        stopFastForward(videoEl) {
            if (!videoEl) return;
            videoEl.playbackRate = 1.0;
        },

        getRemainingTime(expiresOn) {
            if (!expiresOn) return '—';
            const now = new Date();
            const expires = new Date(expiresOn);
            const diff = expires - now;
            if (diff <= 0) return 'Expired';
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            return `${hours}h ${mins}m`;
        },

        toggleSaveById(boardId) {
            // Find the board from existing state
            const board =
                this.items.find(b => b.id === boardId) ||
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

        searchUsers() {
            if (this.searchQuery.trim().length === 0) {
                this.searchResults = [];
                return;
            }

            fetch(`/api/search-users?q=${encodeURIComponent(this.searchQuery)}`)
                .then(res => res.json())
                .then(res => {
                    this.boards = res.data
                    this.filteredBoards = this.boards
                    console.log("Filtered now:", this.filteredBoards)
                })
                .catch(err => {
                    console.error('Search error:', err);
                });
        },

        goToProfile(username) {
            window.location.href = `/space/${username}`;
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
            const board = this.items.find(b => b.id === boardId);
            if (!board) { this.showToast("Board not found", 'error'); return; }
            if (board.user_reacted_mood === mood) { this.showToast("You already picked this mood 💅", 'error'); return; }
            if (board.reacting) return; // Prevent double-clicks

            board.reacting = true;
            this.showLoadingToast("Reacting...");

            fetch('/reaction', {
                method: 'POST',
                headers: this._headers(),
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

                board.user_reacted_mood = newMood;

                this.showToast("Mood updated! 💖");
            })
            .catch(err => this.showToast(err?.error || "Failed to react 💔", 'error'))
            .finally(() => {
                board.reacting = false;
            });
        },

        postComment(board) {
            if (!board.newComment.trim() || board.commenting) return;

            board.commenting = true;
            this.showLoadingToast("Commenting...");

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
            .catch(() => this.showToast("Comment failed 😢", 'error'))
            .finally(() => {
                board.commenting = false;
            });
        },

        reactToTeaser(teaserId, reaction) {
            const now = Date.now();
            const teaser = this.items.find(t => t.id === teaserId && t.type === 'teaser');
            if (!teaser) return;

            // Cooldown check
            if (this.teaserReactionCooldowns[teaserId] && now < this.teaserReactionCooldowns[teaserId]) {
                this.showToast("Too many reactions! Please wait a bit.", "error");
                return;
            }

            // Track click timestamps
            if (!this.teaserReactionClicks[teaserId]) this.teaserReactionClicks[teaserId] = [];
            // Remove timestamps older than 1 minute
            this.teaserReactionClicks[teaserId] = this.teaserReactionClicks[teaserId].filter(ts => now - ts < 60000);
            this.teaserReactionClicks[teaserId].push(now);

            if (this.teaserReactionClicks[teaserId].length > 4) {
                // Set 30s cooldown
                this.teaserReactionCooldowns[teaserId] = now + 30000;
                this.showToast("Reaction limit reached! Try again in 30 seconds.", "error");
                return;
            }

            if (teaser.reacting) return;
            teaser.reacting = true;

            // If already reacted, remove reaction
            const isRemoving = teaser.user_teaser_reaction === reaction;
            fetch('/teasers/react', {
                method: 'POST',
                headers: this._headers(),
                body: JSON.stringify({
                    teaser_id: teaserId,
                    reaction,
                    remove: isRemoving ? 1 : 0
                }),
            })
            .then(res => res.ok ? res.json() : Promise.reject())
            .then(data => {
                ['fire','love','boring'].forEach(r => {
                    teaser[r + '_count'] = data[r + '_count'] || 0;
                });
                teaser.user_teaser_reaction = data.user_reaction;
            })
            .catch(() => this.showToast("Failed to react", 'error'))
            .finally(() => { teaser.reacting = false; });
        },

        async openTeaserComments(teaser) {
            this.activeTeaserComments = teaser;
            this.showTeaserComments = true;
            if (!teaser.comments) {
                try {
                    const res = await fetch(`/teasers/${teaser.id}/comments`);
                    teaser.comments = res.ok ? await res.json() : [];
                } catch {
                    teaser.comments = [];
                }
            }
        },

        closeTeaserComments() {
            this.showTeaserComments = false;
            this.activeTeaserComments = null;
        },

        async postTeaserComment(teaser) {
            if (!teaser.newComment || !teaser.newComment.trim()) return;
            if (teaser.commenting) return;
            teaser.commenting = true;

            try {
                const res = await fetch('/teasers/comments', {
                    method: 'POST',
                    headers: this._headers(),
                    body: JSON.stringify({
                        teaser_id: teaser.id,
                        body: teaser.newComment.trim(),
                    }),
                });
                if (!res.ok) throw new Error('Failed to post comment');
                const comment = await res.json();

                // Ensure comments array exists
                if (!teaser.comments) teaser.comments = [];
                // Add new comment at the top
                teaser.comments.unshift(comment);
                teaser.comment_count = (teaser.comment_count || 0) + 1;
                teaser.newComment = '';
                this.showToast('Comment posted! 🎉');
            } catch (e) {
                this.showToast('Failed to post comment', 'error');
            } finally {
                teaser.commenting = false;
            }
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

        async toggleSaveTeaser(teaser) {
            // Debug: Log when the function is called
            console.log('[toggleSaveTeaser] Clicked for teaser:', teaser);

            // Prevent double-clicks while saving
            if (teaser.saving) {
                console.log('[toggleSaveTeaser] Already saving, aborting.');
                return;
            }
            teaser.saving = true;

            try {
                // Send the save/unsave request
                const response = await fetch('/teasers/toggle-save', {
                    method: 'POST',
                    headers: this._headers(),
                    body: JSON.stringify({ teaser_id: teaser.id })
                });

                // Debug: Log the raw response
                console.log('[toggleSaveTeaser] Response:', response);

                if (!response.ok) {
                    const text = await response.text();
                    console.error('[toggleSaveTeaser] Error response:', text);
                    throw new Error('Failed to toggle save');
                }

                const data = await response.json();
                console.log('[toggleSaveTeaser] Parsed response:', data);

                // Update the teaser's saved state
                teaser.is_saved = !!data.is_saved;
                this.showToast(teaser.is_saved ? 'Teaser saved!' : 'Removed from saved');

            } catch (error) {
                console.error('[toggleSaveTeaser] Exception:', error);
                this.showToast('Failed to save teaser', 'error');
            } finally {
                teaser.saving = false;
                console.log('[toggleSaveTeaser] Done for teaser:', teaser.id);
            }
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



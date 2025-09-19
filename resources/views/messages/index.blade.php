@extends('layouts.app')

@section('content')
<div x-data="messageInbox()" x-init="init()" @open-preview-modal.window="openPreviewModal($event.detail.files, $event.detail.index)" class="flex flex-col lg:flex-row h-[100dvh] overflow-hidden">

<!-- Block/Unblock Confirm Modal -->
<template x-if="showBlockModal">
    <div class="fixed inset-0 z-[9999] bg-black/60 flex items-center justify-center" x-cloak>
        <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full">
            <template x-if="!$store.messaging.receiver.is_blocked">
                <div>
                    <h2 class="text-lg font-bold text-red-600 mb-2">Block User</h2>
                    <p class="mb-4 text-gray-700">
                        Are you sure you want to block this user? You will not receive their messages after blocking.
                    </p>
                    <div class="flex justify-end gap-2">
                        <button @click="showBlockModal = false" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300">Cancel</button>
                        <button @click="blockUser()" class="px-4 py-2 rounded bg-red-500 text-white hover:bg-red-600">Block</button>
                    </div>
                </div>
            </template>
            <template x-if="$store.messaging.receiver.is_blocked">
                <div>
                    <h2 class="text-lg font-bold text-green-600 mb-2">Unblock User</h2>
                    <p class="mb-4 text-gray-700">
                        You have already blocked this user. Do you want to unblock and resume messaging?
                    </p>
                    <div class="flex justify-end gap-2">
                        <button @click="showBlockModal = false" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300">Cancel</button>
                        <button @click="blockUser()" class="px-4 py-2 rounded bg-green-500 text-white hover:bg-green-600">Unblock</button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<!-- Mute Confirm Modal -->
<template x-if="showMuteModal">
    <div class="fixed inset-0 z-[9999] bg-black/60 flex items-center justify-center" x-cloak>
        <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full">
            <h2 class="text-lg font-bold text-yellow-600 mb-2">Mute User</h2>
            <p class="mb-4 text-gray-700">How long do you want to mute this user?</p>
            <select x-model="muteDuration" class="w-full mb-4 border rounded px-2 py-1">
                <option value="8h">8 hours</option>
                <option value="24h">24 hours</option>
                <option value="1w">1 week</option>
                <option value="forever">Forever</option>
            </select>
            <div class="flex justify-end gap-2">
                <button @click="showMuteModal = false" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300">Cancel</button>
                <button @click="muteUser()" class="px-4 py-2 rounded bg-yellow-500 text-white hover:bg-yellow-600">Mute</button>
            </div>
        </div>
    </div>
</template>

<template x-if="showPreviewModal && previewFiles && previewFiles.length > 0 && typeof previewIndex === 'number'">
    <div 
        class="fixed inset-0 bg-black/80 z-[1999] flex items-center justify-center"
        x-data="{
            videoCurrentTime: 0,
            videoDuration: 0,
            videoMuted: true,
            formatTime(seconds) {
                if (!seconds || isNaN(seconds)) return '00:00';
                const m = Math.floor(seconds / 60);
                const s = Math.floor(seconds % 60);
                return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
            }
        }"
    >
        <button @click="showPreviewModal = false" class="absolute top-4 right-4 text-white text-xl sm:text-2xl hover:text-pink-300 transition">×</button>
        <template x-if="previewFiles[previewIndex]">
            <div class="relative group">
                <!-- Video Preview -->
                <template x-if="previewFiles[previewIndex].extension && previewFiles[previewIndex].extension.match(/^(mp4|mov|webm)$/i)">
                    <div class="relative group">
                        <video
                            x-ref="previewVideo"
                            :src="previewFiles[previewIndex].url || previewFiles[previewIndex].path || previewFiles[previewIndex].file_path"
                            @timeupdate="videoCurrentTime = $refs.previewVideo.currentTime"
                            @loadedmetadata="videoDuration = $refs.previewVideo.duration"
                            @volumechange="videoMuted = $refs.previewVideo.muted"
                            class="max-w-[90vw] max-h-[80vh] rounded-lg shadow-xl bg-black"
                            x-init="videoCurrentTime = 0; videoDuration = 0; videoMuted = $refs.previewVideo.muted"
                            controlslist="nodownload noremoteplayback"
                            @contextmenu.prevent
                            @ended="videoCurrentTime = 0"
                        ></video>
                        <!-- Custom Progress Bar & Controls -->
                        <div class="absolute bottom-0 left-0 w-full bg-black/70 text-white px-4 py-2 flex items-center justify-between gap-3 rounded-b-lg">
                            <div class="flex items-center gap-3">
                                <button
                                    @click="
                                        if ($refs.previewVideo.src && !$refs.previewVideo.src.endsWith('/')) {
                                            if ($refs.previewVideo.paused) {
                                                $refs.previewVideo.play();
                                            } else {
                                                $refs.previewVideo.pause();
                                            }
                                        }
                                    "
                                    class="px-1 hover:text-pink-400"
                                >
                                    <template x-if="$refs.previewVideo && $refs.previewVideo.paused">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><polygon points="6,4 18,10 6,16" /></svg>
                                    </template>
                                    <template x-if="$refs.previewVideo && !$refs.previewVideo.paused">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><rect x="6" y="4" width="3" height="12" /><rect x="11" y="4" width="3" height="12" /></svg>
                                    </template>
                                </button>
                                <button @click="$refs.previewVideo.muted = !$refs.previewVideo.muted" class="px-1 hover:text-pink-400">
                                    <template x-if="!videoMuted">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 7H5a1 1 0 00-1 1v4a1 1 0 001 1h4l4 4V3l-4 4z"/></svg>
                                    </template>
                                    <template x-if="videoMuted">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9 7H5a1 1 0 00-1 1v4a1 1 0 001 1h4l4 4V3l-4 4z"/><line x1="16" y1="4" x2="4" y2="16" stroke="currentColor" stroke-width="2"/></svg>
                                    </template>
                                </button>
                                <span class="text-xs font-mono min-w-[48px]" x-text="formatTime(videoCurrentTime)"></span>
                            </div>
                            <div class="flex-1 mx-3">
                                <div class="relative h-2 bg-gray-700 rounded-full cursor-pointer"
                                     @click="$refs.previewVideo.currentTime = (videoDuration * ($event.offsetX / $event.target.offsetWidth))">
                                    <div class="absolute top-0 left-0 h-2 bg-pink-500 rounded-full"
                                         :style="`width: ${(videoCurrentTime / videoDuration) * 100 || 0}%`"></div>
                                </div>
                            </div>
                            <span class="text-xs font-mono min-w-[48px]" x-text="formatTime(videoDuration)"></span>
                        </div>
                    </div>
                </template>
                <!-- Image Preview -->
                <template x-if="previewFiles[previewIndex].extension && previewFiles[previewIndex].extension.match(/^(jpg|jpeg|png|gif|webp)$/i)">
                    <img :src="previewFiles[previewIndex].url || previewFiles[previewIndex].path || previewFiles[previewIndex].file_path"
                        class="max-w-[90vw] max-h-[80vh] rounded-lg shadow-xl object-contain">
                </template>
                <!-- Fallback for other files -->
                <template x-if="!previewFiles[previewIndex].extension || !previewFiles[previewIndex].extension.match(/^(jpg|jpeg|png|gif|webp|mp4|mov|webm)$/i)">
                    <div class="flex flex-col items-center justify-center w-full h-full text-white bg-black/80">
                        <span class="text-lg">File preview not supported.</span>
                        <a :href="previewFiles[previewIndex].url || previewFiles[previewIndex].path || previewFiles[previewIndex].file_path" target="_blank" class="underline">Download</a>
                    </div>
                </template>
            </div>
        </template>
        <!-- Navigation Buttons -->
        <button @click="previewIndex = Math.max(0, previewIndex - 1)"
                :disabled="previewIndex === 0"
                class="absolute left-4 top-1/2 transform -translate-y-1/2 text-white text-2xl">‹</button>
        <button @click="previewIndex = Math.min(previewFiles.length - 1, previewIndex + 1)"
                :disabled="previewIndex === previewFiles.length - 1"
                class="absolute right-4 top-1/2 transform -translate-y-1/2 text-white text-2xl">›</button>
    </div>
</template>

    <!-- File Picker Modal (mirroring create.blade.php CSS/structure, but keeping logic) -->
    <div
        x-show="showMediaModal"
        x-data="filePicker()"
        x-cloak
        @click.self="showMediaModal = false; $store.filePicker && $store.filePicker.selectedIds ? $store.filePicker.selectedIds = [] : null"
        class="fixed inset-0 z-[999] bg-black/60 backdrop-blur-sm flex items-center justify-center"
        x-transition
    >
        <div class="bg-white w-full md:w-[1000px] max-w-[95vw] h-[600px] rounded-xl shadow-2xl overflow-hidden flex flex-col text-base sm:text-sm">
            <!-- Header -->
            <div class="sticky top-0 z-10 p-3 sm:p-4 border-b bg-gradient-to-r from-pink-50 to-purple-50">
                <div class="flex items-center gap-2">
                    <!-- Back Arrow -->
                    <button x-show="!showFileListsPanel" @click="showFileListsPanel = true" class="flex items-center justify-center w-8 h-8 bg-white/90 hover:bg-white rounded-full shadow-sm border border-gray-200 mr-2" title="Show file lists">⬅️</button>
                    <h2 class="font-bold text-pink-600 text-lg sm:text-xl flex-1 text-center sm:text-left">
                        📂 Select Your Files
                    </h2>
                    <button   @click="showMediaModal = false; selectedIds = []" type="button"
                        class="px-3 sm:px-2 py-0.5 sm:py-1 rounded bg-red-500 text-white hover:bg-red-600 font-medium text-sm sm:text-base">
                        Close
                    </button>
                </div>
                <div class="mt-2 sm:mt-3 flex flex-wrap gap-1 sm:gap-2 text-xs sm:text-sm text-gray-600" x-show="!showFileListsPanel">
                    <select x-model="filters.type" @change="loadFiles()" class="rounded border px-2 py-1 bg-white text-xs sm:text-sm">
                        <option value="all">📁 All</option>
                        <option value="image">🖼️ Images</option>
                        <option value="video">🎬 Videos</option>
                    </select>
                    <select x-model="filters.contentType" @change="loadFiles()" class="rounded border px-2 py-1 bg-white text-xs sm:text-sm">
                        <option value="all">🔒 All</option>
                        <option value="safe">🙂 Safe</option>
                        <option value="adult">⚠️ Adult</option>
                    </select>
                    <select x-model="filters.sort" @change="loadFiles()" class="rounded border px-2 py-1 bg-white text-xs sm:text-sm">
                        <option value="latest">⏱️ Latest</option>
                        <option value="earliest">🕰️ Earliest</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-1 overflow-hidden relative">
                <!-- File Lists Panel -->
                <div x-show="showFileListsPanel" class="w-full sm:w-[250px] border-r bg-gray-50 overflow-y-auto p-2 sm:p-4 absolute sm:static z-20 transition-transform duration-200">
                    <template x-if="isLoadingLists">
                        <div class="flex items-center justify-center h-40 w-full">
                            <svg class="animate-spin h-8 w-8 text-pink-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                        </div>
                    </template>
                    <template x-if="!isLoadingLists">
                        <div>
                            <!-- Default All Media List -->
                            <button type="button"
                                @click="setList('all')"
                                class="flex items-center justify-between w-full px-2 sm:px-3 py-1 sm:py-2 mb-1 sm:mb-2 text-left rounded-lg hover:bg-pink-50 text-sm sm:text-base"
                                :class="activeListId === 'all' ? 'bg-pink-100 text-pink-700 font-bold' : 'text-gray-700'">
                                <span class="truncate">All Media</span>
                                <span class="text-xs sm:text-sm text-gray-500 flex gap-2 ml-2">
                                    <span class="flex items-center gap-1">
                                        <span x-text="lists.length > 0 ? lists[0].imageCount : 0"></span>
                                        <span>🖼️</span>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <span x-text="lists.length > 0 ? lists[0].videoCount : 0"></span>
                                        <span>🎬</span>
                                    </span>
                                </span>
                            </button>
                            <!-- Other Lists -->
                            <template x-for="list in lists.slice(1)" :key="list.id">
                                <button type="button"
                                    @click="setList(list.id)"
                                    class="flex items-center justify-between w-full px-2 sm:px-3 py-1 sm:py-2 mb-1 sm:mb-2 text-left rounded-lg hover:bg-pink-50 text-sm sm:text-base"
                                    :class="list.id === activeListId ? 'bg-pink-100 text-pink-700 font-bold' : 'text-gray-700'">
                                    <span x-text="list.name" class="truncate"></span>
                                    <span class="text-xs sm:text-sm text-gray-500 flex gap-2 ml-2">
                                        <span class="flex items-center gap-1">
                                            <span x-text="list.imageCount || 0"></span>
                                            <span>🖼️</span>
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <span x-text="list.videoCount || 0"></span>
                                            <span>🎬</span>
                                        </span>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- File Grid -->
                <div x-show="!showFileListsPanel" class="w-full flex-1 h-full overflow-y-auto relative">
                    <template x-if="isLoadingFiles">
                        <div class="absolute inset-0 flex items-center justify-center bg-white/80 z-10">
                            <svg class="animate-spin h-10 w-10 text-pink-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                        </div>
                    </template>
                    <div class="px-2 py-4 sm:px-4 md:px-6 lg:px-8 grid gap-3 sm:gap-4"
                        style="grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));">
                        <template x-for="file in files" :key="file.id">
                            <div class="aspect-square bg-white border border-gray-300 rounded-xl overflow-hidden relative hover:scale-[1.05] transition shadow-sm group cursor-pointer"
                                @click="$dispatch('open-preview-modal', { files: files, index: files.findIndex(f => f.id === file.id) })">
                                <!-- Checkbox (top left) -->
                                <input type="checkbox"
                                    class="absolute top-1 sm:top-2 left-1 sm:left-2 h-4 w-4 text-pink-500 rounded border-gray-300 z-10"
                                    :checked="selectedIds.includes(file.id)"
                                    @click.stop="toggleFileSelection(file.id)">
                                <!-- File Type Icon (top right, no bg, only 3 types) -->
                                <div class="absolute top-1 right-1 z-20 text-xl select-none">
                                    <template x-if="['jpg','jpeg','png','gif','webp'].includes(file.extension)">
                                        <span title="Image">🖼️</span>
                                    </template>
                                    <template x-if="['mp4','mov','webm'].includes(file.extension)">
                                        <span title="Video">🎬</span>
                                    </template>
                                    <template x-if="['mp3','wav','ogg'].includes(file.extension)">
                                        <span title="Audio">🎵</span>
                                    </template>
                                </div>
                                <template x-if="file.extension && ['mp4','mov','webm'].includes(file.extension)">
                                    <video :src="file.url || file.path" muted playsinline preload="metadata" class="w-full h-full object-cover bg-black">
                                        <template x-if="!(file.url || file.path)">
                                            <source src="" />
                                        </template>
                                    </video>
                                </template>
                                <template x-if="file.extension && ['jpg','jpeg','png','gif','webp'].includes(file.extension)">
                                    <img :src="file.url || file.path" class="w-full h-full object-cover" alt="">
                                </template>
                                <template x-if="file.extension && ['mp3','wav','ogg'].includes(file.extension)">
                                    <div class="flex flex-col items-center justify-center w-full h-full text-blue-500">
                                        <span class="text-4xl">🎵</span>
                                        <audio :src="file.url || file.path" controls class="w-full mt-1"></audio>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-2 sm:p-4 border-t bg-white flex justify-end items-center">
                <div class="flex gap-2">
                    <button
                        @click="selectedIds = []"
                        type="button"
                        :disabled="selectedIds.length === 0"
                        class="px-3 sm:px-4 py-1 sm:py-2 rounded bg-red-500 text-white hover:bg-red-600 font-medium text-sm sm:text-base transition"
                        :class="selectedIds.length === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                        >
                        ✖️ Clear
                    </button>
                    <button
                        @click="addSelectedFiles()"
                        type="button"
                        :disabled="selectedIds.length === 0"
                        class="px-3 sm:px-4 py-1 sm:py-2 rounded bg-green-500 text-white hover:bg-green-600 font-medium text-sm sm:text-base transition"
                        :class="selectedIds.length === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                        >
                        ➕ Add (<span x-text="selectedIds.length"></span>/20)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 📬 Messages Page Mobile Nav -->
<div class="lg:hidden sticky top-0 z-[99] bg-gradient-to-r from-pink-500 to-purple-600 shadow-md border-b border-white/20">
    <div class="flex justify-around px-3 py-2 text-white text-sm">
        <!-- Home -->
        <a href="{{ route('home') }}"
             class="flex flex-col items-center transition duration-300 ease-in-out hover:text-yellow-300">
            🏠
            <span class="text-[11px] mt-1 tracking-wide">Home</span>
        </a>

        <!-- Messages with unread conversations badge -->
        <div class="relative flex flex-col items-center">
            <a href="/messages"
                 class="flex flex-col items-center text-yellow-300 font-semibold transition duration-300 ease-in-out">
                💌
                <span class="text-[11px] mt-1 tracking-wide">Messages</span>
            </a>
            <template x-if="$store.messaging.unreadConversationsCount > 0">
                <span class="absolute -top-1 -right-2 bg-pink-500 text-white text-xs font-bold rounded-full px-1.5 py-0.5 min-w-[18px] text-center z-10" x-text="$store.messaging.unreadConversationsCount"></span>
            </template>
        </div>

        <!-- Me -->
        <a href="/my-vibes"
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

<!-- Tab Buttons: Only show when no conversation is open -->
<template x-if="!$store.messaging.receiver">
    <div class="lg:hidden sticky top-[44px] z-[98] bg-white border-b flex justify-around items-center py-2">
        <button
            @click="$store.messaging.activeTab = 'messages'"
            :class="$store.messaging.activeTab === 'messages' ? 'text-pink-600 font-bold border-b-2 border-pink-500' : 'text-gray-500'"
            class="flex-1 py-2 text-center transition relative"
        >
            Messages
            <template x-if="$store.messaging.unreadMessagesCount() > 0">
                <span class="absolute top-0 right-2 bg-pink-500 text-white text-xs rounded-full px-2 py-0.5"
                    x-text="$store.messaging.unreadMessagesCount()"></span>
            </template>
        </button>
        <button
            @click="$store.messaging.activeTab = 'requests'"
            :class="$store.messaging.activeTab === 'requests' ? 'text-pink-600 font-bold border-b-2 border-pink-500' : 'text-gray-500'"
            class="flex-1 py-2 text-center transition relative"
        >
            Requests
            <template x-if="$store.messaging.unreadRequestsCount() > 0">
                <span class="absolute top-0 right-2 bg-pink-500 text-white text-xs rounded-full px-2 py-0.5"
                    x-text="$store.messaging.unreadRequestsCount()"></span>
            </template>
        </button>
    </div>
</template>


    <!-- Left Sidebar (Recent Chats) -->
    <div class="w-full lg:w-1/3 bg-white border-r flex flex-col">
        <!-- 🔍 Desktop Sticky Search Bar -->
        <div class="hidden lg:block sticky top-0 z-20 bg-gradient-to-r from-pink-500 to-purple-600 px-4 pt-4 pb-2 border-b border-gray-200">
            <input type="text" x-model="searchQuery" placeholder="Search messages..."
                class="w-full px-4 py-2 border border-white/30 rounded-full focus:outline-none focus:ring-2 focus:ring-white text-sm shadow-sm bg-white placeholder-pink-500 text-pink-600">
        </div>

        <!-- 💬 Recent Chats List -->
        <template x-if="$store.messaging.activeTab === 'messages'">
            <div
                x-show="showRecentChats || isDesktop"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform translate-x-4"
                x-transition:enter-end="opacity-100 transform translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform translate-x-0"
                x-transition:leave-end="opacity-0 transform translate-x-4"
                class="flex-1 overflow-y-auto px-4 pt-4 transition-all"
                id="recent-chats-scroll"
            >
                <!-- 🔁 Recent Chats Loop -->
                <template
                    x-for="contact in [...$store.messaging.tabbedContacts].sort((a, b) => new Date(b.last_message?.created_at || 0) - new Date(a.last_message?.created_at || 0))"
                    :key="contact.id"
                >
                    <a href="#"
                        @click.prevent.stop="selectUser(contact)"
                        class="block bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all group px-4 py-3 mb-2"
                    >
                        <div class="flex justify-between items-center">
                            <div class="min-w-0">
                                <div :class="contact.should_bold ? 'font-bold text-gray-900' : 'font-semibold text-gray-800'" class="text-base truncate group-hover:text-pink-600 transition-colors"
                                    x-text="'@' + contact.username"></div>
                                <div class="text-xs truncate flex items-center space-x-1 mt-1 group-hover:text-pink-500 transition-colors"
                                    :class="contact.should_bold ? 'font-bold text-gray-900' : 'text-gray-500'">
                                    <template x-if="contact.has_attachment">
                                        <span class="text-pink-500 font-bold">🖼️</span>
                                    </template>
                                    <template x-if="contact.last_message && contact.last_message.body === 'Attachment'">
                                        <span class="font-bold">Attachment</span>
                                    </template>
                                    <template x-if="contact.last_message && contact.last_message.body && contact.last_message.body !== 'Attachment'">
                                        <span x-text="contact.last_message.body"></span>
                                    </template>
                                    <template x-if="!contact.last_message">
                                        <span>No messages yet</span>
                                    </template>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <template x-if="contact.should_bold && contact.unread_count > 0">
                                    <span class="inline-block min-w-[22px] px-2 py-0.5 rounded-full bg-pink-500 text-white text-xs font-bold text-center" x-text="contact.unread_count"></span>
                                </template>
                                <div :class="contact.should_bold ? 'font-bold text-gray-900' : 'text-gray-400'" class="text-[0.7rem] whitespace-nowrap"
                                    x-text="contact.last_message?.created_at ? new Date(contact.last_message.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : ''">
                                </div>
                            </div>
                        </div>
                    </a>
                </template>

                <!-- 🚫 Fallback if no contacts -->
                 <template x-if="$store.messaging.tabbedContacts.length === 0">
                    <div class="text-center text-gray-400 text-sm py-4">No recent chats found.</div>
                </template>
            </div>
        </template>

        <template x-if="$store.messaging.activeTab === 'requests' && !$store.messaging.receiver">
            <div>
                <!-- Sub-tabs for Requests -->
                <div class="flex justify-center gap-2 mb-2">
                    <button
                        @click="$store.messaging.requestsSubTab = 'received'"
                        :class="$store.messaging.requestsSubTab === 'received' ? 'text-pink-600 font-bold border-b-2 border-pink-500' : 'text-gray-500'"
                        class="px-4 py-2 transition"
                    >Received</button>
                    <button
                        @click="$store.messaging.requestsSubTab = 'sent'"
                        :class="$store.messaging.requestsSubTab === 'sent' ? 'text-pink-600 font-bold border-b-2 border-pink-500' : 'text-gray-500'"
                        class="px-4 py-2 transition"
                    >Sent</button>
                </div>
                <div class="flex-1 overflow-y-auto px-4 pt-4 transition-all" id="recent-chats-scroll">
                    <template
                        x-for="contact in [...$store.messaging.tabbedContacts].sort((a, b) => new Date(b.last_message?.created_at || 0) - new Date(a.last_message?.created_at || 0))"
                        :key="contact.id"
                    >
                        <a href="#"
                            @click.prevent.stop="selectUser(contact)"
                            class="block bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all group px-4 py-3 mb-2"
                        >
                            <div class="flex justify-between items-center">
                                <div class="min-w-0">
                                    <div :class="contact.should_bold ? 'font-bold text-gray-900' : 'font-semibold text-gray-800'" class="text-base truncate group-hover:text-pink-600 transition-colors"
                                        x-text="'@' + contact.username"></div>
                                    <div class="text-xs truncate flex items-center space-x-1 mt-1 group-hover:text-pink-500 transition-colors"
                                        :class="contact.should_bold ? 'font-bold text-gray-900' : 'text-gray-500'">
                                        <template x-if="contact.has_attachment">
                                            <span class="text-pink-500 font-bold">🖼️</span>
                                        </template>
                                        <template x-if="contact.last_message && contact.last_message.body === 'Attachment'">
                                            <span class="font-bold">Attachment</span>
                                        </template>
                                        <template x-if="contact.last_message && contact.last_message.body && contact.last_message.body !== 'Attachment'">
                                            <span x-text="contact.last_message.body"></span>
                                        </template>
                                        <template x-if="!contact.last_message">
                                            <span>No messages yet</span>
                                        </template>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <!-- Show unread count for each user -->
                                    <template x-if="contact.unread_count > 0">
                                        <span class="inline-block min-w-[22px] px-2 py-0.5 rounded-full bg-pink-500 text-white text-xs font-bold text-center" x-text="contact.unread_count"></span>
                                    </template>
                                    <div :class="contact.should_bold ? 'font-bold text-gray-900' : 'text-gray-400'" class="text-[0.7rem] whitespace-nowrap"
                                        x-text="contact.last_message?.created_at ? new Date(contact.last_message.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : ''">
                                    </div>
                                </div>
                            </div>
                        </a>
                    </template>
                    <template x-if="$store.messaging.tabbedContacts.length === 0">
                        <div class="text-center text-gray-400 text-sm py-4">No requests found.</div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <!-- Main Chat Area -->
    <div
        class="flex-1 flex flex-col bg-gray-50 overflow-y-auto"
        x-transition
    >
        <template x-if="$store.messaging.receiver">
            <div class="flex flex-col flex-1 overflow-hidden">
                <!-- Sticky Chat Header -->
                 <template x-if="!showMediaScreen">
                    <div class="sticky top-0 z-10 px-3 py-2 sm:px-4 sm:py-3 border-b bg-gradient-to-r from-pink-50 to-purple-50 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <!-- Left Arrow: Only show when a conversation is open (mobile/desktop) -->
                            <template x-if="$store.messaging.receiver">
                                <button @click="
                                    showRecentChats = true;
                                    $store.messaging.receiver = null;
                                    $store.messaging.messages = [];
                                    history.pushState(null, '', '/messages');
                                "
                                class="flex items-center justify-center w-8 h-8 bg-white/90 hover:bg-pink-100 rounded-full shadow-sm border border-gray-200 mr-2 text-pink-600 text-lg"
                                title="Back to Recent Chats">
                                    ←
                                </button>
                            </template>
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-pink-200 flex items-center justify-center text-pink-600 text-sm sm:text-base">
                                <span x-text="$store.messaging.receiver.username.charAt(0).toUpperCase()"></span>
                            </div>
                            <!-- ✅ Clickable Username -->
                            <span
                                class="font-semibold text-pink-600 text-base sm:text-lg truncate max-w-[60vw] sm:max-w-none"
                                x-text="'@' + $store.messaging.receiver.username">
                            </span>
                        </div>

                        <!-- Options Dropdown -->
                        <div x-data="{ showMenu: false }" class="relative z-[49]">
                            <button @click="showMenu = !showMenu" class="text-gray-400 hover:text-pink-500 focus:outline-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                </svg>
                            </button>
                            <div x-show="showMenu" @click.away="showMenu = false" x-transition
                                class="absolute right-0 mt-2 w-48 bg-white rounded shadow-lg border z-50 py-2">
                                <!-- View Profile -->
                                <a
                                    :href="`/space/${$store.messaging.receiver.username}-${$store.messaging.receiver.id}`"
                                    class="w-full text-left px-4 py-2 hover:bg-pink-50 flex items-center gap-2"
                                >
                                    <span>View Profile</span>
                                </a>
                                <!-- Media -->
                                <button type="button"
                                    class="w-full text-left px-4 py-2 hover:bg-pink-50 flex items-center gap-2"
                                    @click="showMediaScreen = true; mediaTab = 'sent'; showMenu = false">
                                    <span>Media</span>
                                </button>
                                <!-- Block User Button -->
                                <button type="button"
                                    class="w-full text-left px-4 py-2 hover:bg-pink-50 flex items-center gap-2 text-red-500"
                                    @click="showBlockModal = true; showMenu = false"
                                    x-text="$store.messaging.receiver.is_blocked ? 'Unblock User' : 'Block User'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-12.728 12.728M5.636 5.636l12.728 12.728" />
                                    </svg>
                                </button>
                                <!-- Mute -->
                                <button type="button"
                                    class="w-full text-left px-4 py-2 hover:bg-pink-50 flex items-center gap-2"
                                    @click="showMuteModal = true; showMenu = false">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5v14l11-7z" />
                                    </svg>
                                    <span>Mute User</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                    <template x-if="!showMediaScreen">
                        <div id="chat-scroll" x-data="{ showLoadMore: false }" @scroll="$store.messaging.scrollHandler; showLoadMore = $event.target.scrollTop <= 10" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gradient-to-b from-white to-gray-50 relative">
                            <!-- 🔼 Load More Button -->
                            <div x-show="showLoadMore && !$store.messaging.topReached && $store.messaging.messages.length >= 20"
                                class="text-center py-2">

                                <button @click="$store.messaging.fetchMoreMessages()"
                                        :disabled="$store.messaging.isFetchingMore"
                                        class="text-sm text-pink-600 hover:underline flex items-center justify-center gap-2">

                                    <template x-if="!$store.messaging.isFetchingMore">
                                        <span>Load More</span>
                                    </template>

                                    <template x-if="$store.messaging.isFetchingMore">
                                        <svg class="animate-spin h-4 w-4 text-pink-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                        </svg>
                                    </template>

                                </button>
                            </div>

                            <!-- 🟣 Loading Full Chat -->
                            <template x-if="$store.messaging.isLoading">
                                <div class="absolute inset-0 flex items-center justify-center bg-white/70 z-10">
                                    <div class="text-center py-4 text-gray-400 text-sm">Loading chat...</div>
                                </div>
                            </template>

                            <!-- 🔵 Loading More Messages (Top) -->
                            <template x-if="$store.messaging.isFetchingMore">
                                <div class="sticky top-0 z-10 text-center text-xs py-2 text-gray-400 bg-gradient-to-b from-white to-transparent">
                                    Loading more messages...
                                </div>
                            </template>
                            <template x-for="message in $store.messaging.messages" :key="message.id">

                                <div class="flex" :class="message.sender_id === $store.messaging.authUser.id ? 'justify-end' : 'justify-start'">
                                    <div class="w-full max-w-[75%] sm:max-w-[60%] px-3 py-2 rounded-xl text-sm shadow relative"
                                        :class="message.sender_id === $store.messaging.authUser.id 
                                            ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white rounded-br-none' 
                                            : 'bg-white border border-gray-200 rounded-bl-none'">

                                        <!-- Attachments Preview (Unified, Spinner until loaded, disables click/navigation while loading) -->
                                        <template x-if="message.attachments?.length">
                                            <div class="relative mt-3 group w-full max-w-full" x-data="{
                                                index: 0,
                                                loading: true,
                                                loadedFiles: [],
                                                minLoadingTime: 5000,
                                                startLoading() {
                                                    this.loading = true;
                                                    this.loadedFiles = Array(message.attachments.length).fill(false);
                                                    const start = Date.now();
                                                    Promise.all(message.attachments.map((file, i) => {
                                                        return new Promise(resolve => {
                                                            const ext = (file.extension || (file.filename ? file.filename.split('.').pop().toLowerCase() : '') || (file.mime_type ? file.mime_type.split('/').pop().toLowerCase() : ''));
                                                            if (['jpg','jpeg','png','gif','webp'].includes(ext)) {
                                                                const img = new Image();
                                                                img.onload = () => { this.loadedFiles[i] = true; resolve(); };
                                                                img.onerror = () => { this.loadedFiles[i] = true; resolve(); };
                                                                img.src = file.url || file.file_path || file.path;
                                                            } else if (['mp4','mov','webm'].includes(ext)) {
                                                                const video = document.createElement('video');
                                                                video.onloadedmetadata = () => { this.loadedFiles[i] = true; resolve(); };
                                                                video.onerror = () => { this.loadedFiles[i] = true; resolve(); };
                                                                video.src = file.url || file.file_path || file.path;
                                                            } else {
                                                                this.loadedFiles[i] = true;
                                                                resolve();
                                                            }
                                                        });
                                                    })).then(() => {
                                                        const elapsed = Date.now() - start;
                                                        setTimeout(() => { this.loading = false; }, Math.max(0, this.minLoadingTime - elapsed));
                                                    });
                                                }
                                            }"
                                            x-init="startLoading()"
                                            >
                                                <!-- Spinner Overlay -->
                                                <template x-if="loading">
                                                    <div class="absolute inset-0 flex items-center justify-center bg-white/80 z-0">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                                        </svg>
                                                    </div>
                                                </template>
                                                <template x-if="message.attachments[index]">
                                                    <div class="relative w-full max-w-full overflow-hidden rounded-2xl shadow-lg border border-gray-200 bg-white" style="height: 320px;">
                                                        <!-- Image Preview -->
                                                        <template x-if="!loading && ['jpg','jpeg','png','gif','webp'].includes((message.attachments[index].extension || (message.attachments[index].filename ? message.attachments[index].filename.split('.').pop().toLowerCase() : '') || (message.attachments[index].mime_type ? message.attachments[index].mime_type.split('/').pop().toLowerCase() : '')))">
                                                            <img :src="message.attachments[index].url || message.attachments[index].file_path || message.attachments[index].path" class="object-cover w-full h-full transition-transform duration-200 group-hover:scale-105" style="height: 320px;" />
                                                        </template>
                                                        <!-- Video Thumbnail Preview -->
                                                        <template x-if="!loading && ['mp4','mov','webm'].includes((message.attachments[index].extension || (message.attachments[index].filename ? message.attachments[index].filename.split('.').pop().toLowerCase() : '') || (message.attachments[index].mime_type ? message.attachments[index].mime_type.split('/').pop().toLowerCase() : '')))">
                                                            <div class="relative w-full h-full">
                                                                <video
                                                                    :src="message.attachments[index].url || message.attachments[index].file_path || message.attachments[index].path"
                                                                    class="object-cover w-full h-full rounded-lg mb-2"
                                                                    style="height: 320px;"
                                                                    autoplay="false"
                                                                    muted
                                                                    playsinline
                                                                    @play="$event.target.pause()"
                                                                ></video>
                                                                <!-- Play button overlay (smaller) -->
                                                                <button
                                                                    type="button"
                                                                    class="absolute inset-0 flex items-center justify-center"
                                                                    style="pointer-events: none;"
                                                                    tabindex="-1"
                                                                >
                                                                    <svg class="h-10 w-10 text-white/80 drop-shadow-lg" fill="currentColor" viewBox="0 0 64 64">
                                                                        <circle cx="32" cy="32" r="32" fill="black" fill-opacity="0.4"/>
                                                                        <polygon points="26,20 50,32 26,44" fill="white"/>
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </template>
                                                        <!-- Fallback for other files -->
                                                        <template x-if="!loading && !['jpg','jpeg','png','gif','webp','mp4','mov','webm'].includes((message.attachments[index].extension || (message.attachments[index].filename ? message.attachments[index].filename.split('.').pop().toLowerCase() : '') || (message.attachments[index].mime_type ? message.attachments[index].mime_type.split('/').pop().toLowerCase() : '')))">
                                                            <div class="flex flex-col items-center justify-center h-full p-6 text-xs italic text-gray-500 text-center">
                                                                <span x-text="message.attachments[index].name || message.attachments[index].file_name || message.attachments[index].filename"></span><br>
                                                                <a :href="message.attachments[index].url || message.attachments[index].file_path || message.attachments[index].path" target="_blank" class="text-blue-500 underline">Download</a>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>
                                                <!-- Navigation Arrows (disabled while loading) -->
                                                <button @click.stop="if(!loading) index = index > 0 ? index - 1 : index"
                                                        :disabled="loading || index === 0"
                                                        :class="(loading || index === 0) ? 'opacity-30 cursor-not-allowed' : ''"
                                                        class="absolute left-0 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-pink-100 text-pink-500 p-2 rounded-full shadow-lg border border-pink-100 text-lg">
                                                    ‹
                                                </button>
                                                <button @click.stop="if(!loading) index = index < message.attachments.length - 1 ? index + 1 : index"
                                                        :disabled="loading || index === message.attachments.length - 1"
                                                        :class="(loading || index === message.attachments.length - 1) ? 'opacity-30 cursor-not-allowed' : ''"
                                                        class="absolute right-0 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-pink-100 text-pink-500 p-2 rounded-full shadow-lg border border-pink-100 text-lg">
                                                    ›
                                                </button>
                                                <!-- Click to open preview modal (disabled while loading) -->
                                                <div class="absolute inset-0" :class="loading ? 'pointer-events-none' : ''"
                                                    @click.stop="if(!loading) $dispatch('open-preview-modal', { files: message.attachments.map(a => ({...a, is_attachment: true})), index })">
                                                </div>
                                                <!-- File Info -->
                                                <template x-if="message.attachments[index]">
                                                    <div class="absolute bottom-0 left-1/2 -translate-x-1/2 bg-white/90 text-xs px-3 py-1 rounded-t-xl shadow flex items-center gap-2 font-semibold">
                                                        <template x-if="['jpg','jpeg','png','gif','webp'].includes(message.attachments[index]?.extension?.toLowerCase())">
                                                            <span class="text-pink-500">🖼️</span>
                                                        </template>
                                                        <template x-if="['mp4','mov','webm'].includes(message.attachments[index]?.extension?.toLowerCase())">
                                                            <span class="text-purple-500">🎬</span>
                                                        </template>
                                                        <span x-text="`${index + 1} / ${message.attachments.length}`"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>

                                        <template x-if="message.attachments?.length">
                                            <div class="my-2"></div>
                                        </template>

                                        <!-- 💬 Message Body -->
                                        <div x-text="message.body" class="break-words mt-2"></div>

                                        <div class="my-2"></div>

                                        <!-- ⏰ Timestamp & ✅ Read Status -->
                                        <div class="flex justify-end items-center mt-1">
                                            <div class="text-[0.65rem]" 
                                                :class="message.sender_id === $store.messaging.authUser.id ? 'text-white/70' : 'text-gray-400'"
                                                x-text="new Date(message.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})">
                                            </div>
                                            <template x-if="message.sender_id === $store.messaging.authUser.id">
                                                <svg x-show="message.read_at" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1 text-white/70" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Media Screen Template with Date Grouping (No Blade curly braces, Alpine only) -->
                    <template x-if="showMediaScreen">
                        <div class="flex flex-col flex-1 overflow-hidden bg-white">
                            <!-- Header: Back arrow + Media Tabs -->
                            <div class="sticky top-0 z-10 px-3 py-2 sm:px-4 sm:py-3 border-b bg-gradient-to-r from-pink-50 to-purple-50 flex items-center">
                                <button @click="showMediaScreen = false"
                                    class="flex items-center justify-center w-8 h-8 bg-white/90 hover:bg-pink-100 rounded-full shadow-sm border border-gray-200 text-pink-600 text-lg mr-4"
                                    title="Back to Chat">
                                    ←
                                </button>
                                <div class="flex gap-2">
                                    <button
                                        @click="mediaTab = 'sent'"
                                        :class="mediaTab === 'sent' ? 'text-pink-600 font-bold border-b-2 border-pink-500' : 'text-gray-500'"
                                        class="px-4 py-2 transition"
                                    >Sent Media</button>
                                    <button
                                        @click="mediaTab = 'received'"
                                        :class="mediaTab === 'received' ? 'text-pink-600 font-bold border-b-2 border-pink-500' : 'text-gray-500'"
                                        class="px-4 py-2 transition"
                                    >Received Media</button>
                                </div>
                            </div>
                            <!-- Media Grouped by Date -->
                            <div class="flex-1 overflow-y-auto p-4">
                                <template x-for="(group, groupIdx) in (() => {
                                    // 1. Get all media for current tab
                                    const mediaList = $store.messaging.messages
                                        .filter(m => m.attachments?.length &&
                                            (mediaTab === 'sent'
                                                ? m.sender_id === $store.messaging.authUser.id
                                                : m.sender_id !== $store.messaging.authUser.id)
                                        )
                                        .flatMap(m => m.attachments.map((a, i) => ({
                                            ...a,
                                            _msgId: m.id,
                                            _idx: i,
                                            _created_at: m.created_at
                                        })))
                                        .sort((a, b) => new Date(b._created_at) - new Date(a._created_at)); // newest first

                                    // 2. Group by date string
                                    const groups = {};
                                    mediaList.forEach(media => {
                                        const d = new Date(media._created_at);
                                        const today = new Date();
                                        const yesterday = new Date();
                                        yesterday.setDate(today.getDate() - 1);

                                        let label;
                                        if (d.toDateString() === today.toDateString()) label = 'Today';
                                        else if (d.toDateString() === yesterday.toDateString()) label = 'Yesterday';
                                        else label = d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });

                                        if (!groups[label]) groups[label] = [];
                                        groups[label].push(media);
                                    });

                                    // 3. Return as array of { label, items }
                                    return Object.entries(groups).map(([label, items]) => ({ label, items }));
                                })()" :key="groupIdx">
                                    <div class="mb-8">
                                        <div class="text-xs font-bold text-pink-600 mb-2 uppercase tracking-wide" x-text="group.label"></div>
                                        <div class="grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));">
                                            <template x-for="(media, mediaIdx) in group.items" :key="`${media._msgId}-${media._idx}`">
                                                <div class="aspect-square bg-white border border-gray-300 rounded-xl overflow-hidden relative group cursor-pointer"
                                                    @click="$dispatch('open-preview-modal', { files: group.items, index: mediaIdx })">
                                                    <template x-if="['jpg','jpeg','png','gif','webp'].includes(media.extension)">
                                                        <img :src="media.url || media.file_path || media.path" class="w-full h-full object-cover" alt="">
                                                    </template>
                                                    <template x-if="['mp4','mov','webm'].includes(media.extension)">
                                                        <video :src="media.url || media.file_path || media.path" muted playsinline preload="metadata" class="w-full h-full object-cover bg-black"></video>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="(() => {
                                    const mediaList = $store.messaging.messages
                                        .filter(m => m.attachments?.length &&
                                            (mediaTab === 'sent'
                                                ? m.sender_id === $store.messaging.authUser.id
                                                : m.sender_id !== $store.messaging.authUser.id)
                                        )
                                        .flatMap(m => m.attachments);
                                    return mediaList.length === 0;
                                })()">
                                    <div class="text-center text-gray-400 text-sm py-4">No media found.</div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Message Input -->
                    <template x-if="!showMediaScreen">
                        <div class="sticky bottom-0 z-10 p-3 border-t bg-white">
                            <!-- File Previews -->
                            <div 
                                x-show="$store.messaging.selectedFiles.length > 0" 
                                class="flex flex-row gap-2 overflow-x-auto mb-2"
                                style="padding-bottom: 2px;"
                            >
                                <template x-for="(file, index) in $store.messaging.selectedFiles" :key="file.id || index">
                                    <div class="relative group flex-shrink-0 w-20 h-20 rounded-lg border border-gray-200 bg-white overflow-hidden"
                                        @click="$dispatch('open-preview-modal', { files: $store.messaging.selectedFiles, index })">
                                        <!-- File Type Icon (top right, no bg, only 3 types) -->
                                        <div class="absolute top-1 right-1 z-20 text-xl select-none">
                                            <template x-if="['jpg','jpeg','png','gif','webp'].includes((file.extension || (file.filename ? file.filename.split('.').pop().toLowerCase() : '')))">
                                                <span title="Image">🖼️</span>
                                            </template>
                                            <template x-if="['mp4','mov','webm'].includes((file.extension || (file.filename ? file.filename.split('.').pop().toLowerCase() : '')))">
                                                <span title="Video">🎬</span>
                                            </template>
                                            <template x-if="['mp3','wav','ogg'].includes((file.extension || (file.filename ? file.filename.split('.').pop().toLowerCase() : '')))">
                                                <span title="Audio">🎵</span>
                                            </template>
                                        </div>
                                        <template x-if="(file.extension || (file.filename ? file.filename.split('.').pop().toLowerCase() : '')) && ['mp4','mov','webm'].includes(file.extension || (file.filename ? file.filename.split('.').pop().toLowerCase() : ''))">
                                            <video :src="file.url || file.path" muted playsinline preload="metadata" class="w-full h-full object-cover bg-black"></video>
                                        </template>
                                        <template x-if="(file.extension || (file.filename ? file.filename.split('.').pop().toLowerCase() : '')) && ['jpg','jpeg','png','gif','webp'].includes(file.extension || (file.filename ? file.filename.split('.').pop().toLowerCase() : ''))">
                                            <img :src="file.url || file.path" class="w-full h-full object-cover" alt="">
                                        </template>
                                        <template x-if="(file.extension || (file.filename ? file.filename.split('.').pop().toLowerCase() : '')) && ['mp3','wav','ogg'].includes(file.extension || (file.filename ? file.filename.split('.').pop().toLowerCase() : ''))">
                                            <div class="flex flex-col items-center justify-center w-full h-full text-blue-500">
                                                <span class="text-4xl">🎵</span>
                                                <audio :src="file.url || file.path" controls class="w-full mt-1"></audio>
                                            </div>
                                        </template>
                                        <button 
                                            @click="$store.messaging.removeFile(index)" 
                                            class="absolute top-1 left-1 bg-white/80 hover:bg-white text-red-500 rounded-full p-1 text-xs shadow"
                                            title="Remove"
                                        >×</button>
                                    </div>
                                </template>
                            </div>

                            <form @submit.prevent="handleSend" class="flex items-center gap-2">
                                <button type="button" 
                                    @click="showMediaModal = true"
                                    :disabled="$store.messaging.selectedFiles.length >= 20 || $store.messaging.receiver.is_blocked"
                                    class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-pink-500 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                    title="Add files (max 20)">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </button>
                                <textarea 
                                    x-model="newMessage"
                                    placeholder="Type a message..."
                                    rows="1"
                                    class="flex-1 px-4 py-2 border rounded-full text-sm resize-none focus:outline-none focus:ring-2 focus:ring-pink-500 max-h-[6.5rem] overflow-y-auto"
                                    @input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 104) + 'px'"
                                    :disabled="$store.messaging.receiver.is_blocked"
                                ></textarea>
                                <button type="submit"
                                    class="bg-pink-500 text-white px-4 py-2 rounded-full text-sm hover:bg-pink-600 transition flex items-center gap-1"
                                    :disabled="(!newMessage.trim() && $store.messaging.selectedFiles.length === 0) || $store.messaging.isLoading || $store.messaging.receiver.is_blocked">
                                    <span x-show="!$store.messaging.isLoading">Send</span>
                                    <span x-show="$store.messaging.isLoading">Sending...</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                </button>
                            </form>

                            <template x-if="$store.messaging.receiver.is_blocked">
                                <div class="text-center text-red-500 py-2 text-xs font-semibold">
                                    You have blocked this user. Unblock to continue messaging.
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

                    <!-- 🚨 Empty State (Large Screens Only) -->
            <template x-if="!$store.messaging.receiver">
                <div class="hidden lg:flex items-center justify-center h-full bg-gray-50 rounded-xl border-2 border-dashed border-gray-200 text-gray-400">
                    <div class="text-center p-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <h3 class="text-lg font-medium mb-1">No conversation selected</h3>
                        <p class="text-sm">Select a chat from the sidebar to start messaging</p>
                    </div>
                </div>
            </template>
        </div>


        <!-- Right Sidebar - Desktop Navigation -->
        <div class="hidden lg:block w-1/5">
            <div class="sticky top-24 space-y-4">
                <h3 class="text-xl font-semibold mb-4">Navigation</h3>
                <a href="{{ route('home') }}" class="block px-4 py-2 rounded-lg font-medium text-sm text-gray-700 hover:bg-pink-100 transition">
                    🏠 Home
                </a>
                <div class="relative">
                  <a href="/messages" class="block px-4 py-2 rounded-lg font-medium text-sm text-gray-700 hover:bg-pink-100 transition">
                      💌 Messages
                  </a>
                  <template x-if="$store.messaging.unreadConversationsCount > 0">
                    <span class="absolute top-1 right-3 bg-pink-500 text-white text-xs font-bold rounded-full px-1.5 py-0.5 min-w-[18px] text-center z-10" x-text="$store.messaging.unreadConversationsCount"></span>
                  </template>
                </div>
                <a href="/my-vibes" class="block px-4 py-2 rounded-lg font-medium text-sm text-gray-700 hover:bg-pink-100 transition">
                    💫 My Boards
                </a>
                <a href="/notifications" class="block px-4 py-2 rounded-lg font-medium text-sm text-gray-700 hover:bg-pink-100 transition">
                    🔔 Notifications
                </a>
                <a href="/settings" class="block px-4 py-2 rounded-lg font-medium text-sm text-gray-700 hover:bg-pink-100 transition">
                    ⚙️ Settings
                </a>
            </div>
        </div>
    </div>

<!-- File Preview Modal -->
<template x-if="$store.previewModal.show">
    <div class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center" @click.self="$store.previewModal.close()">
        <div class="relative w-full max-w-4xl h-[80vh] bg-white rounded shadow-lg overflow-hidden flex items-center justify-center">
            <template x-if="$store.previewModal.previewType === 'image'">
                <img :src="$store.previewModal.currentFile.url" class="max-w-full max-h-full object-contain" />
            </template>
            <template x-if="$store.previewModal.previewType === 'video'">
                <video :src="$store.previewModal.currentFile.url" class="max-w-full max-h-full object-contain" controls playsinline />
            </template>
            <template x-if="$store.previewModal.previewType === 'file'">
                <div class="text-center text-sm text-white">
                    <p class="mb-2 italic">File preview not supported</p>
                    <a :href="$store.previewModal.currentFile.url" target="_blank" class="underline">Download</a>
                </div>
            </template>
            <!-- Navigation -->
            <button @click="$store.previewModal.index = Math.max(0, $store.previewModal.index - 1)"
                    :disabled="$store.previewModal.index === 0"
                    class="absolute left-4 top-1/2 transform -translate-y-1/2 text-white text-2xl">‹</button>
            <button @click="$store.previewModal.index = Math.min($store.previewModal.files.length - 1, $store.previewModal.index + 1)"
                    :disabled="$store.previewModal.index === $store.previewModal.files.length - 1"
                    class="absolute right-4 top-1/2 transform -translate-y-1/2 text-white text-2xl">›</button>
            <!-- Close -->
            <button @click="$store.previewModal.close()" class="absolute top-4 right-4 text-white text-xl">×</button>
        </div>
    </div>
</template>

<style>
[x-cloak] { display: none !important; }
</style>

    <!-- End File Picker Modal -->
@endsection

@push('scripts')
<script>
    window.__inbox_contacts = @json($contacts);
    window.__inbox_receiver = @json($receiver);
    window.__inbox_messages = @json($messages);
</script>
<script>
document.addEventListener('alpine:init', () => {
Alpine.store('messaging', {
    authUser: @json(auth()->user()),
    contacts: @json($contacts),
    receiver: @json($receiver),
    messages: [],
    selectedFiles: [],
    isLoading: false,
    isFetchingMore: false,
    topReached: false,
    offset: 0,
    error: null,
    searchQuery: '',
    autoScrollFailed: false,
    showRecentChats: window.innerWidth < 1024,
    unreadConversationsCount: 0,
    activeTab: 'messages',
    requestsSubTab: 'received',

    async fetchUnreadConversationsCount() {
        try {
            const res = await fetch('/messages/unread-conversations-count', {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            this.unreadConversationsCount = data.count || 0;
        } catch (e) {
            this.unreadConversationsCount = 0;
        }
    },

    get tabbedContacts() {
        if (this.activeTab === 'messages') {
            return this.contacts.filter(c =>
                c.is_friend && c.has_messaged
            );
        }
        if (this.activeTab === 'requests') {
            if (this.requestsSubTab === 'received') {
                // Received requests: messages from people who follow the user, but user doesn't follow back
                return this.contacts.filter(c =>
                    c.follows_user && !c.user_follows && !c.is_friend && c.has_messaged
                );
            }
            if (this.requestsSubTab === 'sent') {
                // Sent requests: messages sent by user to people they follow, but those people don't follow back
                return this.contacts.filter(c =>
                    c.user_follows && !c.follows_user && !c.is_friend && c.has_messaged
                );
            }
        }
        return [];
    },

    init() {
        // Load recent chats list
        this.loadContacts();
        this.fetchUnreadConversationsCount();

        // 🧠 If there's a receiver passed in from the backend, load their messages
        if (this.receiver) {
            this.loadInitialMessages(this.receiver.id);
        }

        // 💡 Set `showRecentChats` ONLY if we're on mobile and no receiver is loaded
        this.showRecentChats = window.innerWidth < 1024 && !this.receiver;
    },

    async loadContacts() {
        try {
            const res = await fetch('/api/recent-messages');
            const contentType = res.headers.get('Content-Type');
            if (!res.ok || !contentType.includes('application/json')) {
                throw new Error('Non-JSON response');
            }
            const data = await res.json();
            this.contacts = data;
        } catch (err) {
            console.error('[LOAD CONTACTS] Failed:', err);
            this.contacts = [];
        }
    },

    get sortedContacts() {
        return this.filteredContacts
            .slice()
            .sort((a, b) => {
                const aTime = new Date(a.last_message?.created_at || 0).getTime();
                const bTime = new Date(b.last_message?.created_at || 0).getTime();
                return bTime - aTime; // Newest first
            });
    },

    unreadMessagesCount() {
        return this.contacts.filter(c => c.is_friend && c.has_messaged && c.unread_count > 0).length;
    },

    unreadRequestsCount() {
        return this.contacts.filter(c => c.follows_user && !c.user_follows && !c.is_friend && c.has_messaged && c.unread_count > 0).length;
    },

    get filteredContacts() {
        try {
            return this.contacts.filter(contact =>
                (contact?.username ?? '').toLowerCase().includes(this.searchQuery.toLowerCase())
            );
        } catch (err) {
            console.error('[FILTERED CONTACTS] ERROR:', err);
            return [];
        }
    },

    async loadInitialMessages(receiverId) {
        console.log('[Select Contact] Clicked on:', receiverId);
        this.isLoading = true;
        this.messages = [];
        this.offset = 0;
        this.topReached = false;

        try {
            const res = await fetch(`/api/messages/thread/${receiverId}?limit=5`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            const contentType = res.headers.get('Content-Type');
            if (!res.ok || !contentType.includes('application/json')) {
                throw new Error('Non-JSON response');
            }

            const data = await res.json();
            console.log('[Backend Data for Clicked User]:', data);
            this.receiver = data.receiver;
            this.messages = data.messages;
            this.offset = this.messages.length;

            // Immediately clear unread state for this contact in the sidebar
            const contact = this.contacts.find(c => c.id === receiverId);
            if (contact) {
                contact.should_bold = false;
                contact.unread_count = 0;
            }

            // Update unread conversations count
            this.fetchUnreadConversationsCount();

            await Alpine.nextTick();
            const el = document.getElementById('chat-scroll');
            if (el) el.scrollTop = el.scrollHeight;

        } catch (error) {
            console.error('[Initial Load] Failed:', error);
            this.messages = [];
        } finally {
            this.isLoading = false;
        }
    },

    async fetchMoreMessages({ manual = true } = {}) {
        if (this.isFetchingMore || this.topReached || !this.receiver?.id) return;

        this.isFetchingMore = true;

        try {
            const res = await fetch(`/api/messages/thread/${this.receiver.id}?offset=${this.offset}&limit=10`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            const contentType = res.headers.get('Content-Type');
            if (!res.ok || !contentType.includes('application/json')) {
                throw new Error('Non-JSON response');
            }

            const data = await res.json();

            if (data.messages.length === 0) {
                this.topReached = true;
                this.autoScrollFailed = !manual;
                return;
            }

            this.autoScrollFailed = false;

            // 🧠 Preserve scroll position by capturing height before and after
            const el = document.getElementById('chat-scroll');
            const prevScrollHeight = el?.scrollHeight;

            this.messages = [...data.messages, ...this.messages];
            this.offset += data.messages.length;

            await Alpine.nextTick();

            if (manual && el) {
                const newScrollHeight = el.scrollHeight;
                const heightDiff = newScrollHeight - prevScrollHeight;
                el.scrollTop += heightDiff;
            }

        } catch (e) {
            console.error('[Fetch More] Failed:', e);
        } finally {
            this.isFetchingMore = false;
        }
    },

    scrollHandler: (event) => {
        const el = event.target;
        const store = Alpine.store('messaging');
        if (el.scrollTop === 0 && store.receiver?.id) {
            store.fetchMoreMessages();
        }
    },

    async sendMessage(message, files = []) {
        console.log('[SEND] Starting sendMessage...');
        if (!this.receiver || (!message.trim() && files.length === 0)) {
            console.warn('[SEND] No receiver or empty message/files, aborting.');
            return;
        }

        this.isLoading = true;
        this.error = null;

        // 1. Add temp message immediately so spinner shows
        const tempId = 'pending-' + Date.now();
        const tempMessage = {
            id: tempId,
            body: message,
            sender_id: this.authUser.id,
            receiver_id: this.receiver.id,
            created_at: new Date().toISOString(),
            attachments: files.length ? files.map((file, idx) => {
                console.log(`[SEND] Adding pending attachment for file #${idx}`, file);
                return { pending: true };
            }) : [],
            pending: true
        };
        console.log('[SEND] Pushing temp message:', tempMessage);
        this.messages.push(tempMessage);

        // 2. Start timer for minimum spinner duration
        const spinnerMinTime = 2000;
        const spinnerStart = Date.now();

        try {
            const formData = new FormData();
            formData.append('body', message);
            formData.append('receiver_id', this.receiver.id);

            files.forEach((file, index) => {
                if (file instanceof File) {
                    formData.append(`files[${index}]`, file);
                    console.log(`[SEND] Appended raw File object at index ${index}:`, file);
                } else {
                    formData.append(`file_ids[${index}]`, file.id);
                    console.log(`[SEND] Appended file by ID at index ${index}:`, file.id);
                }
            });

            console.log('[SEND] Sending fetch request to /messages...');
            const res = await fetch('/messages', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            console.log('[SEND] Fetch response status:', res.status);
            const data = await res.json();
            console.log('[SEND] Response data:', data);

            if (!res.ok || !data.success) {
                this.error = data.error || 'Failed to send message. Please try again.';
                this.showToast(this.error);
                console.warn('[SEND] Backend error:', this.error);
                // Remove the temp message
                this.messages = this.messages.filter(m => m.id !== tempId);
                return;
            }

            // 3. Wait for at least 2 seconds before replacing the temp message
            const elapsed = Date.now() - spinnerStart;
            if (elapsed < spinnerMinTime) {
                await new Promise(resolve => setTimeout(resolve, spinnerMinTime - elapsed));
            }

            // 4. Replace temp message with real message
            console.log('[SEND] Replacing temp message with backend message:', data.message);
            this.messages = this.messages.map(m => m.id === tempId ? data.message : m);

            // ...rest of your logic...
            const contact = this.contacts.find(c => c.id === this.receiver.id);
            if (contact) {
                contact.last_message = {
                    id: data.message.id,
                    body: data.message.body,
                    created_at: data.message.created_at,
                    has_attachment: (data.message.attachments && data.message.attachments.length > 0),
                };
                contact.should_bold = false;
                contact.unread_count = 0;
                contact.has_attachment = (data.message.attachments && data.message.attachments.length > 0);
                console.log('[SEND] Updated contact:', contact);
            }

            await Alpine.nextTick();
            const el = document.getElementById('chat-scroll');
            if (el) {
                el.scrollTop = el.scrollHeight;
                console.log('[SEND] Scrolled chat to bottom.');
            }

            this.selectedFiles = [];
            this.fetchUnreadConversationsCount();
            console.log('[SEND] Finished sendMessage.');
        } catch (e) {
            this.error = 'Failed to send message. Please try again.';
            this.showToast(this.error);
            console.error('[SEND] Exception:', e);
            // Remove the temp message
            this.messages = this.messages.filter(m => m.id !== tempId);
        } finally {
            this.isLoading = false;
            console.log('[SEND] isLoading set to false.');
        }
    },

    addSelectedFiles(files) {
        this.selectedFiles = [...this.selectedFiles, ...files].slice(0, 20);
    },

    removeFile(index) {
        this.selectedFiles.splice(index, 1);
    }
});

Alpine.data('messageInbox', () => ({
    searchQuery: '',
    newMessage: '',
    showModal: false,
    showMediaModal: false,
    selectedFiles: [],
    showRecentChats: window.innerWidth < 1024,
    isDesktop: window.innerWidth >= 1024,
    focusedPreviewFiles: [],
    focusedPreviewIndex: null,
    showPreviewModal: false,
    previewFiles: [],
    previewIndex: null,
    showMediaScreen: false,
    mediaTab: 'sent', 
    showBlockModal: false,
    showMuteModal: false,
    muteDuration: '8h',

    async muteUser() {
        if (!this.$store.messaging.receiver?.id) return;
        let duration = this.muteDuration;
        let until = null;
        if (duration === '8h') until = dayjs().add(8, 'hour').toISOString();
        else if (duration === '24h') until = dayjs().add(24, 'hour').toISOString();
        else if (duration === '1w') until = dayjs().add(7, 'day').toISOString();
        // 'forever' means until stays null

        try {
            const res = await fetch('/mute-user', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    muted_id: this.$store.messaging.receiver.id,
                    mute_until: until
                })
            });
            const data = await res.json();
            if (res.ok && data.success) {
                this.showToast('User muted successfully');
                this.showMuteModal = false;
            } else {
                this.showToast(data.error || 'Failed to mute user');
            }
        } catch (e) {
            this.showToast('Failed to mute user');
        }
    },

    openPreviewModal(files, index = 0) {
        this.showPreviewModal = true;
        this.previewFiles = files;
        this.previewIndex = index;
    },

    init() {
        this.$store.messaging.init();

        // 🧠 On mobile: show recent chats only if there's no receiver
        if (!this.isDesktop) {
            this.showRecentChats = !this.$store.messaging.receiver;
        }

        // 🔁 Watch for receiver change and auto-hide chat list on desktop
        this.$watch('$store.messaging.receiver', () => {
            if (this.isDesktop) {
                this.showRecentChats = false;
            }
        });

        // 📱 Handle screen resize
        window.addEventListener('resize', () => {
            this.isDesktop = window.innerWidth >= 1024;
            if (this.isDesktop) {
                this.showRecentChats = false;
            }
        });
    },

    selectUser(user) {
        console.log('[CHAT SELECTED] User:', user);
        this.$store.messaging.loadInitialMessages(user.id);
        this.showRecentChats = false;
        history.pushState(null, null, `/messages?receiver_id=${user.id}`);
    },

    async handleSend() {
        if (!this.newMessage.trim() && this.$store.messaging.selectedFiles.length === 0) return;

        try {
            await this.$store.messaging.sendMessage(
                this.newMessage,
                this.$store.messaging.selectedFiles
            );
            this.newMessage = '';
        } catch (error) {
            this.showToast('Failed to send message');
        }
    },

    showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 px-4 py-2 bg-black text-white rounded-lg';
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);
    },

    async blockUser() {
        if (!this.$store.messaging.receiver?.id) return;
        const isBlocked = this.$store.messaging.receiver.is_blocked;
        try {
            const res = await fetch(isBlocked ? '/unblock-user' : '/block-user', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    blocked_id: this.$store.messaging.receiver.id,
                    block_type: 'message'
                })
            });
            const data = await res.json();
            if (res.ok && data.success) {
                this.showToast(isBlocked ? 'User unblocked successfully' : 'User blocked successfully');
                this.showBlockModal = false;
                // Update block state in UI
                this.$store.messaging.receiver.is_blocked = !isBlocked;
            } else {
                this.showToast(data.error || 'Failed to update block state');
            }
        } catch (e) {
            this.showToast('Failed to update block state');
        }
    }
}));

    // File Picker Component
    Alpine.data('filePicker', () => ({
        files: [],
        selectedIds: [],
        filters: {
            type: 'all',
            contentType: 'all',
            sort: 'latest'
        },
        lists: [],
        activeListId: 'all',
        showFileListsPanel: true,
        isLoadingLists: false,
        isLoadingFiles: false,

        get showModal() {
            return Alpine.store('filePicker').showModal;
        },
        set showModal(value) {
            Alpine.store('filePicker').showModal = value;
        },

        setList(id) {
            this.activeListId = id;
            this.files = [];
            this.filesOffset = 0;
            this.filesHasMore = true;
            this.loadFiles();
            this.showFileListsPanel = false; // if you want to hide lists after selection
        },
        
        async loadLists() {
            try {
                console.log('[🔄 LISTS] Fetching file lists from /files/lists-with-counts ...');

                const response = await fetch('/files/lists-with-counts');
                const contentType = response.headers.get('Content-Type');

                if (!response.ok || !contentType.includes('application/json')) {
                    console.error('[❌ LISTS] Bad response:', response.status, contentType);
                    throw new Error(`Invalid response format. Status: ${response.status}, Content-Type: ${contentType}`);
                }

                const dbLists = await response.json();
                console.log('[📦 LISTS] Raw response:', dbLists);

                // Inject "All Media" list manually
                const allMediaList = {
                    id: 'all',
                    name: '🎞️ All Media',
                    imageCount: dbLists.allMedia?.imageCount ?? 0,
                    videoCount: dbLists.allMedia?.videoCount ?? 0,
                    safeCount: dbLists.allMedia?.safeCount ?? 0,
                    adultCount: dbLists.allMedia?.adultCount ?? 0,
                    totalCount: dbLists.allMedia?.totalCount ?? 0
                };

                this.lists = [allMediaList, ...(Array.isArray(dbLists.lists) ? dbLists.lists : [])];

                console.log(`[✅ LISTS] this.lists:`, this.lists);
                console.log(`[✅ LISTS] ${this.lists.length} total lists (including All Media)`);
                if (this.lists.length > 0) {
                    this.lists.forEach((list, i) => {
                        console.log(`#${i}: id=${list.id}, name=${list.name}, images=${list.imageCount}, videos=${list.videoCount}`);
                    });
                } else {
                    console.warn('[⚠️ LISTS] No lists found.');
                }

            } catch (error) {
                console.error('[❌ LISTS] Failed to load lists:', error);
            }
        },
        
        async loadFiles() {
            // Reset offset and hasMore if needed (optional, for pagination)
            if (typeof this.filesOffset === 'undefined') this.filesOffset = 0;
            if (typeof this.filesHasMore === 'undefined') this.filesHasMore = true;

            if (!this.filesHasMore) return;

            try {
                const params = new URLSearchParams({
                    offset: this.filesOffset,
                    limit: 20,
                    type: this.filters.type || 'all',
                    content: this.filters.contentType || 'all',
                    sort: this.filters.sort || 'latest'
                });

                // Use correct endpoint for all media or a specific list
                const url = this.activeListId === 'all'
                    ? `/user-files?${params.toString()}`
                    : `/file-lists/${this.activeListId}/items?${params.toString()}`;

                const res = await fetch(url);
                const data = await res.json();

                // Get files array from response (handles both endpoints)
                let newFiles = Array.isArray(data.files)
                    ? data.files
                    : Array.isArray(data)
                        ? data
                        : [];

                // Add extension property to each file
                newFiles = newFiles.map(file => ({
                    ...file,
                    extension: file.filename ? file.filename.split('.').pop().toLowerCase() : ''
                }));

                // Deduplicate by id
                this.files = this.filesOffset === 0
                    ? newFiles
                    : [
                        ...this.files,
                        ...newFiles.filter(f => !this.files.some(existing => existing.id === f.id))
                    ];

                this.filesOffset += newFiles.length;
                this.filesHasMore = newFiles.length >= 20;
            } catch (err) {
                this.showToast('Could not load files', true);
                console.error('File fetch failed:', err);
            }
        },

        toggleFileSelection(id) {
            const file = this.files.find(f => f.id === id);
            if (!file) return;

            let wasSelected = this.selectedIds.includes(id);
            if (wasSelected) {
                this.selectedIds = this.selectedIds.filter(fid => fid !== id);
            } else {
                // Only allow up to 20 files total (including already selected in messaging.selectedFiles)
                const totalSelected = this.selectedIds.length + (this.$store.messaging.selectedFiles?.length || 0);
                if (totalSelected >= 20) {
                    this.showToast('You can select up to 20 files only.', true);
                    // Uncheck automatically (do not add)
                    return;
                }
                this.selectedIds.push(id);
            }
        },
        
        addSelectedFiles() {
            // Only add if total will not exceed 20
            const alreadySelected = this.$store.messaging.selectedFiles || [];
            const newSelectedFiles = this.files.filter(file => this.selectedIds.includes(file.id));
            // Remove duplicates by id
            const allFiles = [...alreadySelected, ...newSelectedFiles.filter(f => !alreadySelected.some(a => a.id === f.id))];
            if (allFiles.length > 20) {
                this.showToast('You can only add up to 20 files in total.', true);
                return;
            }
            this.$store.messaging.selectedFiles = allFiles;
            this.showFileListsPanel = true;
            this.showMediaModal = false; // closes the modal
        },
        
        init() {
            this.loadLists();
            this.loadFiles();
            this.showFileListsPanel = true;
            // Sync selectedIds with already selected files when modal opens
            this.$watch('showMediaModal', (val) => {
                if (val) {
                    // When opening, pre-check files already in selectedFiles
                    this.selectedIds = (this.$store.messaging.selectedFiles || []).map(f => f.id);
                } else {
                    // When closing, clear selection
                    this.selectedIds = [];
                }
            });
        }
    }));

    Alpine.store('previewModal', {
        show: false,
        files: [],
        index: 0,
        videoCurrentTime: 0,
        videoDuration: 0,
        videoMuted: true,
        hoverTime: null,

        formatTime(seconds) {
            if (!seconds || isNaN(seconds)) return "00:00";
            const m = Math.floor(seconds / 60);
            const s = Math.floor(seconds % 60);
            return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        },

        open(files, startIndex = 0) {
            this.files = files;
            this.index = startIndex;
            this.show = true;
        },

        close() {
            this.show = false;
            this.files = [];
            this.index = 0;
        },

        get currentFile() {
            return this.files[this.index] || {};
        },

        get previewType() {
            const ext = (this.currentFile?.extension || '').toLowerCase();
            const imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            const videoTypes = ['mp4', 'mov', 'webm'];

            if (imageTypes.includes(ext)) return 'image';
            if (videoTypes.includes(ext)) return 'video';
            return 'file';
        }
    });
});
</script>
@endpush
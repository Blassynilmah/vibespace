@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-6 sm:py-10 pb-20 sm:pb-32" x-data="createBoardForm()" x-init="init()">

    {{-- 🧭 Tab Navigation --}}
    <div class="sticky top-[50px] bg-white px-2 sm:px-0">
        <div class="flex justify-center gap-2 sm:gap-4">
            <button
                @click="
                    activeTab = 'moodboard';
                    selectedFileIds = [];
                    form.images = [];
                    videoForm.video = null;
                "
                :class="activeTab === 'moodboard'
                    ? 'bg-white text-pink-600 font-semibold shadow rounded-full hover:bg-pink-100'
                    : 'text-gray-400'"
                class="px-4 py-2 text-xs sm:text-sm md:text-base transition">
                Create Moodboard
            </button>
            <button
                @click="
                    activeTab = 'Teasers';
                    selectedFileIds = [];
                    form.images = [];
                    videoForm.video = null;
                "
                :class="activeTab === 'Teasers'
                    ? 'bg-white text-pink-600 font-semibold shadow rounded-full hover:bg-pink-100'
                    : 'text-gray-400'"
                class="px-4 py-2 text-xs sm:text-sm md:text-base transition">
                Create Teaser
            </button>
        </div>
    </div>

    <template x-if="activeTab === 'moodboard'">

        <!-- Main Form -->
        <form @submit.prevent="submitForm" class="bg-white p-6 sm:p-8 rounded-xl sm:rounded-2xl shadow-lg sm:shadow-xl space-y-4 sm:space-y-6 border border-gray-100">
            <!-- Honeypot Field -->

            <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">

            <!-- Title Field -->
            <div>
                <label class="block text-sm sm:text-base font-semibold mb-1 text-gray-700">Title</label>
                <input type="text" x-model="form.title"
                        class="w-full rounded-lg sm:rounded-xl border border-gray-300 focus:ring-2 focus:ring-pink-400 focus:outline-none px-4 py-2 text-sm sm:text-base"
                        autofocus>
            </div>

            <!-- Description Field -->
            <div>
                <label class="block text-sm sm:text-base font-semibold mb-1 text-gray-700">Description</label>
                <textarea x-model="form.description" rows="2"
                            class="w-full rounded-lg sm:rounded-xl border border-gray-300 focus:ring-2 focus:ring-pink-400 focus:outline-none px-4 py-2 resize-none text-sm sm:text-base"></textarea>
            </div>

            <!-- Mood + Upload Section -->
            <div class="flex flex-col sm:flex-row gap-4 items-stretch sm:items-center">
                <!-- Mood Select -->
                <div class="w-full sm:w-1/2">
                    <label class="block text-sm sm:text-base font-semibold mb-1 text-gray-700">Mood</label>
                    <select x-model="form.latest_mood"
                        class="w-full rounded-lg sm:rounded-xl border border-gray-300 focus:ring-2 focus:ring-pink-400 focus:outline-none px-4 py-2 text-sm sm:text-base">
                        <option value="">Select mood</option>
                        <option value="excited">🔥 excited</option>
                        <option value="happy">😊 happy</option>
                        <option value="chill">😎 chill</option>
                        <option value="thoughtful">🤔 thoughtful</option>
                        <option value="sad">😭 sad</option>
                        <option value="flirty">😏 flirty</option>
                        <option value="mindblown">🤯 mind-blown</option>
                        <option value="love">💖 love</option>
                    </select>
                </div>

                <!-- Upload Trigger -->
                <div class="w-full sm:w-1/2">
                    <label class="block text-sm sm:text-base font-semibold mb-1 text-gray-700">Upload</label>
                    <div @click="showFilePickerModal = true; loadFilePickerFiles()"
                        class="relative flex justify-center items-center border border-gray-300 rounded-lg sm:rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 h-[42px] sm:h-auto">
                        <span class="text-lg sm:text-xl text-gray-500 py-2">📂 Select File</span>
                    </div>
                </div>
            </div>

            <!-- Selected Files Preview -->
            <div class="grid grid-cols-4 sm:grid-cols-6 gap-2 mt-4">
                <template x-for="file in form.images" :key="file.id">
                    <div
                        class="relative aspect-square rounded-lg overflow-hidden border group cursor-pointer w-16 sm:w-20"
                        @click="focusedPreviewFile = file"
                    >
                        <!-- Video Preview -->
                        <template x-if="file.filename && file.filename.match(/\.(mp4|mov|avi|webm)$/i)">
                            <div class="w-full h-full relative">
                                <video :src="file.path" class="w-full h-full object-cover" muted playsinline></video>
                                <!-- Play icon overlay -->
                                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                    <svg class="h-8 w-8 text-white/80 drop-shadow" fill="currentColor" viewBox="0 0 20 20">
                                        <polygon points="6,4 16,10 6,16" />
                                    </svg>
                                </div>
                            </div>
                        </template>
                        <!-- Image Preview -->
                        <template x-if="!file.filename || file.filename.match(/\.(jpg|jpeg|png|gif|webp)$/i)">
                            <img :src="file.path" class="w-full h-full object-cover" />
                        </template>
                        <!-- Remove Button -->
                        <button
                            @click.stop="removeImage(file.id)"
                            class="absolute top-1 right-1 bg-white/80 hover:bg-white text-red-500 rounded-full p-1 text-xs shadow hidden group-hover:block"
                        >
                            ×
                        </button>
                    </div>
                </template>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit"
                        class="w-full px-6 py-3 bg-pink-500 text-white rounded-lg sm:rounded-xl font-semibold hover:bg-pink-600 transition-all text-sm sm:text-base">
                    ✨ Create MoodBoard
                </button>
            </div>
        </form>
    </template>

    <template x-if="activeTab === 'Teasers'">
        <form @submit.prevent="submitVideoForm" class="bg-white p-6 sm:p-8 rounded-xl shadow-lg space-y-6 border border-gray-100">
            <!-- Honeypot -->
            <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">

            <!-- Description -->
            <div>
                <label class="block text-sm font-semibold mb-1 text-gray-700">Description</label>
                <textarea x-model="videoForm.description" rows="2"
                    class="w-full rounded-xl border border-gray-300 focus:ring-2 focus:ring-pink-400 focus:outline-none px-4 py-2 resize-none text-sm"></textarea>
            </div>

            <!-- Hashtags -->
            <div>
                <label class="block text-sm font-semibold mb-1 text-gray-700">Hashtags</label>
                <input type="text" x-model="videoForm.hashtags"
                    placeholder="#funny, #dance"
                    class="w-full rounded-xl border border-gray-300 focus:ring-2 focus:ring-pink-400 focus:outline-none px-4 py-2 text-sm">
            </div>

            <!-- Expiring After -->
            <div>
                <label class="block text-sm font-semibold mb-1 text-gray-700">Expires After</label>
                <select x-model="videoForm.expires_after"
                    class="w-full rounded-xl border border-gray-300 focus:ring-2 focus:ring-pink-400 focus:outline-none px-4 py-2 text-sm">
                    <option value="">No expiry</option>
                    <option value="24">24 hours</option>
                    <option value="48">48 hours</option>
                    <option value="72">72 hours</option>
                    <option value="168">One week</option>
                </select>
            </div>

            <!-- Video Upload -->
            <div>
                <label class="block text-sm font-semibold mb-1 text-gray-700">Upload Video</label>
                <div @click="showFilePickerModal = true; loadFilePickerFiles()"
                    class="flex justify-center items-center border border-gray-300 rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 h-[42px]">
                    <span class="text-lg text-gray-500 py-2">🎬 Select Video</span>
                </div>
            </div>

            <!-- Selected Video Preview (Teasers) -->
            <template x-if="videoForm.video">
                <div class="mt-4 flex flex-wrap gap-2">
                    <div
                        class="relative aspect-square rounded-lg overflow-hidden border group cursor-pointer w-20 sm:w-24"
                        @click="focusedPreviewFile = filePickerFiles.find(f => f.id === videoForm.video)"
                    >
                        <template x-if="filePickerFiles.find(f => f.id === videoForm.video)">
                            <div class="w-full h-full relative">
                                <video
                                    :src="filePickerFiles.find(f => f.id === videoForm.video).path"
                                    class="w-full h-full object-cover"
                                    muted
                                    playsinline
                                ></video>
                                <!-- Play icon overlay -->
                                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                    <svg class="h-8 w-8 text-white/80 drop-shadow" fill="currentColor" viewBox="0 0 20 20">
                                        <polygon points="6,4 16,10 6,16" />
                                    </svg>
                                </div>
                            </div>
                        </template>
                        <!-- Remove Button -->
                        <button
                            @click.stop="videoForm.video = null"
                            class="absolute top-1 right-1 bg-white/80 hover:bg-white text-red-500 rounded-full p-1 text-xs shadow hidden group-hover:block"
                        >
                            ×
                        </button>
                    </div>
                </div>
            </template>

            <!-- Submit -->
            <div class="pt-2">
                <button type="submit"
                    class="w-full px-6 py-3 bg-pink-500 text-white rounded-xl font-semibold hover:bg-pink-600 transition-all text-sm">
                    🚀 Upload Teaser
                </button>
            </div>
        </form>
    </template>

        <!-- File Picker Modal -->
<div x-show="showFilePickerModal"
     x-cloak
     @click.self="showFilePickerModal = false"
     class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center"
     x-transition>
    <div class="bg-white w-full md:w-[1000px] max-w-[95vw] h-[600px] rounded-xl shadow-2xl overflow-hidden flex flex-col text-base sm:text-sm">
                <!-- 🧠 Header -->
        <div class="sticky top-0 z-10 p-3 sm:p-4 border-b bg-gradient-to-r from-pink-50 to-purple-50">
            <div class="flex items-center gap-2">
                <!-- Back Button - Mobile Only -->
                <button
                    x-show="!showFileListsPanel"
                    @click="showFileListsPanel = true"
                    class="flex items-center justify-center w-8 h-8 bg-white/90 hover:bg-white rounded-full shadow-sm border border-gray-200 mr-2"
                    title="Show file lists"
                >⬅️</button>
                <h2 class="font-bold text-pink-600 text-lg sm:text-xl flex-1 text-center sm:text-left">
                    📂 Select Your Files
                </h2>
                <button @click="showFilePickerModal = false; selectedFileIds = []" type="button"
                class="px-3 sm:px-2 py-0.5 sm:py-1 rounded bg-red-500 text-white hover:bg-red-600 font-medium text-sm sm:text-base"
                >
                    Close
                </button>
            </div>
            <div class="mt-2 sm:mt-3 flex flex-wrap gap-1 sm:gap-2 text-xs sm:text-sm text-gray-600">
                <select x-model="filters.fileType" @change="resetAndLoad()" class="rounded border px-2 py-1 bg-white text-xs sm:text-sm">
                    <option value="all">📁 All</option>
                    <option value="image">🖼️ Images</option>
                    <option value="video">🎬 Videos</option>
                </select>
                <select x-model="filters.contentType" @change="resetAndLoad()" class="rounded border px-2 py-1 bg-white text-xs sm:text-sm">
                    <option value="all">🔒 All</option>
                    <option value="safe">🙂 Safe</option>
                    <option value="adult">⚠️ Adult</option>
                </select>
                <select x-model="filters.sortOrder" @change="resetAndLoad()" class="rounded border px-2 py-1 bg-white text-xs sm:text-sm">
                    <option value="latest">⏱️ Latest</option>
                    <option value="earliest">🕰️ Earliest</option>
                </select>
            </div>
        </div>
        

        <div class="flex flex-1 overflow-hidden relative">
            <!-- 📚 File Lists Panel -->
            <div x-show="showFileListsPanel" class="w-full sm:w-[250px] border-r bg-gray-50 overflow-y-auto p-2 sm:p-4 absolute sm:static z-20 transition-transform duration-200">
                <template x-for="list in lists || []" :key="list.id">
                    <button type="button"
                        @click="setList(list.id); showFileListsPanel = false"
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

            <!-- 🖼️ File Grid -->
            <div x-show="!showFileListsPanel" class="w-full flex-1 h-full overflow-y-auto">
                <div class="px-2 py-4 sm:px-4 md:px-6 lg:px-8 grid gap-3 sm:gap-4"
                     style="grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));">
                    <template x-for="file in (filePickerFiles || [])" :key="file.id">
                        <div class="aspect-square bg-white border border-gray-300 rounded-xl overflow-hidden relative hover:scale-[1.05] transition shadow-sm group cursor-pointer"
                            @click="focusedPreviewFile = file">
                            <input type="checkbox"
                                class="absolute top-1 sm:top-2 left-1 sm:left-2 h-4 w-4 text-pink-500 rounded border-gray-300 z-10"
                                :checked="selectedFileIds.includes(file.id)"
                                @click.stop="toggleFileSelection(file.id)">
                            <template x-if="file.filename.match(/\.(mp4|mov|avi|webm)$/i)">
                                <video :src="file.path" muted playsinline preload="metadata" class="w-full h-full object-cover"></video>
                            </template>
                            <template x-if="file.filename.match(/\.(jpg|jpeg|png|gif|webp)$/i)">
                                <img :src="file.path" class="w-full h-full object-cover" alt="">
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- File Picker Modal Footer -->
        <div class="p-2 sm:p-4 border-t bg-white flex justify-end items-center">
            <div class="flex gap-2">
                <button
                    @click="selectedFileIds = []"
                    type="button"
                    :disabled="selectedFileIds.length === 0"
                    class="px-3 sm:px-4 py-1 sm:py-2 rounded bg-red-500 text-white hover:bg-red-600 font-medium text-sm sm:text-base transition"
                    :class="selectedFileIds.length === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                >
                    ✖️ Clear
                </button>
                <button
                    @click="addSelectedFilesToForm"
                    type="button"
                    :disabled="selectedFileIds.length === 0"
                    class="px-3 sm:px-4 py-1 sm:py-2 rounded bg-green-500 text-white hover:bg-green-600 font-medium text-sm sm:text-base transition"
                    :class="selectedFileIds.length === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                >
                    ➕ Add
                </button>
            </div>
        </div>
    </div>
</div>

        <!-- File Preview Modal -->
<template x-if="focusedPreviewFile">
    <div class="fixed inset-0 bg-black/80 z-50 flex items-center justify-center">
        <!-- Close Button -->
        <button @click="focusedPreviewFile = null"
            class="absolute top-4 sm:top-6 right-4 sm:right-6 text-white text-xl sm:text-2xl hover:text-pink-300 transition">
            ×
        </button>

        <!-- Video Preview with Custom Controls -->
        <template x-if="focusedPreviewFile.filename.match(/\.(mp4|mov|avi|webm)$/i)">
            <div class="relative group">
                <video
                    x-ref="previewVideo"
                    :src="focusedPreviewFile.path"
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
                    <!-- Left: Play/Pause, Mute/Unmute, Time Played -->
                    <div class="flex items-center gap-3">
                        <!-- Play/Pause -->
                        <button
                            @click="
                                if ($refs.previewVideo.paused) {
                                    $refs.previewVideo.play();
                                } else {
                                    $refs.previewVideo.pause();
                                }
                            "
                            class="px-1 hover:text-pink-400"
                        >
                            <template x-if="$refs.previewVideo && $refs.previewVideo.paused">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 20 20" stroke="currentColor"><polygon points="6,4 18,10 6,16" fill="currentColor"/></svg>
                            </template>
                            <template x-if="$refs.previewVideo && !$refs.previewVideo.paused">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 20 20" stroke="currentColor"><rect x="6" y="4" width="3" height="12" fill="currentColor"/><rect x="11" y="4" width="3" height="12" fill="currentColor"/></svg>
                            </template>
                        </button>
                        <!-- Mute/unmute -->
                        <button @click="$refs.previewVideo.muted = !$refs.previewVideo.muted" class="px-1 hover:text-pink-400">
                            <template x-if="!videoMuted">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 20 20" stroke="currentColor"><path d="M9 7H5a1 1 0 00-1 1v4a1 1 0 001 1h4l4 4V3l-4 4z"/></svg>
                            </template>
                            <template x-if="videoMuted">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 20 20" stroke="currentColor"><path d="M9 7H5a1 1 0 00-1 1v4a1 1 0 001 1h4l4 4V3l-4 4z"/><line x1="16" y1="4" x2="4" y2="16" stroke="currentColor" stroke-width="2"/></svg>
                            </template>
                        </button>
                        <!-- Time played -->
                        <span class="text-xs font-mono min-w-[48px]" x-text="formatTime(videoCurrentTime)"></span>
                    </div>
                    <!-- Progress Bar -->
                    <div class="flex-1 mx-3">
                        <div class="relative h-2 bg-gray-700 rounded-full cursor-pointer"
                             @click="$refs.previewVideo.currentTime = (videoDuration * ($event.offsetX / $event.target.offsetWidth))">
                            <div class="absolute top-0 left-0 h-2 bg-pink-500 rounded-full"
                                 :style="`width: ${(videoCurrentTime / videoDuration) * 100 || 0}%`"></div>
                        </div>
                    </div>
                    <!-- Right: Total time -->
                    <span class="text-xs font-mono min-w-[48px]" x-text="formatTime(videoDuration)"></span>
                </div>
            </div>
        </template>
        <!-- Image Preview -->
        <template x-if="focusedPreviewFile.filename.match(/\.(jpg|jpeg|png|gif|webp)$/i)">
            <img :src="focusedPreviewFile.path"
                class="max-w-[90vw] max-h-[80vh] rounded-lg shadow-xl object-contain">
        </template>
    </div>
</template>

    <!-- Toast Notification -->
    <div x-show="toast.show"
         x-transition
         class="fixed bottom-4 sm:bottom-6 right-4 sm:right-6 z-50 px-3 py-2 sm:px-4 sm:py-3 rounded-lg text-white text-xs sm:text-sm font-medium shadow-xl"
         :class="toast.error ? 'bg-red-500' : 'bg-green-500'">
        <span x-text="toast.message"></span>
    </div>

    <style>
[x-cloak] { display: none !important; }
        /* Chrome, Safari, Edge, Opera */
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
/* Firefox */
input[type=number] {
  -moz-appearance: textfield;
}
    </style>
</div>
@endsection

@push('scripts')

<script>
window.createBoardForm = function() {
  return {
    // Initial state
    form: { 
      title: '', 
      description: '', 
      latest_mood: '', 
      images: [] 
    },
    videoForm: {
  description: '',
  hashtags: '',
  expires_after: '', // in hours
  video: null // { id, path }
},
    toast: { 
      message: '', 
      show: false, 
      error: false 
    },
    showFilePickerModal: false,
    selectedFileIds: [],
    filePickerFiles: [], 
    filePickerOffset: 0,
    filePickerHasMore: true,
    focusedPreviewFile: null,
    lists: [],
    activeListId: 'all',
    isMobileListView: true,
    filters: {
      fileType: 'all',
      contentType: 'all',
      sortOrder: 'latest'
    },
    activeTab: 'moodboard',
    showFileListsPanel: true,
    videoCurrentTime: 0,
    videoDuration: 0,
    videoMuted: false,

    formatTime(seconds) {
        if (!seconds || isNaN(seconds)) return '0:00';
        const m = Math.floor(seconds / 60);
        const s = Math.floor(seconds % 60);
        return `${m}:${s.toString().padStart(2, '0')}`;
    },

    async init() {
        this.isMobileListView = window.innerWidth < 640;
      try {
        // Load lists first
        const res = await fetch('{{ route('file.lists') }}');
        const data = await res.json();

        this.lists = [
          { id: 'all', name: 'All Media', ...data.allMedia },
          ...data.lists
        ];
        
        // Then load initial files
        await this.loadFilePickerFiles();
        
      } catch (err) {
        console.error('Initialization error:', err);
        this.showToast('Could not initialize 😢', true);
      }
    },

    async submitVideoForm() {
    console.log('submitVideoForm called', { videoForm: this.videoForm });

    // Validation: Ensure a video is selected
    if (!this.videoForm.video) {
        console.log('⛔ No video selected');
        return this.showToast('Please select a video 🎥', true);
    }

    this.showToast('Uploading teaser...');
    console.log('Preparing FormData for teaser upload...');

    let expiresAfter = this.videoForm.expires_after;
    let expiresOn = null;

    if (expiresAfter) {
        const hours = parseInt(expiresAfter, 10);
        if (!isNaN(hours) && hours > 0) {
        const now = new Date();
        const future = new Date(now.getTime() + hours * 60 * 60 * 1000);
        expiresOn = future.toISOString();
        console.log(`Calculated expires_on: ${expiresOn} for expires_after: ${hours} hours`);
        } else {
        // Invalid value, treat as no expiry
        expiresAfter = null;
        expiresOn = null;
        console.log('Invalid expires_after value, setting both to null');
        }
    } else {
        // No expiry selected
        expiresAfter = null;
        expiresOn = null;
        console.log('No expiry selected, setting both expires_after and expires_on to null');
    }

    const formData = new FormData();
    formData.append('description', this.videoForm.description);
    formData.append('hashtags', this.videoForm.hashtags);
    formData.append('expires_after', expiresAfter !== null ? expiresAfter : '');
    formData.append('video_id', this.videoForm.video);

    // Log FormData entries
    for (let [key, value] of formData.entries()) {
        console.log('FormData:', key, value);
    }

    try {
        console.log('Sending POST request to /teasers...');
        const res = await fetch('/teasers', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            'Accept': 'application/json',
        },
        body: formData,
        });

        console.log('Received response:', res.status);
        const data = await res.json();
        console.log('Parsed JSON response:', data);

        if (!res.ok) {
        console.log('⛔ Server responded with error:', data);
        throw new Error(data.error || 'Upload failed');
        }

        this.showToast('Teaser uploaded 🎉');
        console.log('🎉 Success! Redirecting to:', data.redirect);
        setTimeout(() => {
        window.location.href = data.redirect;
        }, 3000);
    } catch (err) {
        console.error('❌ Error in submitVideoForm:', err);
        this.showToast(err.message || 'Something went wrong 😢', true);
    }
    },

        async submitForm() {
        
        console.log('submitForm called', { form: this.form });

        // Mood validation
        if (!this.form.latest_mood.trim()) {
            console.log('⛔ Validation failed: no mood selected');
            return this.showToast('Please select a mood 🧠', true);
        }

        // Title or images validation
        if (!this.form.title.trim() && !this.form.images.length) {
            console.log('⛔ Validation failed: missing title & no images');
            return this.showToast('Add a title or select media 🙏', true);
        }

        console.log('✔️ Validation passed, showing "Creating..." toast');
        this.showToast('Creating...');

        // Build FormData
        const formData = new FormData();
        formData.append('title', this.form.title);
        formData.append('description', this.form.description);
        formData.append('latest_mood', this.form.latest_mood);

        // Images: single number or array
        if (this.form.images.length === 1) {
            // send one numeric ID
            const id = this.form.images[0].id;
            console.log('Appending single image_id:', id);
            formData.append('image_ids', id);
        } else if (this.form.images.length > 1) {
            // send multiple via bracket notation
            const ids = this.form.images.map(img => img.id);
            console.log('Appending multiple image_ids[]:', ids);
            ids.forEach(id => {
            formData.append('image_ids[]', id);
            });
        } else {
            console.log('No images to append');
        }

        console.log('Final FormData entries:', Array.from(formData.entries()));

        // Send request
        try {
            const res = await fetch('/boards', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            },
            body: formData,
            });

            console.log('Fetch completed with status:', res.status);
            const data = await res.json();
            console.log('Parsed JSON response:', data);

            if (!res.ok) {
            console.log('⛔ Server responded with error:', data);
            throw new Error(data.error || 'Invalid server response');
            }

            console.log('🎉 Success! Redirecting to:', data.redirect);
            this.showToast('Board created 🎉');
            setTimeout(() => {
            window.location.href = data.redirect;
            }, 3000);

        } catch (err) {
            console.error('❌ Error in submitForm:', err);
            this.showToast(err.message || 'Something went wrong 😢', true);
        }
        },

    async loadFilePickerFiles() {
      if (!this.filePickerHasMore) return;

      try {
        const params = new URLSearchParams({
          offset: this.filePickerOffset,
          limit: 20,
          type: this.filters.fileType,
          content: this.filters.contentType,
          sort: this.filters.sortOrder
        });

        const baseUrl = this.activeListId === 'all'
          ? `/user-files?${params.toString()}`
          : `/file-lists/${this.activeListId}/items?${params.toString()}`;

        const res = await fetch(baseUrl);
        const data = await res.json();

        // Ensure unique files by ID to prevent duplicate keys
        const newFiles = Array.isArray(data.files) 
          ? data.files.filter(newFile => 
              !this.filePickerFiles.some(existingFile => existingFile.id === newFile.id)
            )
          : [];

        this.filePickerFiles = this.filePickerOffset === 0 
          ? newFiles 
          : [...this.filePickerFiles, ...newFiles];
        
        this.filePickerOffset += newFiles.length;
        this.filePickerHasMore = newFiles.length >= 20;
        
      } catch (err) {
        console.error('File fetch failed:', err);
        this.showToast('Could not load files', true);
      }
    },

    resetAndLoad() {
      this.filePickerFiles = [];
      this.filePickerOffset = 0;
      this.filePickerHasMore = true;
      this.loadFilePickerFiles();
    },

    async fetchFilesForList(listId) {
        const isAll = listId === 'all';
        const url = isAll
            ? `/user-files?offset=${this.filePickerOffset}&limit=20`
            : `/file-lists/${listId}/items?offset=${this.filePickerOffset}&limit=20`;

        const res = await fetch(url);
        const data = await res.json();
        this.filePickerFiles = data.files;
    },

      showToast(msg, isError = false) {
        this.toast = { message: msg, show: true, error: isError };
        setTimeout(() => this.toast.show = false, 3000);
      },

      refreshFiles() {
        this.filePickerOffset = 0;
        this.filePickerFiles = [];
        this.filePickerHasMore = true;
        this.loadFilePickerFiles();
      },

    watch: {
    fileTypeFilter() { this.resetAndLoad(); },
    contentTypeFilter() { this.resetAndLoad(); },
    sortOrder() { this.resetAndLoad(); }
    },

    methods: {
    resetAndLoad() {
        this.filePickerFiles = [];
        this.filePickerOffset = 0;
        this.filePickerHasMore = true;
        this.loadFilePickerFiles();
    }
    },

    removeImage(fileId) {
    // Remove from form preview
    this.form.images = this.form.images.filter(f => f.id !== fileId);

    // Also uncheck in modal
    this.selectedFileIds = this.selectedFileIds.filter(id => id !== fileId);
    },

    setList(id) {
        this.activeListId = id;
        this.filePickerFiles = [];
        this.filePickerOffset = 0;
        this.filePickerHasMore = true;
        this.showFileListsPanel = false; // Hide lists, show files
        this.loadFilePickerFiles();
    },

    showListView() {
        this.isMobileListView = true;
    },

        // 📸 File Preview Modal (from raw upload)
        openPreviewModal(event) {
            const file = event.target.files[0];
            if (!file) return;

            const type = file.type.startsWith('video') ? 'video' : 'image';
            const previewUrl = URL.createObjectURL(file);

            if (type === 'video') {
                const tempVideo = document.createElement('video');
                tempVideo.preload = 'metadata';
                tempVideo.src = previewUrl;

                tempVideo.onloadedmetadata = () => {
                    if (tempVideo.duration > 120) {
                        this.showToast('Video must be under 2 mins ⏱️', true);
                        URL.revokeObjectURL(previewUrl);
                    } else {
                        this.modal.open = true;
                        this.modal.previewUrl = previewUrl;
                        this.modal.fileType = type;
                        this.modal.rawFile = file;
                    }
                };

                tempVideo.onerror = () => {
                    this.showToast('Failed to load video metadata ❌', true);
                    URL.revokeObjectURL(previewUrl);
                };
            } else {
                this.modal.open = true;
                this.modal.previewUrl = previewUrl;
                this.modal.fileType = type;
                this.modal.rawFile = file;
            }
        },

        toggleFileSelection(id) {
            const file = this.filePickerFiles.find(f => f.id === id);
            if (!file) return;

            const isImage = file.filename.match(/\.(jpg|jpeg|png|gif|webp)$/i);
            const isVideo = file.filename.match(/\.(mp4|mov|avi|webm)$/i);

            // Toggle selection first
            let wasSelected = this.selectedFileIds.includes(id);
            if (wasSelected) {
                this.selectedFileIds = this.selectedFileIds.filter(fid => fid !== id);
            } else {
                this.selectedFileIds.push(id);
            }

            // --- Teasers tab ---
            if (this.activeTab === 'Teasers') {
                if (isImage) {
                    this.showToast("You can't select an image here.", true);
                    // Revert selection
                    this.selectedFileIds = this.selectedFileIds.filter(fid => fid !== id);
                    return;
                }
                if (isVideo) {
                    const selectedVideos = this.filePickerFiles.filter(f =>
                        this.selectedFileIds.includes(f.id) && f.filename.match(/\.(mp4|mov|avi|webm)$/i)
                    );
                    if (selectedVideos.length > 1) {
                        this.showToast("You can't select more than 1 video.", true);
                        this.selectedFileIds = this.selectedFileIds.filter(fid => fid !== id);
                        return;
                    }
                }
                return;
            }

            // --- Moodboard tab ---
            const selectedFiles = this.filePickerFiles.filter(f =>
                this.selectedFileIds.includes(f.id)
            );
            const selectedImages = selectedFiles.filter(f =>
                f.filename.match(/\.(jpg|jpeg|png|gif|webp)$/i)
            );
            const selectedVideos = selectedFiles.filter(f =>
                f.filename.match(/\.(mp4|mov|avi|webm)$/i)
            );

            if (isImage && selectedImages.length > 20) {
                this.showToast('You have reached maximum allowed selections.', true);
                this.selectedFileIds = this.selectedFileIds.filter(fid => fid !== id);
                return;
            }
            if (isVideo && selectedImages.length > 0) {
                this.showToast("You can't mix videos and pictures.", true);
                this.selectedFileIds = this.selectedFileIds.filter(fid => fid !== id);
                return;
            }
            if (isVideo && selectedVideos.length > 1) {
                this.showToast("You can't have more than 1 video.", true);
                this.selectedFileIds = this.selectedFileIds.filter(fid => fid !== id);
                return;
            }
            if (isImage && selectedVideos.length > 0) {
                this.showToast("You can't mix images and videos.", true);
                this.selectedFileIds = this.selectedFileIds.filter(fid => fid !== id);
                return;
            }
        },

        addSelectedFilesToForm() {
            const selectedFiles = this.filePickerFiles.filter(f =>
                this.selectedFileIds.includes(f.id)
            );

            // Only allow one video or up to 20 images
            const images = selectedFiles.filter(f => f.filename.match(/\.(jpg|jpeg|png|gif|webp)$/i));
            const videos = selectedFiles.filter(f => f.filename.match(/\.(mp4|mov|avi|webm)$/i));

            if (videos.length > 1) {
                this.showToast('Only one video allowed 🎬', true);
                return;
            }
            if (images.length > 20) {
                this.showToast('You can select up to 20 images 🖼️', true);
                return;
            }
            if (videos.length === 1 && images.length > 0) {
                this.showToast('Cannot mix images and video', true);
                return;
            }

            // 👇 Add this block
            if (this.activeTab === 'Teasers') {
            // Only store the video ID, not the whole object
                this.videoForm.video = videos.length === 1 ? videos[0].id : null;
            } else {
                this.form.images = selectedFiles;
            }

            this.focusedPreviewFile = null;
            this.showFilePickerModal = false;
            this.showToast(`${selectedFiles.length} file(s) added 🎉`);
        },

        selectFile() {
            this.form.image = this.modal.rawFile;
            URL.revokeObjectURL(this.modal.previewUrl);
            this.modal.open = false;
            this.modal.previewUrl = null;
            this.modal.fileType = null;
            this.modal.rawFile = null;
        },

        cancelFile() {
            URL.revokeObjectURL(this.modal.previewUrl);
            this.modal.open = false;
            this.modal.previewUrl = null;
            this.modal.fileType = null;
            this.modal.rawFile = null;

            // 🎉 Show toast
            this.toast.message = 'File selection canceled';
            this.toast.error = false;
            this.toast.show = true;
            setTimeout(() => this.toast.show = false, 4000); // Optional auto-hide
        },
    };
  }
</script>
@endpush
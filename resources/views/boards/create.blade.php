@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-6 sm:py-10 pb-20 sm:pb-32" x-data="createBoardForm()" x-init="init()">
    <!-- Header -->
    <h1 class="text-3xl sm:text-4xl font-bold mb-6 sm:mb-8 text-pink-600 flex items-center gap-3">
        🖼️ New MoodBoard
    </h1>

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
                <select x-model="form.mood"
                        class="w-full rounded-lg sm:rounded-xl border border-gray-300 focus:ring-2 focus:ring-pink-400 focus:outline-none px-4 py-2 text-sm sm:text-base">
                    <option value="">Select mood</option>
                    <option value="relaxed">😌 Relaxed</option>
                    <option value="craving">🤤 Craving</option>
                    <option value="hyped">🔥 Hyped</option>
                    <option value="obsessed">🫠 Obsessed</option>
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
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-4">
            <template x-for="file in form.images" :key="file.id">
                <div class="relative w-full aspect-square rounded-lg overflow-hidden border group cursor-pointer"
                    @click="focusedPreviewFile = file">
                    <!-- Thumbnail -->
                    <img :src="file.path" class="w-full h-full object-cover" />

                    <!-- Remove Button -->
                    <button @click.stop="removeImage(file.id)"
                            class="absolute top-1 right-1 bg-white/80 hover:bg-white text-red-500 rounded-full p-1 text-xs shadow hidden group-hover:block">
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

    <!-- File Picker Modal (keep your existing amazing modal here) -->
            <div x-show="showFilePickerModal"
            @click.self="showFilePickerModal = false"
            class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center"
            x-transition>
            <div class="bg-white w-[1000px] max-w-[95vw] h-[600px] rounded-xl shadow-2xl overflow-hidden flex flex-col text-base sm:text-sm"> <!-- Reduced base font size -->
                <!-- 🧠 Header -->
                <div class="sticky top-0 z-10 p-3 sm:p-4 border-b bg-gradient-to-r from-pink-50 to-purple-50"> <!-- Reduced padding on mobile -->
                    <div class="flex items-center gap-2">
                        <!-- Back Button - Mobile Only -->
                        <button x-show="!isMobileListView && window.innerWidth < 640" type="button"
                                @click="isMobileListView = true"
                                class="sm:hidden flex items-center justify-center w-8 h-8 bg-white/90 hover:bg-white rounded-full shadow-sm border border-gray-200">
                            ←
                        </button>
                        
                        <h2 class="font-bold text-pink-600 text-lg sm:text-xl flex-1 text-center sm:text-left"> <!-- Adjusted text size -->
                            📂 Select Your Files
                        </h2>
                        
                        <button @click="showFilePickerModal = false" type="button" 
                                class="text-xl text-gray-600 hover:text-pink-500 w-8 h-8 flex items-center justify-center">
                            ×
                        </button>
                    </div>
                    
                    <div class="mt-2 sm:mt-3 flex flex-wrap gap-1 sm:gap-2 text-xs sm:text-sm text-gray-600"> <!-- Reduced gap and text size -->
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
                    <!-- 📚 Sidebar Lists -->
                    <div class="w-full sm:w-[250px] border-r bg-gray-50 overflow-y-auto p-2 sm:p-4 absolute sm:static transition-transform duration-200"
                        :class="isMobileListView ? 'left-0 w-full z-20' : '-left-full sm:left-0'">
                        <!-- List Buttons -->
                        <template x-for="list in lists || []" :key="list.id">
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

                    <!-- 🖼️ File Grid -->
                    <div class="w-full flex-1 overflow-y-auto p-2 sm:p-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 sm:gap-4"
                        :class="isMobileListView ? 'hidden sm:grid' : 'grid'">
                        <template x-for="file in (filePickerFiles || [])" :key="file.id">
                            <div class="relative h-28 sm:h-32 p-1 bg-white border border-gray-200 rounded-lg overflow-hidden cursor-pointer hover:ring-2 hover:ring-pink-500 transition">
                                <input type="checkbox"
                                    class="absolute top-1 sm:top-2 left-1 sm:left-2 h-4 w-4 text-pink-500 rounded border-gray-300 z-10"
                                    :disabled="!canSelectFile(file)"
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

                <!-- ✅ Footer Actions -->
                <div class="p-2 sm:p-4 border-t bg-white flex justify-end">
                    <button @click="addSelectedFilesToForm" type="button" class="px-3 sm:px-4 py-1 sm:py-2 rounded bg-pink-500 text-white hover:bg-pink-600 font-medium text-sm sm:text-base">
                        ➕ Add Selected
                    </button>
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

            <!-- Select Checkbox -->
            <div class="absolute top-4 sm:top-6 left-4 sm:left-6">
                <label class="flex items-center gap-2 text-white font-medium text-sm sm:text-base">
                    <input type="checkbox"
                        :checked="selectedFileIds.includes(focusedPreviewFile.id)"
                        @click.stop="toggleFileSelection(focusedPreviewFile.id)"
                        class="form-checkbox h-4 w-4 sm:h-5 sm:w-5 text-pink-500 rounded border-gray-300">
                    Select
                </label>
            </div>

            <!-- Media Preview -->
            <template x-if="focusedPreviewFile.filename.match(/\.(mp4|mov|avi|webm)$/i)">
                <video :src="focusedPreviewFile.path"
                    autoplay
                    controls
                    class="max-w-[90vw] max-h-[80vh] rounded-lg shadow-xl"></video>
            </template>
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
</div>
@endsection

@push('scripts')

<script>
function createBoardForm() {
  return {
    // Initial state
    form: { 
      title: '', 
      description: '', 
      mood: '', 
      images: [] 
    },
    toast: { 
      message: '', 
      show: false, 
      error: false 
    },
    showFilePickerModal: false,
    selectedFileIds: [],
    filePickerFiles: [], // Properly initialized
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
        
        // Hide lists in mobile view
        if (window.innerWidth < 640) {
            this.isMobileListView = false;
        }
        
        this.loadFilePickerFiles();
    },

    showListView() {
        this.isMobileListView = true;
    },

async submitForm() {
    if (!this.form.mood.trim()) return this.showToast('Please select a mood 🧠', true);
    if (!this.form.title.trim() && !this.form.images.length) return this.showToast('Add a title or select media 🙏', true);

    this.showToast('Creating...');

    const formData = new FormData();
    formData.append('title', this.form.title);
    formData.append('description', this.form.description);
    formData.append('latest_mood', this.form.mood);
    
    // Send array of image IDs
    if (this.form.images.length) {
        const imageIds = this.form.images.map(img => img.id);
        formData.append('image_ids', JSON.stringify(imageIds));
    }

    try {
        const res = await fetch('/boards', {
            method: 'POST',
            headers: { 
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            },
            body: formData
        });

        const data = await res.json();
        
        if (!res.ok) {
            throw new Error(data.error || 'Invalid server response');
        }

        this.showToast('Board created 🎉');
        setTimeout(() => window.location.href = data.redirect, 3000);
    } catch (err) {
        console.error(err);
        this.showToast(err.message || 'Something went wrong 😢', true);
    }
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
            const alreadySelected = this.selectedFileIds.includes(id);

            if (alreadySelected) {
                this.selectedFileIds = [];
            } else {
                this.selectedFileIds = [id]; // allow only one selection
            }
        },

        canAddSelection() {
            const selected = this.filePickerFiles.filter(f =>
                this.selectedFileIds.includes(f.id)
            );

            const selectedImages = selected.filter(f =>
                f.filename.match(/\.(jpg|jpeg|png|gif|webp)$/i)
            );
            const selectedVideos = selected.filter(f =>
                f.filename.match(/\.(mp4|mov|avi|webm)$/i)
            );

            const validImageCount = selectedImages.length > 0 && selectedImages.length <= 5;
            const validVideoCount = selectedVideos.length === 1;

            return (
                (validImageCount && selectedVideos.length === 0) ||
                (validVideoCount && selectedImages.length === 0)
            );
        },

        addSelectedFilesToForm() {
            const selectedFiles = this.filePickerFiles.filter(f =>
                this.selectedFileIds.includes(f.id)
            );

            this.form.images = selectedFiles.slice(0, 1);
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

        canSelectFile(file) {
            const isImage = file.filename.match(/\.(jpg|jpeg|png|gif|webp)$/i);
            const isVideo = file.filename.match(/\.(mp4|mov|avi|webm)$/i);

            const selected = this.filePickerFiles.find(f =>
                this.selectedFileIds.includes(f.id)
            );

            if (!selected) return true;

            const selectedIsImage = selected.filename.match(/\.(jpg|jpeg|png|gif|webp)$/i);
            const selectedIsVideo = selected.filename.match(/\.(mp4|mov|avi|webm)$/i);

            // ❌ Only allow selection if none is selected OR types match
            if (isImage && selectedIsImage) return false; // already selected an image
            if (isVideo && selectedIsVideo) return false; // already selected a video

            return false;
        },
    };
  }
</script>
@endpush
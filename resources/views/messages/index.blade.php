@extends('layouts.app')

@section('content')
<div x-data="messageInbox()" x-init="init()" class="flex flex-col lg:flex-row h-[100dvh] overflow-hidden">
    <!-- 📱 Mobile Header -->
    <div class="lg:hidden sticky top-0 z-50 bg-white shadow">
        <div class="flex items-center justify-between px-3 py-2 bg-gradient-to-r from-pink-500 to-purple-600 text-white">
            <div class="flex-1">
                <input type="text" x-model="searchQuery" placeholder="Search messages..."
                    class="w-full px-4 py-2 rounded-full border border-white/30 bg-white/20 placeholder-white text-white text-sm focus:outline-none focus:ring-2 focus:ring-white">
            </div>
            <button 
                @click="
                    if (window.innerWidth < 1024 && $store.messaging.receiver) {
                        showRecentChats = true;
                        $store.messaging.receiver = null;
                        $store.messaging.messages = [];
                        history.pushState(null, '', '/messages');
                    }
                "
                class="ml-2 px-4 py-2 bg-white text-pink-600 rounded-full text-sm font-semibold shadow hover:bg-pink-100 transition"
            >
                💬 Recent Chats
            </button>
        </div>

        <div class="flex justify-around px-2 py-2 border-b border-gray-200 text-sm text-gray-700 bg-white">
            <a href="{{ route('home') }}" class="flex flex-col items-center hover:text-pink-600"> 🏠 <span class="text-xs mt-1">Home</span> </a>
            <a href="/messages" class="flex flex-col items-center hover:text-pink-600"> 💌 <span class="text-xs mt-1">Messages</span> </a>
            <a href="/my-vibes" class="flex flex-col items-center hover:text-pink-600"> 💫 <span class="text-xs mt-1">My Vibes</span> </a>
            <a href="/notifications" class="flex flex-col items-center hover:text-pink-600"> 🔔 <span class="text-xs mt-1">Alerts</span> </a>
            <a href="/settings" class="flex flex-col items-center hover:text-pink-600"> ⚙️ <span class="text-xs mt-1">Settings</span> </a>
        </div>
    </div>

    <!-- Left Sidebar (Recent Chats) -->
    <div class="w-full lg:w-1/3 bg-white border-r flex flex-col">
        <!-- 🔍 Desktop Sticky Search Bar -->
        <div class="hidden lg:block sticky top-0 z-20 bg-gradient-to-r from-pink-500 to-purple-600 px-4 pt-4 pb-2 border-b border-gray-200">
            <input type="text" x-model="searchQuery" placeholder="Search messages..."
                class="w-full px-4 py-2 border border-white/30 rounded-full focus:outline-none focus:ring-2 focus:ring-white text-sm shadow-sm bg-white placeholder-pink-500 text-pink-600">
        </div>

        <!-- 💬 Recent Chats List -->
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
                x-for="contact in [...$store.messaging.filteredContacts].sort((a, b) => new Date(b.last_message?.created_at || 0) - new Date(a.last_message?.created_at || 0))"
                :key="contact.id"
            >
                <a href="#"
                    @click.prevent.stop="selectUser(contact)"
                    class="block bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all group px-4 py-3 mb-2"
                >
                    <div class="flex justify-between items-center">
                        <div class="min-w-0">
                            <div class="text-base font-semibold text-gray-800 truncate group-hover:text-pink-600 transition-colors"
                                x-text="'@' + contact.username"></div>
                            <div class="text-xs text-gray-500 truncate flex items-center space-x-1 mt-1 group-hover:text-pink-500 transition-colors">
                                <template x-if="contact.last_message?.has_attachment">
                                    <span class="text-pink-500">🖼️</span>
                                </template>
                                <span x-text="contact.last_message?.body || (contact.last_message?.has_attachment ? 'Attachment' : 'No messages yet')"></span>
                            </div>
                        </div>
                        <div class="text-[0.7rem] text-gray-400 whitespace-nowrap"
                            x-text="contact.last_message?.created_at ? new Date(contact.last_message.created_at).toLocaleTimeString() : ''">
                        </div>
                    </div>
                </a>
            </template>

            <!-- 🚫 Fallback if no contacts -->
            <template x-if="$store.messaging.filteredContacts.length === 0">
                <div class="text-center text-gray-400 text-sm py-4">No recent chats found.</div>
            </template>
        </div>
    </div>

    <!-- Main Chat Area -->
    <div
        class="flex-1 flex flex-col bg-gray-50 overflow-y-auto"
        x-transition
    >
        <template x-if="$store.messaging.receiver">
            <div class="flex flex-col flex-1 overflow-hidden">
                <!-- Sticky Chat Header -->
                <div class="sticky top-0 z-10 px-3 py-2 sm:px-4 sm:py-3 border-b bg-gradient-to-r from-pink-50 to-purple-50 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-pink-200 flex items-center justify-center text-pink-600 text-sm sm:text-base">
                            <span x-text="$store.messaging.receiver.username.charAt(0).toUpperCase()"></span>
                        </div>

                        <!-- ✅ Clickable Username -->
                        <a :href="`/space/${$store.messaging.receiver.username}`"
                        class="font-semibold text-pink-600 text-base sm:text-lg hover:underline truncate max-w-[60vw] sm:max-w-none"
                        x-text="'@' + $store.messaging.receiver.username">
                        </a>
                    </div>

                    <button class="text-gray-400 hover:text-pink-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                        </svg>
                    </button>
                </div>

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

                                    <!-- 📌 Attachments Preview -->
                                    <template x-if="message.attachments?.length">
                                        <div class="relative mt-2 group cursor-pointer w-full max-w-full" x-data="{ index: 0 }" @click="$store.previewModal.open(message.attachments, index)">
                                            <template x-if="message.attachments[index]">
                                                <div class="relative w-full max-w-full overflow-hidden rounded-lg">
                                                    <div class="aspect-video w-full overflow-hidden border border-gray-200 bg-white shadow-sm">

                                                        <!-- 🖼️ Image Preview -->
                                                        <template x-if="['jpg','jpeg','png','gif','webp'].includes(message.attachments[index]?.extension?.toLowerCase())">
                                                            <img :src="message.attachments[index].url" class="object-cover h-full w-full" />
                                                        </template>

                                                        <!-- 🎮 Video Thumbnail -->
                                                        <template x-if="['mp4','mov','webm'].includes(message.attachments[index]?.extension?.toLowerCase())">
                                                            <div class="relative">
                                                                <img :src="message.attachments[index].thumbnail || '/video-placeholder.png'" class="object-cover h-full w-full opacity-80" />
                                                                <div class="absolute inset-0 flex items-center justify-center">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                                                                        <path d="M8 5v14l11-7z" />
                                                                    </svg>
                                                                </div>
                                                            </div>
                                                        </template>

                                                        <!-- 📄 Fallback -->
                                                        <template x-if="!['jpg','jpeg','png','gif','webp','mp4','mov','webm'].includes(message.attachments[index]?.extension?.toLowerCase())">
                                                            <div class="p-4 text-xs italic text-gray-500 text-center">
                                                                <span x-text="message.attachments[index]?.name"></span><br>
                                                                <a :href="message.attachments[index]?.url" target="_blank" class="text-blue-500 underline">Download</a>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>

                                            <!-- ◀️▶️ Navigation Arrows -->
                                            <button @click.stop="index = index > 0 ? index - 1 : index"
                                                    :disabled="index === 0"
                                                    :class="index === 0 ? 'opacity-30 cursor-not-allowed' : ''"
                                                    class="absolute left-0 top-1/2 transform -translate-y-1/2 bg-white/80 hover:bg-white text-gray-600 p-1 rounded-full shadow">
                                                ‹
                                            </button>

                                            <button @click.stop="index = index < message.attachments.length - 1 ? index + 1 : index"
                                                    :disabled="index === message.attachments.length - 1"
                                                    :class="index === message.attachments.length - 1 ? 'opacity-30 cursor-not-allowed' : ''"
                                                    class="absolute right-0 top-1/2 transform -translate-y-1/2 bg-white/80 hover:bg-white text-gray-600 p-1 rounded-full shadow">
                                                ›
                                            </button>

                                            <!-- 📌 File Info -->
                                            <template x-if="message.attachments[index]">
                                                <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 bg-white/80 text-xs px-2 py-1 rounded-t shadow flex items-center gap-1">
                                                    <template x-if="['jpg','jpeg','png','gif','webp'].includes(message.attachments[index]?.extension?.toLowerCase())">
                                                        <span class="text-pink-500">🖼️</span>
                                                    </template>
                                                    <template x-if="['mp4','mov','webm'].includes(message.attachments[index]?.extension?.toLowerCase())">
                                                        <span class="text-purple-500">🎮</span>
                                                    </template>
                                                    <span x-text="`${index + 1} / ${message.attachments.length}`"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- 🔢 Attachment Type Counters -->
                                    <template x-if="message.attachments?.length">
                                        <div class="mt-1 text-[0.65rem] text-gray-500 flex gap-3 items-center">
                                            <div class="flex items-center gap-1">
                                                📌 <span x-text="message.attachments.length"></span>
                                            </div>
                                            <template x-if="message.attachments.filter(f => ['jpg','jpeg','png','gif','webp'].includes(f?.extension?.toLowerCase())).length">
                                                <div class="flex items-center gap-1 text-pink-500">
                                                    🖼️ <span x-text="message.attachments.filter(f => ['jpg','jpeg','png','gif','webp'].includes(f?.extension?.toLowerCase())).length"></span>
                                                </div>
                                            </template>
                                            <template x-if="message.attachments.filter(f => ['mp4','mov','webm'].includes(f?.extension?.toLowerCase())).length">
                                                <div class="flex items-center gap-1 text-purple-500">
                                                    🎮 <span x-text="message.attachments.filter(f => ['mp4','mov','webm'].includes(f?.extension?.toLowerCase())).length"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- 💬 Message Body -->
                                    <div x-text="message.body" class="break-words mt-2"></div>

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

                    <!-- Message Input -->
                    <div class="sticky bottom-0 z-10 p-3 border-t bg-white">
                        <!-- File Previews -->
                        <div x-show="$store.messaging.selectedFiles.length > 0" class="flex flex-wrap gap-2 mb-2">
                            <template x-for="(file, index) in $store.messaging.selectedFiles" :key="index">
                                <div class="relative group">
                                    <template x-if="file.content_type.includes('image')">
                                        <img :src="file.path" class="h-20 w-20 object-cover rounded-lg border border-gray-200">
                                    </template>
                                    <template x-if="file.content_type.includes('video')">
                                        <div class="h-20 w-20 relative">
                                            <video :src="file.path" class="h-full w-full object-cover rounded-lg border border-gray-200" muted playsinline></video>
                                            <div class="absolute inset-0 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white/80" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </div>
                                    </template>
                                    <button @click="$store.messaging.removeFile(index)" class="absolute top-1 right-1 bg-white/80 hover:bg-white text-red-500 rounded-full p-1 text-xs shadow">
                                        ×
                                    </button>
                                </div>
                            </template>
                        </div>

                        <form @submit.prevent="handleSend" class="flex items-center gap-2">
                            <button type="button" 
                                @click="$store.filePicker.showModal = true"
                                :disabled="$store.messaging.selectedFiles.length >= 20"
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
                            ></textarea>

                            <button type="submit"
                                class="bg-pink-500 text-white px-4 py-2 rounded-full text-sm hover:bg-pink-600 transition flex items-center gap-1"
                                :disabled="(!newMessage.trim() && $store.messaging.selectedFiles.length === 0) || $store.messaging.isLoading">
                                <span x-show="!$store.messaging.isLoading">Send</span>
                                <span x-show="$store.messaging.isLoading">Sending...</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </button>
                        </form>
                    </div>
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
                <a href="/messages" class="block px-4 py-2 rounded-lg font-medium text-sm text-gray-700 hover:bg-pink-100 transition">
                    💌 Messages
                </a>
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

    <!-- File Picker Modal -->
     
    <div x-data="filePicker" x-init="init()">
        <div x-show="$store.filePicker.showModal" x-transition class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center">
            <div class="bg-white w-[1000px] max-w-[95vw] h-[600px] rounded-xl shadow-2xl overflow-hidden flex flex-col">
                <!-- Modal Header -->
                <div class="sticky top-0 z-10 p-4 border-b bg-gradient-to-r from-pink-50 to-purple-50">
                    <div class="flex items-center gap-2">
                        <h2 class="font-bold text-pink-600 text-lg sm:text-xl flex-1">📂 Select Your Files</h2>
                        <button @click="$store.filePicker.showModal = false" class="text-xl text-gray-600 hover:text-pink-500 w-8 h-8 flex items-center justify-center">
                            ×
                        </button>
                    </div>
                    
                    <div class="mt-3 flex flex-wrap gap-2 text-sm text-gray-600">
                        <select x-model="filters.type" @change="loadFiles()" class="rounded border px-2 py-1 bg-white">
                            <option value="all">📁 All</option>
                            <option value="image">🖼️ Images</option>
                            <option value="video">🎬 Videos</option>
                        </select>

                        <select x-model="filters.contentType" @change="loadFiles()" class="rounded border px-2 py-1 bg-white">
                            <option value="all">🔒 All</option>
                            <option value="safe">🙂 Safe</option>
                            <option value="adult">⚠️ Adult</option>
                        </select>

                        <select x-model="filters.sort" @change="loadFiles()" class="rounded border px-2 py-1 bg-white">
                            <option value="latest">⏱️ Latest</option>
                            <option value="earliest">🕰️ Earliest</option>
                        </select>
                    </div>
                </div>

                <!-- Modal Content -->
                <div class="flex flex-1 overflow-hidden">
                   <div class="w-[250px] border-r bg-gray-50 overflow-y-auto p-4">
                        <template x-if="files.length === 0">
                            <div class="text-center text-gray-400 py-8 col-span-full">
                                No files found in this list.
                            </div>
                        </template>

                        <template x-for="list in lists" :key="list.id">
                            <button @click="activeListId = list.id; loadFiles()"
                                class="flex items-center justify-between w-full px-3 py-2 mb-2 text-left rounded-lg hover:bg-pink-50"
                                :class="list.id === activeListId ? 'bg-pink-100 text-pink-700 font-bold' : 'text-gray-700'">
                                <span x-text="list.name" class="truncate"></span>
                                <span class="text-xs text-gray-500 flex gap-2 ml-2">
                                    <span x-text="list.imageCount || 0">🖼️</span>
                                    <span x-text="list.videoCount || 0">🎬</span>
                                </span>
                            </button>
                        </template>
                    </div>

                    <!-- File Grid -->
                    <div class="flex-1 overflow-y-auto p-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        <template x-for="file in files" :key="file.id + '-' + file.filename">
                            <div class="relative h-32 p-1 bg-white border border-gray-200 rounded-lg overflow-hidden cursor-pointer hover:ring-2 hover:ring-pink-500 transition">
                                <input type="checkbox"
                                    class="absolute top-2 left-2 h-4 w-4 text-pink-500 rounded border-gray-300 z-10"
                                    :checked="selectedIds.includes(file.id)"
                                    @click.stop="toggleFileSelection(file.id)">
                                <template x-if="file.content_type.includes('video')">
                                    <video :src="file.path" muted playsinline class="w-full h-full object-cover"></video>
                                </template>
                                <template x-if="file.content_type.includes('image')">
                                    <img :src="file.path" class="w-full h-full object-cover" alt="">
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 border-t bg-white flex justify-end">
                    <button
                        @click="addSelectedFiles"
                        :disabled="selectedIds.length === 0"
                        class="px-4 py-2 rounded bg-pink-500 text-white disabled:opacity-50 disabled:cursor-not-allowed hover:bg-pink-600 transition"
                    >
                        ➕ Add Selected
                    </button>
                </div>
            </div>
        </div>
    </div>

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
</div>
@endsection

@push('scripts')
<script>
    window.__inbox_contacts = @json($contacts);
    window.__inbox_receiver = @json($receiver);
    window.__inbox_messages = @json($messages);
</script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('filePicker', {
        showModal: false,
        });
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

    init() {
        // Load recent chats list
        this.loadContacts();

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
            const res = await fetch(`/api/messages/thread/${receiverId}?limit=20`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            const contentType = res.headers.get('Content-Type');
            if (!res.ok || !contentType.includes('application/json')) {
                throw new Error('Non-JSON response');
            }

            const data = await res.json();
            this.receiver = data.receiver;
            this.messages = data.messages;
            this.offset = this.messages.length;

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
        if (!this.receiver || (!message.trim() && files.length === 0)) return;

        this.isLoading = true;
        this.error = null;

        try {
            const formData = new FormData();
            formData.append('body', message);
            formData.append('receiver_id', this.receiver.id);
            files.forEach((file, index) => {
                if (file instanceof File) {
                    formData.append(`files[${index}]`, file);
                } else {
                    formData.append(`file_ids[${index}]`, file.id);
                }
            });

            const res = await fetch('/messages', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await res.json();
            this.messages.push(data.message);

            await Alpine.nextTick();
            const el = document.getElementById('chat-scroll');
            if (el) el.scrollTop = el.scrollHeight;

            this.selectedFiles = [];
        } catch (e) {
            console.error('[Send Message] Error:', e);
            this.error = e.message;
        } finally {
            this.isLoading = false;
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
    selectedFiles: [],
    showRecentChats: window.innerWidth < 1024,
    isDesktop: window.innerWidth >= 1024,

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
        history.pushState(null, null, `/messages/${user.id}`);
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
        get showModal() {
            return Alpine.store('filePicker').showModal;
        },
        set showModal(value) {
            Alpine.store('filePicker').showModal = value;
        },
        
        async loadFiles() {
            try {
                let response;

                if (this.activeListId === 'all') {
                    const params = new URLSearchParams({
                        list_id: this.activeListId,
                        type: this.filters.type,
                        content: this.filters.contentType,
                        sort: this.filters.sort
                    });

                    console.log('[🔄 FILES] Fetching all files with params:', Object.fromEntries(params.entries()));
                    response = await fetch(`/user-files?${params}`);
                } else {
                    console.log(`[📂 FILES] Fetching list items for list: ${this.activeListId}`);
                    response = await fetch(`/file-lists/${this.activeListId}/items`);
                }

                const data = await response.json();
                console.log('[FILES RAW RESPONSE]', data);

                // Handle flexible response formats
                this.files = Array.isArray(data)
                    ? data
                    : Array.isArray(data.files)
                        ? data.files
                        : [];

                console.log(`[✅ FILES] ${this.files.length} files loaded`);
            } catch (error) {
                console.error('[❌ FILES] Failed to load files:', error);
            }
        },
        
        async loadLists() {
            try {
                console.log('[🔄 LISTS] Fetching file lists...');

                const response = await fetch('/files/lists-with-counts');
                const contentType = response.headers.get('Content-Type');

                if (!response.ok || !contentType.includes('application/json')) {
                    throw new Error(`Invalid response format. Status: ${response.status}, Content-Type: ${contentType}`);
                }

                const dbLists = await response.json();

                // Inject "All Media" list manually
                const allMediaList = {
                    id: 'all',
                    name: '🎞️ All Media',
                    imageCount: dbLists.allMedia.imageCount,
                    videoCount: dbLists.allMedia.videoCount,
                    safeCount: dbLists.allMedia.safeCount,
                    adultCount: dbLists.allMedia.adultCount,
                    totalCount: dbLists.allMedia.totalCount
                };

                this.lists = [allMediaList, ...dbLists.lists];

                // ✨ Console Summary
                console.log(`[✅ LISTS] ${this.lists.length} total lists (including All Media)`);
                if (this.lists.length > 0) {
                    console.table(this.lists.map(list => ({
                        ID: list.id,
                        Name: list.name,
                        '🖼️ Images': list.imageCount ?? 'N/A',
                        '🎬 Videos': list.videoCount ?? 'N/A'
                    })));
                } else {
                    console.warn('[⚠️ LISTS] No lists found.');
                }

            } catch (error) {
                console.error('[❌ LISTS] Failed to load lists:', error);
            }
        },
        
        toggleFileSelection(id) {
            if (this.selectedIds.includes(id)) {
                this.selectedIds = this.selectedIds.filter(fileId => fileId !== id);
            } else {
                this.selectedIds = [...this.selectedIds, id];
            }
        },
        
        addSelectedFiles() {
            console.log('[🧠 addSelectedFiles] Running...');
            const selectedFiles = this.files.filter(file => 
                this.selectedIds.includes(file.id)
            );
            
            this.$store.messaging.addSelectedFiles(selectedFiles);
            Alpine.store('filePicker').showModal = false;
            this.selectedIds = [];

            console.log('[📦 Files Added] Closing modal:', this.showModal);
        },
        
        init() {
            this.loadLists();
            this.loadFiles();
        }
    }));

    Alpine.store('previewModal', {
        show: false,
        files: [],
        index: 0,

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

<!-- ✨ Basic Moodboard
<div class="max-w-6xl mx-auto px-6 py-10 space-y-10">
  <div class="bg-white rounded-2xl shadow-xl p-6 border border-pink-200">
    <div class="flex justify-between items-center">
      <h2 class="text-3xl font-bold text-pink-600 flex items-center gap-2">
        <svg class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.946a1 1 0 00.95.69h4.15c.969 0 1.371 1.24.588 1.81l-3.36 2.444a1 1 0 00-.364 1.118l1.286 3.946c.3.921-.755 1.688-1.54 1.118l-3.36-2.444a1 1 0 00-1.176 0l-3.36 2.444c-.784.57-1.838-.197-1.539-1.118l1.286-3.946a1 1 0 00-.364-1.118L2.075 9.373c-.783-.57-.38-1.81.588-1.81h4.15a1 1 0 00.95-.69l1.286-3.946z" /></svg>
        Premium Moodboard
      </h2>
      <span class="bg-pink-100 text-pink-600 px-3 py-1 rounded-full text-sm font-semibold">Ksh.250</span>
    </div>

    <div class="mt-4 grid grid-cols-5 gap-4">
      <img src="/placeholder1.jpg" alt="Preview 1" class="rounded-xl object-cover w-full h-32">
      <img src="/placeholder2.jpg" alt="Preview 2" class="rounded-xl object-cover w-full h-32">
      <div class="col-span-3 bg-gray-100 rounded-xl flex items-center justify-center text-gray-400 text-sm">
        <span class="text-center">Unlock to view 12 more images<br/>+ caption pack, filter preset & IG bios</span>
      </div>
    </div>

    <div class="mt-6 flex flex-wrap justify-between items-center gap-4">
      <button class="bg-pink-600 text-white px-6 py-2 rounded-full shadow hover:bg-pink-700">💖 Unlock Moodboard</button>
      <button class="text-pink-600 font-semibold hover:underline">🔥 React to Preview</button>
      <button class="text-gray-500 hover:underline">🔖 Save to Wishlist</button>
      <span class="text-sm text-gray-400">💬 40 Reactions · 🔓 103 Unlocks</span>
    </div>
  </div>

  
  <div class="bg-white rounded-xl shadow-md p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Alt Girl Room Vibes <span class="text-gray-400 text-sm">(Basic)</span></h2>
    <div class="grid grid-cols-6 gap-2">
      <img src="/basic1.jpg" alt="Basic 1" class="rounded-xl object-cover w-full h-28">
      <img src="/basic2.jpg" alt="Basic 2" class="rounded-xl object-cover w-full h-28">
      <img src="/basic3.jpg" alt="Basic 3" class="rounded-xl object-cover w-full h-28">
      <img src="/basic4.jpg" alt="Basic 4" class="rounded-xl object-cover w-full h-28">
      <img src="/basic5.jpg" alt="Basic 5" class="rounded-xl object-cover w-full h-28">
      <img src="/basic6.jpg" alt="Basic 6" class="rounded-xl object-cover w-full h-28">
    </div>
    <div class="mt-4 flex justify-between items-center">
      <div class="flex gap-4">
        <button class="text-pink-600 hover:underline">🔥 React</button>
        <button class="text-gray-600 hover:underline">📁 Save</button>
        <button class="text-gray-600 hover:underline">📣 Share</button>
      </div>
      <span class="text-sm text-gray-400">💬 22 Reactions</span>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow-sm p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">My Dream Picnic <span class="text-green-500 text-sm">(Free)</span></h2>
    <div class="grid grid-cols-6 gap-2">
      <img src="/free1.jpg" alt="Free 1" class="rounded-xl object-cover w-full h-24">
      <img src="/free2.jpg" alt="Free 2" class="rounded-xl object-cover w-full h-24">
      <img src="/free3.jpg" alt="Free 3" class="rounded-xl object-cover w-full h-24">
      <img src="/free4.jpg" alt="Free 4" class="rounded-xl object-cover w-full h-24">
      <img src="/free5.jpg" alt="Free 5" class="rounded-xl object-cover w-full h-24">
      <img src="/free6.jpg" alt="Free 6" class="rounded-xl object-cover w-full h-24">
    </div>
    <div class="mt-4 flex justify-between items-center">
      <div class="flex gap-4">
        <button class="text-pink-600 hover:underline">❤️ React</button>
        <button class="text-gray-600 hover:underline">💬 Comment</button>
        <button class="text-gray-600 hover:underline">🎨 Remix</button>
      </div>
      <span class="text-sm text-gray-400">💬 14 Reactions · 🗨️ 3 Comments</span>
    </div>
  </div>
</div>
 -->
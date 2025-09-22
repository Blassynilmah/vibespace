@extends('layouts.app')

@section('content')

<!-- Mobile Nav Bar (matches notifications/index) -->
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
                class="flex flex-col items-center transition duration-300 ease-in-out hover:text-yellow-300">
                💌
                <span class="text-[11px] mt-1 tracking-wide">Messages</span>
            </a>
            <template x-if="$store.messaging && $store.messaging.unreadConversationsCount > 0">
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
        <a href="{{ route('settings') }}"
            class="flex flex-col items-center text-yellow-300 font-semibold transition duration-300 ease-in-out">
            ⚙️
            <span class="text-[11px] mt-1 tracking-wide">Settings</span>
        </a>
    </div>
</div>

<!-- Desktop Sidebar Navigation (matches notifications/index) -->
<div class="hidden lg:block w-1/5">
    <div class="sticky top-24 space-y-4">
        <h3 class="text-xl font-semibold mb-4">Navigation</h3>
        <a href="{{ route('home') }}" class="block px-4 py-2 rounded-lg font-medium text-sm text-gray-700 hover:bg-pink-100 transition">Home</a>
        <a href="/my-vibes" class="block px-4 py-2 rounded-lg font-medium text-sm text-gray-700 hover:bg-pink-100 transition">Me</a>
        <a href="/notifications" class="block px-4 py-2 rounded-lg font-medium text-sm text-gray-700 hover:bg-pink-100 transition">Alerts</a>
        <a href="{{ route('settings') }}" class="block px-4 py-2 rounded-lg font-medium text-sm text-yellow-700 bg-yellow-100">Settings</a>
    </div>
</div>

<!-- Main Settings Content -->
<div x-data="{ open: null }" class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-extrabold text-pink-600 mb-8">Settings</h1>
    <div class="bg-white rounded-lg shadow p-6 space-y-6">

        <!-- Profile -->
        <div>
            <button @click="open === 1 ? open = null : open = 1"
                class="w-full flex justify-between items-center text-lg font-semibold mb-2 text-left focus:outline-none">
                <span>Profile</span>
                <span x-show="open !== 1">▼</span>
                <span x-show="open === 1">▲</span>
            </button>
            <div x-show="open === 1" x-transition class="pl-2 mt-2 space-y-3">
                <div>
                    <label class="block text-sm font-medium">Username</label>
                    <input type="text" class="w-full border rounded px-3 py-2" placeholder="Change your username">
                </div>
                <div>
                    <label class="block text-sm font-medium">Profile Picture</label>
                    <input type="file" class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium">Bio</label>
                    <textarea class="w-full border rounded px-3 py-2" rows="2" placeholder="Update your bio"></textarea>
                </div>
                <button class="bg-pink-500 text-white px-4 py-2 rounded hover:bg-pink-600">Save Profile</button>
            </div>
        </div>

        <!-- Account -->
        <div>
            <button @click="open === 2 ? open = null : open = 2"
                class="w-full flex justify-between items-center text-lg font-semibold mb-2 text-left focus:outline-none">
                <span>Account</span>
                <span x-show="open !== 2">▼</span>
                <span x-show="open === 2">▲</span>
            </button>
            <div x-show="open === 2" x-transition class="pl-2 mt-2 space-y-3">
                <div>
                    <label class="block text-sm font-medium">Email Address</label>
                    <input type="email" class="w-full border rounded px-3 py-2" placeholder="Change your email">
                </div>
                <div>
                    <label class="block text-sm font-medium">Change Password</label>
                    <input type="password" class="w-full border rounded px-3 py-2" placeholder="New password">
                </div>
                <button class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">Update Account</button>
            </div>
        </div>

        <!-- Notifications -->
        <div>
            <button @click="open === 3 ? open = null : open = 3"
                class="w-full flex justify-between items-center text-lg font-semibold mb-2 text-left focus:outline-none">
                <span>Notifications</span>
                <span x-show="open !== 3">▼</span>
                <span x-show="open === 3">▲</span>
            </button>
            <div x-show="open === 3" x-transition class="pl-2 mt-2 space-y-3">
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" class="accent-pink-500"> Email notifications
                    </label>
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" class="accent-pink-500"> Push notifications
                    </label>
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" class="accent-pink-500"> VibeSpace alerts
                    </label>
                </div>
                <button class="bg-pink-500 text-white px-4 py-2 rounded hover:bg-pink-600">Save Notification Settings</button>
            </div>
        </div>

        <!-- Privacy -->
        <div>
            <button @click="open === 4 ? open = null : open = 4"
                class="w-full flex justify-between items-center text-lg font-semibold mb-2 text-left focus:outline-none">
                <span>Privacy</span>
                <span x-show="open !== 4">▼</span>
                <span x-show="open === 4">▲</span>
            </button>
            <div x-show="open === 4" x-transition class="pl-2 mt-2 space-y-3">
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" class="accent-pink-500"> Make my profile private
                    </label>
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" class="accent-pink-500"> Hide my moodboards from search
                    </label>
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" class="accent-pink-500"> Block direct messages
                    </label>
                </div>
                <button class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">Save Privacy Settings</button>
            </div>
        </div>

        <!-- Danger Zone -->
        <div>
            <button @click="open === 5 ? open = null : open = 5"
                class="w-full flex justify-between items-center text-lg font-semibold mb-2 text-left focus:outline-none">
                <span>Danger Zone</span>
                <span x-show="open !== 5">▼</span>
                <span x-show="open === 5">▲</span>
            </button>
            <div x-show="open === 5" x-transition class="pl-2 mt-2 space-y-3">
                <div>
                    <button class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 w-full">Delete Account</button>
                </div>
                <div class="text-xs text-red-700 mt-2">
                    Warning: This action is irreversible. All your data will be permanently deleted.
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
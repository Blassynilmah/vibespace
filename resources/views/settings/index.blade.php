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
<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-extrabold text-pink-600 mb-8">Settings</h1>
    <div class="bg-white rounded-lg shadow p-6 space-y-6">
        <div>
            <h2 class="text-lg font-semibold mb-2">Profile</h2>
            <a href="" class="text-pink-500 hover:underline">Edit Profile</a>
        </div>
        <div>
            <h2 class="text-lg font-semibold mb-2">Account</h2>
            <a href="" class="text-pink-500 hover:underline">Change Password</a>
        </div>
        <div>
            <h2 class="text-lg font-semibold mb-2">Notifications</h2>
            <a href="" class="text-pink-500 hover:underline">Notification Preferences</a>
        </div>
        <div>
            <h2 class="text-lg font-semibold mb-2">Privacy</h2>
            <a href="" class="text-pink-500 hover:underline">Privacy Settings</a>
        </div>
        <div>
            <h2 class="text-lg font-semibold mb-2">Danger Zone</h2>
            <a href="" class="text-red-500 hover:underline">Delete Account</a>
        </div>
    </div>
</div>
@endsection
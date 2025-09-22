{{-- filepath: resources/views/settings/index.blade.php --}}
@extends('layouts.app')

@section('content')

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
            class="flex flex-col items-center text-yellow-300 font-semibold transition duration-300 ease-in-out">
        🔔
        <span class="text-[11px] mt-1 tracking-wide">Alerts</span>
    </a>
    <!-- Settings -->
    <a href="{{ route('settings') }}"
            class="flex flex-col items-center transition duration-300 ease-in-out hover:text-yellow-300">
        ⚙️
        <span class="text-[11px] mt-1 tracking-wide">Settings</span>
    </a>
</div>

<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-extrabold text-pink-600 mb-8">Settings</h1>
    <div class="bg-white rounded-lg shadow p-6 space-y-6">
        <div>
            <h2 class="text-lg font-semibold mb-2">Profile</h2>
            <a href="{{ route('profile.edit') }}" class="text-pink-500 hover:underline">Edit Profile</a>
        </div>
        <div>
            <h2 class="text-lg font-semibold mb-2">Account</h2>
            <a href="{{ route('password.change') }}" class="text-pink-500 hover:underline">Change Password</a>
        </div>
        <div>
            <h2 class="text-lg font-semibold mb-2">Notifications</h2>
            <a href="{{ route('notifications.settings') }}" class="text-pink-500 hover:underline">Notification Preferences</a>
        </div>
        <div>
            <h2 class="text-lg font-semibold mb-2">Privacy</h2>
            <a href="{{ route('privacy.settings') }}" class="text-pink-500 hover:underline">Privacy Settings</a>
        </div>
        <div>
            <h2 class="text-lg font-semibold mb-2">Danger Zone</h2>
            <a href="{{ route('account.delete') }}" class="text-red-500 hover:underline">Delete Account</a>
        </div>
    </div>
</div>
@endsection
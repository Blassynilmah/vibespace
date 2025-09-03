@extends('layouts.app')

@section('content')
<div x-data="notificationInbox()" x-init="init()" class="flex flex-col lg:flex-row h-[100dvh] overflow-hidden">
    <!-- 📬 Notifications Page Mobile Nav (copied from messages page) -->
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
                 class="flex flex-col items-center text-yellow-300 font-semibold transition duration-300 ease-in-out">
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

    <!-- Main Notifications Area -->
    <div class="flex-1 flex flex-col bg-gray-50 overflow-y-auto">
        <div class="max-w-2xl mx-auto px-4 py-8">
            <h1 class="text-2xl font-bold mb-6">Notifications</h1>
            <div class="bg-white rounded-lg shadow divide-y">
                <template x-if="!isLoading && notifications.length === 0">
                    <div class="p-4 text-gray-400 text-center">No notifications yet.</div>
                </template>
                <template x-for="group in notifications" :key="group.type + '-' + (group.mood_board_id || '') + '-' + (group.reaction_type || '') + '-' + (group.comment_id || '') + '-' + (group.created_at || '')">
                    <div class="p-4 cursor-pointer hover:bg-pink-50 transition">
                        <div class="text-gray-800">
                            <template x-if="group.type === 'reaction' || group.type === 'comment_reaction'">
                                <span>
                                    <span x-text="group.usernames.length > 1 ? group.usernames[0] + ' and ' + (group.usernames.length - 1) + ' other' + (group.usernames.length - 1 > 1 ? 's' : '') : group.usernames[0]"></span>
                                    reacted with <span x-text="group.reaction_type"></span> to your mood board.
                                </span>
                            </template>
                            <template x-if="group.type === 'comment'">
                                <span>
                                    <span x-text="group.usernames.length > 1 ? group.usernames[0] + ' and ' + (group.usernames.length - 1) + ' other' + (group.usernames.length - 1 > 1 ? 's' : '') : group.usernames[0]"></span>
                                    commented on your mood board.
                                </span>
                            </template>
                            <template x-if="group.type === 'reply'">
                                <span>
                                    <span x-text="group.usernames.length > 1 ? group.usernames[0] + ' and ' + (group.usernames.length - 1) + ' other' + (group.usernames.length - 1 > 1 ? 's' : '') : group.usernames[0]"></span>
                                    replied to a comment on your mood board.
                                </span>
                            </template>
                            <template x-if="group.type === 'follow'">
                                <span>
                                    <span x-text="group.usernames.length > 1 ? group.usernames[0] + ' and ' + (group.usernames.length - 1) + ' other' + (group.usernames.length - 1 > 1 ? 's' : '') : group.usernames[0]"></span>
                                    followed you.
                                </span>
                            </template>
                            <template x-if="group.type === 'unfollow'">
                                <span>
                                    <span x-text="group.usernames.length > 1 ? group.usernames[0] + ' and ' + (group.usernames.length - 1) + ' other' + (group.usernames.length - 1 > 1 ? 's' : '') : group.usernames[0]"></span>
                                    unfollowed you.
                                </span>
                            </template>
                            <template x-if="!['reaction','comment_reaction','comment','reply','follow','unfollow'].includes(group.type)">
                                <span x-text="group.latest_message"></span>
                            </template>
                        </div>
                        <div class="text-xs text-gray-500 mt-1" x-text="window.dayjs ? dayjs(group.created_at).fromNow() : group.created_at"></div>
                    </div>
                </template>
            </div>
            <div class="mt-4 flex justify-center">
                <button @click="fetchNotifications(page-1)" :disabled="page <= 1" class="px-3 py-1 rounded bg-gray-200 mr-2" :class="{'opacity-50': page <= 1}">&laquo; Prev</button>
                <span class="px-2 text-gray-600">Page <span x-text="page"></span></span>
                <button @click="fetchNotifications(page+1)" :disabled="!hasMore" class="px-3 py-1 rounded bg-gray-200 ml-2" :class="{'opacity-50': !hasMore}">Next &raquo;</button>
            </div>
        </div>
    </div>

    <!-- Right Sidebar - Desktop Navigation (optional, can be added for parity) -->
    <div class="hidden lg:block w-1/5">
        <div class="sticky top-24 space-y-4">
            <h3 class="text-xl font-semibold mb-4">Navigation</h3>
            <a href="{{ route('home') }}" class="block px-4 py-2 rounded-lg font-medium text-sm text-gray-700 hover:bg-pink-100 transition">Home</a>
            <a href="/my-vibes" class="block px-4 py-2 rounded-lg font-medium text-sm text-gray-700 hover:bg-pink-100 transition">Me</a>
            <a href="/notifications" class="block px-4 py-2 rounded-lg font-medium text-sm text-yellow-700 bg-yellow-100">Alerts</a>
            <a href="/settings" class="block px-4 py-2 rounded-lg font-medium text-sm text-gray-700 hover:bg-pink-100 transition">Settings</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>


document.addEventListener('alpine:init', () => {
    Alpine.data('notificationInbox', () => ({
        notifications: [],
        page: 1,
        hasMore: false,
        isLoading: false,
        async init() {
            // Check authentication before fetching notifications
            const res = await fetch('/api/user', { credentials: 'include' });
            console.log('[notifications] /api/user response:', res);
            if (!res.ok) {
                window.location.href = '/login';
                return;
            }
            const user = await res.json();
            console.log('[notifications] /api/user data:', user);
            this.fetchNotifications(1);
            if (Alpine.store('messaging') && typeof Alpine.store('messaging').fetchUnreadConversationsCount === 'function') {
                Alpine.store('messaging').fetchUnreadConversationsCount();
            }
        },
async fetchNotifications(page = 1) {
    this.isLoading = true;
    try {
        const res = await fetch(`/api/notifications?page=${page}`, {
            credentials: 'include',
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) throw new Error('Failed to fetch notifications');

        const data = await res.json();
        console.log('[notifications] /api/notifications data:', data);

        // 🔑 handle both paginator style (with `data`) and plain array
        const items = Array.isArray(data) ? data : (data.data || []);

        this.notifications = items.map(n => ({
            ...n,
            created_at_human: window.dayjs ? dayjs(n.created_at).fromNow() : n.created_at
        }));

        this.page = data.current_page || 1;
        this.hasMore = !!data.next_page_url;
    } catch (e) {
        this.notifications = [];
    } finally {
        this.isLoading = false;
    }
}
    
    }));
});

</script>
@endpush

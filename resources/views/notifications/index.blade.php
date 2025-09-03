@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Notifications</h1>
    <div class="bg-white rounded-lg shadow divide-y">
        @forelse($notifications as $notification)
            <div class="p-4">
                <div class="text-gray-800">{!! $notification->data['message'] ?? 'You have a new notification.' !!}</div>
                <div class="text-xs text-gray-500 mt-1">{{ $notification->created_at->diffForHumans() }}</div>
            </div>
        @empty
            <div class="p-4 text-gray-400 text-center">No notifications yet.</div>
        @endforelse
    </div>
    <div class="mt-4">
        {{ $notifications->links() }}
    </div>
</div>
@endsection

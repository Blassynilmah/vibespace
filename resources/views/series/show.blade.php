@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto py-8">
    <h1 class="text-2xl font-bold mb-2">{{ $series->title }}</h1>
    <p class="text-gray-600 mb-4">{{ $series->description }}</p>

    <div class="space-y-4">
        @forelse ($series->posts as $post)
            <div class="p-4 bg-white shadow rounded-xl">
                @if ($post->type === 'image')
                    <img src="{{ $post->content }}" class="rounded-lg w-full">
                @elseif ($post->type === 'video')
                    <video src="{{ $post->content }}" class="rounded-lg w-full" controls></video>
                @elseif ($post->type === 'text')
                    <p class="text-gray-800 italic">"{{ $post->content }}"</p>
                @endif
                @if ($post->caption)
                    <p class="text-xs text-gray-500 mt-1">{{ $post->caption }}</p>
                @endif
            </div>
        @empty
            <p>No posts in this series yet.</p>
        @endforelse
    </div>
</div>
@endsection

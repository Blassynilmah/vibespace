@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-8">
    <h1 class="text-2xl font-bold mb-6">📺 All Series</h1>

    @foreach ($series as $s)
        <a href="{{ route('series.show', $s->id) }}" class="block mb-4 p-4 bg-white rounded-xl shadow hover:bg-gray-50">
            <div class="font-semibold text-lg">{{ $s->title }}</div>
            <div class="text-sm text-gray-500">{{ $s->description }}</div>
        </a>
    @endforeach
</div>
@endsection

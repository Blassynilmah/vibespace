<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Teaser;
use App\Models\UserFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class TeaserController extends Controller
{

public function store(Request $request)
{
    \Log::info('Teaser upload initiated', ['user_id' => Auth::id(), 'payload' => $request->all()]);

    try {
        $request->validate([
            'description'    => 'nullable|string|max:1000',
            'hashtags'       => 'nullable|string|max:255',
            'expires_after'  => 'nullable|integer|in:24,48,72,168',
            'video_id'       => 'required|integer|exists:user_files,id',
            'teaser_mood'    => 'required|string|in:hype,funny,shock,love', // <-- add this line
        ]);

        $user = Auth::user();

        $userFile = UserFile::where('id', $request->video_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $sourcePath = 'user_files/' . $user->id . '/' . basename($userFile->path);
        $extension  = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $targetDir  = 'teasers/' . $user->id . '/';
        $targetName = \Str::random(40) . '.' . $extension;
        $targetPath = $targetDir . $targetName;

        \Storage::disk('public')->makeDirectory($targetDir);

        if (!\Storage::disk('public')->exists($sourcePath)) {
            \Log::error('Source video not found', ['path' => $sourcePath]);
            return response()->json(['error' => 'Video file not found.'], 404);
        }

        \Storage::disk('public')->copy($sourcePath, $targetPath);

        // Calculate expires_on on the backend
        $expiresAfter = $request->expires_after ? (int) $request->expires_after : null;
        $expiresOn = $expiresAfter ? now()->addHours($expiresAfter) : null;

        $teaser = Teaser::create([
            'user_id'       => $user->id,
            'teaser_id'     => \Str::uuid(),
            'hashtags'      => $request->hashtags,
            'video'         => $targetPath,
            'expires_after' => $expiresAfter,
            'expires_on'    => $expiresOn,
            'description'   => $request->description,
            'teaser_mood'   => $request->teaser_mood, // <-- add this line
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        \Log::info('Teaser created successfully', ['teaser_id' => $teaser->id]);

        return response()->json([
            'success'  => true,
            'redirect' => route('teasers.show', $teaser->id),
        ]);
    } catch (\Exception $e) {
        \Log::error('Teaser upload failed', [
            'user_id' => Auth::id(),
            'error'   => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
        ]);

        return response()->json(['error' => 'Upload failed. Please try again later.'], 500);
    }
}

public function myTeasers()
{
    try {
        $user = Auth::user();

        if (!$user) {
            \Log::warning('myTeasers: No authenticated user found');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        \Log::info('myTeasers: Fetching teasers for user', ['user_id' => $user->id]);

        $teasers = Teaser::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $teasers->transform(function ($teaser) use ($user) {
            $teaser->username = $user->username;
            return $teaser;
        });

        \Log::info('myTeasers: Successfully retrieved teasers', ['count' => $teasers->count()]);

        return response()->json([
            'teasers' => $teasers,
            'username' => $user->username,
        ]);
    } catch (\Exception $e) {
        \Log::error('myTeasers: Failed to fetch teasers', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json(['error' => 'Failed to load teasers. Please try again later.'], 500);
    }
}

    public function allTeasers(Request $request)
    {
        $teasers = Teaser::with('user:id,username')
            ->orderByDesc('created_at')
            ->paginate(20);

        // Each teaser will have a 'user' relation with 'id' and 'username'
        return response()->json([
            'teasers' => $teasers->items(),
            'current_page' => $teasers->currentPage(),
            'last_page' => $teasers->lastPage(),
            'total' => $teasers->total(),
        ]);
    }
}
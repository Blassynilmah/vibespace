<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Models\MoodBoard;
use App\Models\User;
use Illuminate\Support\Str;
use App\Models\UserFile;

class BoardController extends Controller
{
    // 🌿 Show Recent MoodBoards (Home Page)
public function index()
{
    $boards = MoodBoard::with(['user.profilePicture'])->latest()->take(10)->get();

    $boards->each(function ($board) {
        $board->user->profile_picture = $board->user->profilePicture?->path ?? null;
    });

    return view('home', compact('boards'));
}


    // 📝 Show Create Form
    public function create()
    {
        return view('boards.create');
    }

public function store(Request $request)
{
    \Log::info('Starting moodboard creation', ['user_id' => auth()->id(), 'input' => $request->all()]);

    try {
        // 🔍 Validate input
        $validated = $request->validate([
            'title'        => 'nullable|string|max:255',
            'description'  => 'nullable|string|max:1000',
            'latest_mood'  => 'required|string|in:relaxed,craving,hyped,obsessed',
            'image_ids'    => 'nullable',
        ]);

        \Log::debug('Validation passed', ['validated' => $validated]);

        // Convert image_ids to array
        $fileIds = is_array($request->image_ids) 
            ? $request->image_ids 
            : json_decode($request->image_ids ?? '[]', true);

        \Log::debug('Processed file IDs', ['fileIds' => $fileIds, 'type' => gettype($fileIds)]);

        // 🧠 Business rules
        $hasTitle = filled($validated['title'] ?? null);
        $hasFiles = !empty($fileIds);
        
        if (!($hasTitle || $hasFiles)) {
            \Log::warning('Validation failed - no title or files');
            return response()->json([
                'success' => false,
                'error' => 'Please provide at least a title or some media.',
            ], 422);
        }

        // 📦 Create Board
        $board = new MoodBoard([
            'title'        => $validated['title'] ?? null,
            'description'  => $validated['description'] ?? null,
            'latest_mood'  => $validated['latest_mood'],
            'user_id'      => Auth::id(),
            'image'        => null,
            'video'        => null,
        ]);

        $board->save(); // Save first to get an ID for the directory

        \Log::debug('Board model created', ['board' => $board->toArray()]);

        // Process selected files
        if (!empty($fileIds)) {
            \Log::debug('Processing files', ['count' => count($fileIds)]);
            
            $file = UserFile::whereIn('id', $fileIds)
                ->where('user_id', Auth::id())
                ->first();

            \Log::debug('File retrieved', ['file' => $file ? $file->toArray() : null]);

            if ($file) {
                // Define paths using Storage facade with 'public' disk
                $sourcePath = 'user_files/' . Auth::id() . '/' . basename($file->path);
                $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
                $targetDir = 'moodboard_uploads/' . $board->id . '/';
                $targetFilename = Str::random(40) . '.' . $extension;
                $targetPath = $targetDir . $targetFilename;

                \Log::debug('File paths', [
                    'source' => $sourcePath,
                    'target' => $targetPath,
                    'extension' => $extension
                ]);

                // Ensure directory exists
                Storage::disk('public')->makeDirectory($targetDir);
                
                if (Storage::disk('public')->exists($sourcePath)) {
                    \Log::debug('Source file exists, attempting copy');
                    
                    // Copy the file
                    Storage::disk('public')->copy($sourcePath, $targetPath);
                    
                    // Determine if file is image or video
                    $isVideo = preg_match('/\.(mp4|mov|avi|webm)$/i', $targetPath);
                    
                    \Log::debug('File type detection', [
                        'isVideo' => $isVideo,
                        'path' => $targetPath
                    ]);
                    
                    // Store relative path in database
                    if ($isVideo) {
                        $board->video = $targetPath;
                        \Log::info('Video path assigned', ['path' => $targetPath]);
                    } else {
                        $board->image = $targetPath;
                        \Log::info('Image path assigned', ['path' => $targetPath]);
                    }
                    
                    $board->save();
                } else {
                    \Log::error('Source file does not exist', [
                        'path' => $sourcePath,
                        'full_path' => Storage::disk('public')->path($sourcePath)
                    ]);
                }
            } else {
                \Log::warning('No valid files found for user', [
                    'fileIds' => $fileIds,
                    'user_id' => Auth::id()
                ]);
            }
        }

        \Log::info('Board saved successfully', ['board_id' => $board->id, 'saved_data' => $board->toArray()]);

        return response()->json([
            'success' => true,
            'redirect' => route('moodboards.show', $board->id),
        ]);

    } catch (ValidationException $e) {
        \Log::error('Validation failed', ['errors' => $e->errors()]);
        return response()->json([
            'success' => false,
            'errors' => $e->errors(),
        ], 422);

    } catch (\Exception $e) {
        \Log::error('Moodboard creation failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'success' => false,
            'error' => 'Something went wrong: ' . $e->getMessage(),
        ], 500);
    }
}

    // 🔍 Show Individual MoodBoard
    public function show($id)
    {
        $board = MoodBoard::with([
            'user',
            'posts',
            'comments.user',
            'comments.replies',
            'reactions' => fn($q) => $q->where('user_id', auth()->id()),
        ])->withCount('comments')->findOrFail($id);

        $reactionCounts = $board->reactions()
            ->selectRaw('mood, COUNT(*) as count')
            ->groupBy('mood')
            ->pluck('count', 'mood')
            ->toArray();

        foreach (['relaxed', 'craving', 'hyped', 'obsessed'] as $mood) {
            $board->{$mood . '_count'} = $reactionCounts[$mood] ?? 0;
        }

        $board->user_reacted_mood = optional($board->reactions->first())->mood;

        $board->comments->each(function ($comment) {
        $comment->reply_count = $comment->replies->count();
        $comment->like_count = $comment->commentReactions()->where('type', 'like')->count();
        $comment->dislike_count = $comment->commentReactions()->where('type', 'dislike')->count();
        $userReaction = $comment->commentReactions()->where('user_id', auth()->id())->first();
        $comment->user_reacted_type = $userReaction?->type;
    });

        return view('boards.show', compact('board'));
    }

    // 🛠️ Edit Board Form
    public function edit($id)
    {
        $board = MoodBoard::findOrFail($id);
        return view('boards.edit', compact('board'));
    }

    // 🔁 Update Existing Board
    public function update(Request $request, $id)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $board = MoodBoard::findOrFail($id);
        $board->update([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        return redirect()->route('boards.show', $board->id)->with('success', 'Board updated ✅');
    }

    // ❌ Delete MoodBoard
    public function destroy($id)
    {
        $board = MoodBoard::findOrFail($id);
        $board->delete();

        return redirect()->route('home')->with('success', 'Board deleted 🗑️');
    }
// 👤 Public User Profile Page
public function showUserProfile($username)
{
    $user = User::with('profilePicture')
        ->withCount(['followers', 'following'])
        ->where('username', $username)
        ->firstOrFail();

    $viewerId = auth()->id();

    $isFollowing = false;

    if ($viewerId) {
        $isFollowing = $user->followers()->where('follower_id', $viewerId)->exists();
    }

    return view('space.show', [
        'user' => $user,
        'username' => $username,
        'viewerId' => $viewerId,
        'isFollowing' => $isFollowing,
        'followerCount' => $user->followers_count, // 👈 use the withCount result
    ]);
}
public function me()
{
    $user = auth()->user();

    // assuming your Board model has a 'user_id'
    $moodboards = \App\Models\Board::where('user_id', $user->id)
        ->latest()
        ->with('media') // if you have media or relationships
        ->get();

    return view('boards.me', compact('moodboards'));
}
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\UserFile;
use App\Models\FileList;

class FileController extends Controller
{
    public function index()
    {
        $files = Auth::user()->userFiles()->latest()->get();
        return view('files.index', compact('files'));
    }

    public function create()
    {
        return view('files.create');
    }

    public function store(Request $request)
    {
        Log::info("🚀 File upload initiated", [
            'user_id' => auth()->id(),
            'has_file' => $request->hasFile('file'),
            'filename' => $request->file('file')?->getClientOriginalName(),
            'list_id' => $request->input('list_id'),
        ]);

        $validated = $request->validate([
            'file' => 'required|file|max:10240',
            'list_id' => 'nullable|exists:file_lists,id',
        ]);

        $file = $validated['file'];
        $filename = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();

        $path = $file->storeAs(
            'user_files/' . auth()->id(),
            Str::random(40) . '.' . $extension,
            'public'
        );

        $userFile = UserFile::create([
            'filename' => $filename,
            'path' => $path,
            'content_type' => $file->getMimeType(),
            'user_id' => auth()->id(),
        ]);

        if ($request->filled('list_id')) {
            $list = FileList::find($validated['list_id']);
            $attachment = $list->items()->create([
                'file_id' => $userFile->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'File uploaded ✅',
            'file_id' => $userFile->id,
        ], 201);
    }
    
public function fetch(Request $request)
{
    $offset = (int) $request->query('offset', 0);
    $limit = (int) $request->query('limit', 20);
    $type = $request->query('type', 'all');
    $content = $request->query('content', 'all');
    $sort = $request->query('sort', 'latest');

    $user = Auth::user();
    $query = $user->userFiles();

    // 🖼️ Filter by type
    if ($type !== 'all') {
        $query->where(function ($q) use ($type) {
            $exts = $type === 'image'
                ? ['jpg', 'jpeg', 'png', 'gif', 'webp']
                : ['mp4', 'mov', 'avi', 'webm'];

            foreach ($exts as $ext) {
                $q->orWhere('path', 'like', "%.$ext");
            }
        });
    }

    // 🧼 Filter by content rating
    if ($content !== 'all') {
        $query->where('content_type', $content);
    }

    // 🕒 Sorting
    $query = $sort === 'latest' ? $query->latest() : $query->oldest();

    $files = $query->skip($offset)->take($limit)->get();

    // 📊 Summary counts for all user files (ignoring filters)
    $baseQuery = $user->userFiles();

    $imageCount = (clone $baseQuery)
        ->where(function ($q) {
            $q->where('path', 'like', '%.jpg')
              ->orWhere('path', 'like', '%.jpeg')
              ->orWhere('path', 'like', '%.png')
              ->orWhere('path', 'like', '%.gif')
              ->orWhere('path', 'like', '%.webp');
        })->count();

    $videoCount = (clone $baseQuery)
        ->where(function ($q) {
            $q->where('path', 'like', '%.mp4')
              ->orWhere('path', 'like', '%.mov')
              ->orWhere('path', 'like', '%.avi')
              ->orWhere('path', 'like', '%.webm');
        })->count();

    return response()->json([
        'files' => $files->map(fn($file) => [
            'id' => $file->id,
            'filename' => $file->filename,
            'path' => asset('storage/' . $file->path),
            'content_type' => $file->content_type,
            'created_at' => $file->created_at->toDateTimeString(),
        ]),
        'imageCount' => $imageCount,
        'videoCount' => $videoCount,
        'totalCount' => $imageCount + $videoCount,
    ]);
}

    public function fetchListItems($listId, Request $request)
    {
        $offset = (int) $request->query('offset', 0);
        $limit = (int) $request->query('limit', 20);
        $list = FileList::with(['items.file' => fn ($q) => $q->latest()])
            ->where('user_id', Auth::id())
            ->findOrFail($listId);

        $items = $list->items
            ->slice($offset)
            ->take($limit)
            ->map(fn($item) => [
                'id' => $item->file->id,
                'filename' => $item->file->filename,
                'path' => asset('storage/' . $item->file->path),
                'content_type' => $item->file->content_type,
                'created_at' => $item->file->created_at->toDateTimeString(),
            ]);

        return response()->json(['files' => $items]);
    }

public function getListsWithCounts()
{
    $user = Auth::user();

    // Base query for all media counts
    $allMediaQuery = $user->userFiles();

    // Get lists with filtered counts
    $lists = FileList::withCount([
        'items as imageCount' => function($q) {
            $q->whereHas('file', function($f) {
                $f->where(function($query) {
                    $query->where('path', 'like', '%.jpg')
                          ->orWhere('path', 'like', '%.jpeg')
                          ->orWhere('path', 'like', '%.png')
                          ->orWhere('path', 'like', '%.gif')
                          ->orWhere('path', 'like', '%.webp');
                });
            });
        },
        'items as videoCount' => function($q) {
            $q->whereHas('file', function($f) {
                $f->where(function($query) {
                    $query->where('path', 'like', '%.mp4')
                          ->orWhere('path', 'like', '%.mov')
                          ->orWhere('path', 'like', '%.avi')
                          ->orWhere('path', 'like', '%.webm');
                });
            });
        },
        'items as safeCount' => function($q) {
            $q->whereHas('file', function($f) {
                $f->where('content_type', 'safe');
            });
        },
        'items as adultCount' => function($q) {
            $q->whereHas('file', function($f) {
                $f->where('content_type', 'adult');
            });
        }
    ])
    ->where('user_id', $user->id)
    ->latest()
    ->get();

    // Count all user files by type and content
    $imageCount = $user->userFiles()
        ->where(function($q) {
            $q->where('path', 'like', '%.jpg')
              ->orWhere('path', 'like', '%.jpeg')
              ->orWhere('path', 'like', '%.png')
              ->orWhere('path', 'like', '%.gif')
              ->orWhere('path', 'like', '%.webp');
        })
        ->count();

    $videoCount = $user->userFiles()
        ->where(function($q) {
            $q->where('path', 'like', '%.mp4')
              ->orWhere('path', 'like', '%.mov')
              ->orWhere('path', 'like', '%.avi')
              ->orWhere('path', 'like', '%.webm');
        })
        ->count();

    $safeCount = $user->userFiles()
        ->where('content_type', 'safe')
        ->count();

    $adultCount = $user->userFiles()
        ->where('content_type', 'adult')
        ->count();

    return response()->json([
        'allMedia' => [
            'imageCount' => $imageCount,
            'videoCount' => $videoCount,
            'safeCount' => $safeCount,
            'adultCount' => $adultCount,
            'totalCount' => $imageCount + $videoCount,
        ],
        'lists' => $lists->map(function($list) {
            return [
                'id' => $list->id,
                'name' => $list->name,
                'imageCount' => $list->imageCount ?? 0,
                'videoCount' => $list->videoCount ?? 0,
                'safeCount' => $list->safeCount ?? 0,
                'adultCount' => $list->adultCount ?? 0
            ];
        }),
    ]);
}
}
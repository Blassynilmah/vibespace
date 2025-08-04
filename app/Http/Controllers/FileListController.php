<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FileList;
use App\Models\FileListItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class FileListController extends Controller
{
public function index()
{
    $lists = \App\Models\FileList::with('items.file')
        ->where('user_id', auth()->id())
        ->get();

    return response()->json($lists);
}

    public function store(Request $request)
{
    $validated = $request->validate(['name' => 'required|string|max:255']);
    $list = FileList::create([
        'user_id' => auth()->id(),
        'name' => $validated['name']
    ]);
    return response()->json($list);
}

public function stats($id)
{
    $list = FileList::with(['items.file'])->where('user_id', auth()->id())->findOrFail($id);

    $files = $list->items->map(fn($item) => $item->file)->filter();

    $imageCount = $files->filter(fn($file) => preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file->path))->count();
    $videoCount = $files->filter(fn($file) => preg_match('/\.(mp4|mov|avi|webm)$/i', $file->path))->count();

    return response()->json([
        'list_id' => $list->id,
        'list_name' => $list->name,
        'imageCount' => $imageCount,
        'videoCount' => $videoCount,
        'totalCount' => $files->count(),
    ]);
}

public function destroy($id)
{
    $list = FileList::where('user_id', Auth::id())->findOrFail($id);

    // Detach related items (if cascade isn’t set)
    $list->items()->delete();

    $list->delete();

    return response()->json(['success' => true, 'message' => 'List deleted ✅']);
}

public function update(Request $request, $id)
{
    $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $list = FileList::where('user_id', Auth::id())->findOrFail($id);
    $list->name = $request->input('name');
    $list->save();

    return response()->json([
        'success' => true,
        'message' => 'List renamed ✅',
        'list' => [
            'id' => $list->id,
            'name' => $list->name,
        ],
    ]);
}

public function attach(Request $request, FileList $list)
{
    $request->validate([
        'file_ids' => 'required|array',
        'file_ids.*' => 'integer|exists:user_files,id',
    ]);

    if ($list->user_id !== auth()->id()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    foreach ($request->file_ids as $fileId) {
        try {
            FileListItem::firstOrCreate([
                'file_list_id' => $list->id,
                'file_id' => $fileId,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Failed to attach file ID '.$fileId, ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to attach one or more files'], 500);
        }
    }

    return response()->json(['success' => true, 'message' => 'Files copied ✅']);
}

public function detach(Request $request, FileList $list)
{
    \Log::info("📤 Detach request received", [
        'user_id' => auth()->id(),
        'list_id' => $list->id,
        'file_ids' => $request->file_ids
    ]);

    $request->validate([
        'file_ids' => 'required|array',
        'file_ids.*' => 'integer|exists:user_files,id',
    ]);

    if ($list->user_id !== auth()->id()) {
        \Log::warning("🚫 Unauthorized detach attempt", ['list_id' => $list->id]);
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $deletedCount = FileListItem::where('file_list_id', $list->id)
        ->whereIn('file_id', $request->file_ids)
        ->delete();

    \Log::info("✅ Detached files", [
        'list_id' => $list->id,
        'deleted_count' => $deletedCount
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Files detached ✅',
        'removed_count' => $deletedCount
    ]);
}

public function bulkDeleteOrRemove(Request $request)
{
    $request->validate([
        'file_ids' => 'required|array',
        'file_ids.*' => 'integer|exists:user_files,id',
        'list_id'   => 'required|string',
    ]);

    $user = auth()->user();
    $listId = $request->list_id;
    $fileIds = $request->file_ids;

    if ($listId === 'all') {
        // 🚨 Full delete from DB
        $files = $user->files()->whereIn('id', $fileIds)->get();

        foreach ($files as $file) {
            // Optional: delete physical file from storage
            // Storage::delete($file->path);
            $file->delete();
        }

        \Log::info("🧨 Files permanently deleted by user", [
            'user_id' => $user->id,
            'deleted_count' => count($files)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Files permanently deleted 🗑️'
        ]);
    }

    // 🧹 Remove entries from selected list only
    $list = FileList::where('id', $listId)
        ->where('user_id', $user->id)
        ->firstOrFail();

    $deleted = FileListItem::where('file_list_id', $list->id)
        ->whereIn('file_id', $fileIds)
        ->delete();

    \Log::info("🚫 Files removed from list", [
        'user_id' => $user->id,
        'file_list_id' => $listId,
        'removed_count' => $deleted
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Files removed from list ✅'
    ]);
}
}

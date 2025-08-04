<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProfilePicture;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileSettingsController extends Controller
{
    public function updateUsername(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
        ]);

        $user->username = $request->username;
        $user->save();

        return response()->json([
            'username' => $user->username,
        ]);
    }

    public function updateProfilePicture(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'profile_picture' => 'required|image|max:2048',
        ]);

        $file = $request->file('profile_picture');
        $path = $file->store('profile_pictures', 'public');

        // Delete old one if exists
        if ($user->profilePicture) {
            Storage::disk('public')->delete($user->profilePicture->path);
            $user->profilePicture->update(['path' => $path]);
        } else {
            ProfilePicture::create([
                'user_id' => $user->id,
                'path' => $path,
            ]);
        }

        return response()->json([
            'profile_picture_url' => asset('storage/' . $path),
        ]);
    }

    public function updatePassword(Request $request)
{
    $user = auth()->user();

    $request->validate([
        'old_password' => 'required|string',
        'new_password' => 'required|string|min:8|confirmed', // must pass new_password + new_password_confirmation
    ]);

    // ✅ Check if old password matches
    if (!Hash::check($request->old_password, $user->password)) {
        return response()->json([
            'message' => 'The current password is incorrect.'
        ], 422);
    }

    // 🚫 Don't allow new password to be same as old
    if (Hash::check($request->new_password, $user->password)) {
        return response()->json([
            'message' => 'New password must be different from the old one.'
        ], 422);
    }

    // 🔐 Save new hashed password
    $user->password = Hash::make($request->new_password);
    $user->save();

    return response()->json([
        'message' => 'Password updated successfully.',
    ]);
}
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Favorite;
use App\Models\ChatMessage;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function users()
    {
        $users = User::orderBy('created_at', 'desc')->get()->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role ?? 'user',
                'nationality' => $u->nationality ?? null,
                'created_at' => $u->created_at?->toIso8601String(),
            ];
        });
        return response()->json($users);
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        if ($user->role === 'admin') {
            return response()->json(['message' => 'Cannot delete an admin user.'], 422);
        }
        $user->tokens()->delete();
        Favorite::where('user_id', $user->id)->delete();
        \App\Models\ChatMessage::where('user_id', $user->id)->delete();
        \App\Models\Translation::where('user_id', $user->id)->delete();
        \App\Models\Album::where('user_id', $user->id)->delete();
        $user->delete();
        return response()->json(['message' => 'User deleted.']);
    }

    public function blockUser($id)
    {
        $user = User::findOrFail($id);
        if ($user->role === 'admin') {
            return response()->json(['message' => 'Cannot block an admin user.'], 422);
        }
        $user->tokens()->delete();
        $user->forceFill(['role' => 'blocked'])->save();
        return response()->json(['message' => 'User blocked.']);
    }

    public function unblockUser($id)
    {
        $user = User::findOrFail($id);
        if ($user->role === 'admin') {
            return response()->json(['message' => 'Cannot unblock an admin user.'], 422);
        }
        $user->forceFill(['role' => 'user'])->save();
        return response()->json(['message' => 'User unblocked.']);
    }

    public function favoritesCount()
    {
        $count = Favorite::count();
        return response()->json(['count' => $count]);
    }

    public function chatCount()
    {
        $count = ChatMessage::count();
        return response()->json(['count' => $count]);
    }
}

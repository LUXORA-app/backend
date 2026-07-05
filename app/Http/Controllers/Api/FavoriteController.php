<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Get user's favorite landmarks.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $landmarks = $user
            ->favoriteLandmarks()
            ->withPivot('created_at')
            ->get()
            ->map(function ($landmark) {
                return [
                    'id' => $landmark->id,
                    'landmark_id' => $landmark->id,
                    'name' => $landmark->name,
                    'location' => $landmark->location,
                    'image_url' => $landmark->image_url,
                    'created_at' => $landmark->pivot?->created_at?->toIso8601String(),
                ];
            });

        return response()->json($landmarks);
    }

    /**
     * Add a landmark to favorites.
     */
    public function store(Request $request)
    {
        $request->validate([
            'landmark_id' => 'required|exists:landmarks,id',
        ]);

        $user = $request->user();
        $landmarkId = $request->landmark_id;

        // Check if already favorited
        if ($user->favoriteLandmarks()->where('landmark_id', $landmarkId)->exists()) {
            return response()->json(['message' => 'Already favorited'], 200);
        }

        $user->favoriteLandmarks()->attach($landmarkId);

        return response()->json(['message' => 'Added to favorites'], 201);
    }

    /**
     * Remove a landmark from favorites.
     */
    public function destroy(Request $request, $landmarkId)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $user->favoriteLandmarks()->detach($landmarkId);

        return response()->json(['message' => 'Removed from favorites'], 200);
    }
}

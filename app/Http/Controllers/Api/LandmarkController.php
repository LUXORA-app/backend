<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Landmark;
use Illuminate\Http\Request;

class LandmarkController extends Controller
{
    public function index()
    {
        return response()->json(Landmark::all());
    }

    public function show($id)
    {
        $landmark = Landmark::findOrFail($id);

        $isFavorited = false;
        if (auth('sanctum')->check()) {
            /** @var \App\Models\User $user */
            $user = auth('sanctum')->user();
            $isFavorited = $user->favoriteLandmarks()->where('landmark_id', $id)->exists();
        }

        $landmark->is_favorited = $isFavorited;

        return response()->json($landmark);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'image_url' => 'nullable|string', // Changed from url to string
            'image' => 'nullable|image|max:10240', // 10MB max
        ]);

        $data = $request->only(['name', 'description', 'location', 'latitude', 'longitude', 'image_url']);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('landmarks', 'public');
            $data['image_url'] = '/storage/' . $path;
        }

        $landmark = Landmark::create($data);
        return response()->json($landmark, 201);
    }

    public function update(Request $request, $id)
    {
        $landmark = Landmark::findOrFail($id);
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'image_url' => 'nullable|string',
            'image' => 'nullable|image|max:10240', // 10MB max
        ]);

        $data = $request->only(['name', 'description', 'location', 'latitude', 'longitude', 'image_url']);

        if ($request->hasFile('image')) {
            // Delete old image if it was a local file (check raw attribute to bypass getter)
            $oldImageUrl = $landmark->getRawOriginal('image_url');
            if ($oldImageUrl && str_starts_with($oldImageUrl, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $oldImageUrl);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('image')->store('landmarks', 'public');
            $data['image_url'] = '/storage/' . $path;
        }

        $landmark->update($data);
        return response()->json($landmark->fresh());
    }

    public function destroy($id)
    {
        $landmark = Landmark::findOrFail($id);
        $landmark->delete();
        return response()->json(['message' => 'Landmark deleted.'], 200);
    }

    public function scan(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // 10MB max
        ]);

        try {
            // Convert image to base64
            $image = $request->file('image');
            $imageData = base64_encode(file_get_contents($image->getPathname()));
            $imageBase64 = 'data:' . $image->getMimeType() . ';base64,' . $imageData;

            // Send to ML service
            $client = new \GuzzleHttp\Client();
            $response = $client->post('http://localhost:5000/api/predict', [
                'multipart' => [
                    [
                        'name'     => 'image',
                        'contents' => fopen($image->getPathname(), 'r'),
                        'filename' => $image->getClientOriginalName()
                    ]
                ],
                'timeout' => 30,
                'connect_timeout' => 10
            ]);

            $result = json_decode($response->getBody(), true);

            if ($result['success']) {
                return response()->json([
                    'message' => $result['message'] ?? 'Image scanned successfully',
                    'recognized_text' => $result['landmark_name'] ?? 'No landmark recognized',
                    'landmark_name' => $result['landmark_name'] ?? 'Unknown Landmark',
                    'confidence' => $result['confidence'] ?? 0.0,
                    'ml_result' => $result
                ]);
            } else {
                return response()->json([
                    'message' => 'Scanning failed',
                    'error' => $result['error'] ?? 'Unknown error occurred'
                ], 500);
            }

        } catch (\Exception $e) {
            // Fallback to placeholder if ML service is unavailable
            return response()->json([
                'message' => 'ML service unavailable, using fallback',
                'recognized_text' => 'Sample Hieroglyphic Text',
                'landmark_name' => 'Luxor Temple',
                'confidence' => 0.95,
                'error' => 'ML service error: ' . $e->getMessage()
            ], 200);
        }
    }
}

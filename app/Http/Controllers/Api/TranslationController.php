<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Translation;
use App\Support\PublicMediaUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TranslationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->translations()->latest()->get(),
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120',
            'translated_text' => 'required|string',
        ]);

        $path = $request->file('image')->store('translations', 'public');

        $translation = $request->user()->translations()->create([
            'image_url' => PublicMediaUrl::absolute($path),
            'translated_text' => $request->input('translated_text'),
            'original_text' => $request->input('original_text'),
            'confidence_score' => $request->input('confidence_score'),
        ]);

        return response()->json($translation, 201, [], JSON_UNESCAPED_UNICODE);
    }

    public function show(Request $request, $id)
    {
        $translation = $request->user()->translations()->findOrFail($id);
        return response()->json($translation, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function destroy(Request $request, $id)
    {
        $translation = $request->user()->translations()->findOrFail($id);

        $this->deleteStoredImageIfPossible($translation->image_url);
        $translation->delete();

        return response()->json(['message' => 'Translation deleted.'], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function translateImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // 10MB max
        ]);

        try {
            // Send to ML service
            $client = new \GuzzleHttp\Client();
            $response = $client->post('http://localhost:5000/api/translate', [
                'multipart' => [
                    [
                        'name'     => 'image',
                        'contents' => fopen($request->file('image')->getPathname(), 'r'),
                        'filename' => $request->file('image')->getClientOriginalName()
                    ]
                ],
                'timeout' => 30,
                'connect_timeout' => 10
            ]);

            $result = json_decode($response->getBody(), true);

            // Save the translation to database
            $path = $request->file('image')->store('translations', 'public');

            $translation = $request->user()->translations()->create([
                'image_url' => PublicMediaUrl::absolute($path),
                'translated_text' => $result['translation'] ?? $result['translated_text'] ?? 'No translation available',
                'original_text' => $result['original_text'] ?? 'No original text detected',
                'confidence_score' => ($result['confidence'] ?? $result['confidence_score'] ?? 0.0) / 100, // Convert to decimal
            ]);

            return response()->json([
                'message' => 'Translation completed successfully',
                'translation' => $translation,
                'ml_result' => $result
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'ML service unavailable',
                'error' => 'ML service error: ' . $e->getMessage()
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    private function deleteStoredImageIfPossible(?string $imageUrl): void
    {
        if (!$imageUrl) {
            return;
        }

        // Expected: http(s)://<host>/storage/<relative-path>
        $path = parse_url($imageUrl, PHP_URL_PATH);
        if (!$path) {
            return;
        }

        $marker = '/storage/';
        $pos = strpos($path, $marker);
        if ($pos === false) {
            return;
        }

        $relative = ltrim(substr($path, $pos + strlen($marker)), '/');
        if ($relative === '') {
            return;
        }

        Storage::disk('public')->delete($relative);
    }
}

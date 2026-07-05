<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Album;
use Illuminate\Http\Request;

class AlbumController extends Controller
{
    public function index(Request $request)
    {
        return response()->json($request->user()->albums);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $album = $request->user()->albums()->create($request->all());

        return response()->json($album, 201);
    }

    public function show($id)
    {
        return response()->json(Album::with('user')->findOrFail($id));
    }
}

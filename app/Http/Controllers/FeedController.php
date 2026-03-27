<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $posts = Post::with('user')
            ->latest()
            ->paginate($request->integer('per_page', 10));

        $feed = $posts->through(function (Post $post): array {
            return [
                'id' => $post->id,
                'content' => $post->content,
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
                'author' => [
                    'id' => $post->user->id,
                    'name' => $post->user->name,
                    'username' => $post->user->username,
                    'profile_photo' => $post->user->profile_photo,
                    'cover_photo' => $post->user->cover_photo,
                ],
            ];
        });

        return response()->json([
            'message' => 'Feed retrieved successfully.',
            'data' => $feed->items(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $post = $request->user()->posts()->create($validated);
        $post->load('user');

        return response()->json([
            'message' => 'Post created successfully.',
            'post' => [
                'id' => $post->id,
                'content' => $post->content,
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
                'author' => [
                    'id' => $post->user->id,
                    'name' => $post->user->name,
                    'username' => $post->user->username,
                    'profile_photo' => $post->user->profile_photo,
                    'cover_photo' => $post->user->cover_photo,
                ],
            ],
        ], 201);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    // READ - List posts (search + pagination), only the logged-in user's posts
    public function index(Request $request)
    {
        $query = Post::where('user_id', $request->user()->id);

        // Search functionality (?search=keyword)
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Pagination (?per_page=10&page=1)
        $perPage = $request->get('per_page', 10);
        $posts = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $posts
        ], 200);
    }

    // CREATE - New post, automatically owned by the logged-in user
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $post = Post::create([
            'user_id' => $request->user()->id,
            'title'   => $request->title,
            'content' => $request->content,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully',
            'data' => $post
        ], 201);
    }

    // READ - Single post (must belong to the logged-in user)
    public function show(Request $request, $id)
    {
        $post = Post::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found or does not belong to you'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $post
        ], 200);
    }

    // UPDATE - Only the owner can update
    public function update(Request $request, $id)
    {
        $post = Post::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found or does not belong to you'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title'   => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $post->update($request->only(['title', 'content']));

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully',
            'data' => $post
        ], 200);
    }

    // DELETE - Soft delete, only the owner can delete
    public function destroy(Request $request, $id)
    {
        $post = Post::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found or does not belong to you'
            ], 404);
        }

        $post->delete(); // soft delete - sets deleted_at, keeps the row

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully'
        ], 200);
    }
}
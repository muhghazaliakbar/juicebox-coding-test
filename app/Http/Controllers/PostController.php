<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Request;

class PostController extends Controller
{
    /**
     * Display a listing of the posts.
     */
    public function index(): AnonymousResourceCollection
    {
        // Retrieve all posts with related user (author) and category
        $posts = Post::with(['author', 'category', 'comments'])->paginate(10);

        // Return paginated posts as a resource collection
        return PostResource::collection($posts);
    }

    /**
     * Store a newly created post in storage.
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        // Create the post with validated data
        $post = Post::query()->create([
            'user_id' => $request->user()->id, // Set the authenticated user as the author
            'category_id' => $request->category_id,
            'title' => $request->title,
            'body' => $request->body,
        ]);

        // Load relationships
        $post->load(['author', 'category']);

        // Return the created post as a resource with a 201 status code
        return (new PostResource($post))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified post.
     */
    public function show(Post $post): PostResource
    {
        // Load relationships
        $post->load(['author', 'category', 'comments.author']);

        // Return the post as a resource
        return new PostResource($post);
    }

    /**
     * Update the specified post in storage.
     */
    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        // Authorization: Ensures the user can update the post (handled via Policy)
        Gate::authorize('update', $post);

        // Update the post with validated data
        $post->update($request->only(['category_id', 'title', 'body']));

        // Load relationships
        $post->load(['author', 'category']);

        // Return the updated post as a resource
        return new PostResource($post);
    }

    /**
     * Remove the specified post from storage.
     */
    public function destroy(Request $request, Post $post): JsonResponse
    {
        // Authorization: Ensures the user can delete the post (handled via Policy)
        Gate::authorize('delete', $post);

        // Delete the post
        $post->delete();

        // Return a 204 No Content response
        return response()->json(null, 204);
    }
}

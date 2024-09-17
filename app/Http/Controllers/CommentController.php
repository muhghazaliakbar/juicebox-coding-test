<?php
// app/Http/Controllers/CommentController.php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    /**
     * Display a listing of the comments for a specific post.
     */
    public function index(Post $post): AnonymousResourceCollection
    {
        $comments = $post->comments()->with('author')->paginate(10);
        return CommentResource::collection($comments);
    }

    /**
     * Store a newly created comment for a specific post.
     */
    public function store(StoreCommentRequest $request, Post $post): CommentResource
    {
        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->body,
        ]);

        return new CommentResource($comment->load('author'));
    }

    /**
     * Display the specified comment.
     */
    public function show(Post $post, Comment $comment): CommentResource
    {
        return new CommentResource($comment->load('author'));
    }

    /**
     * Update the specified comment.
     */
    public function update(UpdateCommentRequest $request, Post $post, Comment $comment): CommentResource
    {
        Gate::authorize('update', $comment);

        $comment->update($request->validated());

        return new CommentResource($comment->load('author'));
    }

    /**
     * Remove the specified comment.
     */
    public function destroy(Post $post, Comment $comment): Response
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return response()->noContent();
    }
}

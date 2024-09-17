<?php

namespace Tests\Feature;

use App\Http\Resources\CommentResource;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that an authenticated user can create a comment on a post.
     */
    public function test_authenticated_user_can_create_comment()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $commentData = [
            'body' => 'This is a test comment.',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/posts/{$post->id}/comments", $commentData);

        $comment = Comment::where('body', 'This is a test comment.')->first();

        $response->assertStatus(201)
            ->assertJson(CommentResource::make($comment)->response()->getData(true));
    }

    /**
     * Test that an authenticated user can view comments for a post.
     */
    public function test_authenticated_user_can_view_comments_for_a_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comments = Comment::factory()->count(3)->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/posts/{$post->id}/comments");

        $expectedData = CommentResource::collection($comments->load('author'))->response()->getData(true)['data'];

        $response->assertStatus(200)
            ->assertJson([
                'data' => $expectedData,
            ]);
    }

    /**
     * Test that an authenticated user can view a specific comment.
     */
    public function test_authenticated_user_can_view_a_specific_comment()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/posts/{$post->id}/comments/{$comment->id}");

        $expectedData = CommentResource::make($comment->load('author'))->response()->getData(true)['data'];

        $response->assertStatus(200)
            ->assertJson([
                'data' => $expectedData,
            ]);
    }

    /**
     * Test that an authenticated user can update their own comment.
     */
    public function test_authenticated_user_can_update_their_comment()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'body' => 'Original Comment',
        ]);

        $updateData = [
            'body' => 'Updated Comment',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/posts/{$post->id}/comments/{$comment->id}", $updateData);

        $comment->refresh();

        $expectedData = CommentResource::make($comment->load('author'))->response()->getData(true)['data'];

        $response->assertStatus(200)
            ->assertJson([
                'data' => $expectedData,
            ]);
    }

    /**
     * Test that an authenticated user cannot update someone else's comment.
     */
    public function test_authenticated_user_cannot_update_others_comment()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $otherUser->id,
            'body' => 'Other User Comment',
        ]);

        $updateData = [
            'body' => 'Attempted Update',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/posts/{$post->id}/comments/{$comment->id}", $updateData);

        $response->assertStatus(403);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'body' => 'Other User Comment',
        ]);
    }

    /**
     * Test that an authenticated user can delete their own comment.
     */
    public function test_authenticated_user_can_delete_their_comment()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/posts/{$post->id}/comments/{$comment->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }

    /**
     * Test that an authenticated user cannot delete someone else's comment.
     */
    public function test_authenticated_user_cannot_delete_others_comment()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/posts/{$post->id}/comments/{$comment->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
        ]);
    }

    /**
     * Test that an unauthenticated user cannot create a comment.
     */
    public function test_unauthenticated_user_cannot_create_comment()
    {
        $post = Post::factory()->create();

        $commentData = [
            'body' => 'This is a test comment.',
        ];

        $response = $this->postJson("/api/posts/{$post->id}/comments", $commentData);

        $response->assertStatus(401);

        $this->assertDatabaseMissing('comments', [
            'body' => 'This is a test comment.',
        ]);
    }

    /**
     * Test validation errors when creating a comment.
     */
    public function test_validation_errors_on_create_comment()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $commentData = [
            'body' => '', // Empty body should fail validation
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/posts/{$post->id}/comments", $commentData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    }
}

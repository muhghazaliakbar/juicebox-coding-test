<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that an authenticated user can create a post.
     */
    public function test_authenticated_user_can_create_post()
    {
        // Fake mail to prevent actual emails from being sent
        Mail::fake();

        // Create a user
        $user = User::factory()->create();

        // Create a category
        $category = Category::factory()->create();

        // Define post data
        $postData = [
            'category_id' => $category->id,
            'title' => 'Sample Post Title',
            'body' => 'This is the body of the sample post.',
        ];

        // Authenticate and make the POST request
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/posts', $postData);

        // Assert response status and structure
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'author' => [
                        'id',
                        'name',
                        'email',
                    ],
                    'category' => [
                        'id',
                        'name',
                    ],
                    'title',
                    'body',
                    'created_at',
                    'updated_at',
                ],
            ]);

        // Assert the post exists in the database
        $this->assertDatabaseHas('posts', [
            'title' => 'Sample Post Title',
            'body' => 'This is the body of the sample post.',
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
    }

    /**
     * Test that unauthenticated users cannot create posts.
     */
    public function test_unauthenticated_user_cannot_create_post()
    {
        // Define post data
        $postData = [
            'category_id' => 1,
            'title' => 'Sample Post Title',
            'body' => 'This is the body of the sample post.',
        ];

        // Make the POST request without authentication
        $response = $this->postJson('/api/posts', $postData);

        // Assert unauthorized response
        $response->assertStatus(401);
    }

    /**
     * Test that an authenticated user can view all posts.
     */
    public function test_authenticated_user_can_view_all_posts()
    {
        // Create a user
        $user = User::factory()->create();

        // Create categories
        $categories = Category::factory()->count(3)->create();

        // Create posts
        Post::factory()->count(5)->create([
            'user_id' => $user->id,
            'category_id' => $categories->random()->id,
        ]);

        // Authenticate and make the GET request
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/posts');

        // Assert response status and structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'author' => [
                            'id',
                            'name',
                            'email',
                        ],
                        'category' => [
                            'id',
                            'name',
                        ],
                        'title',
                        'body',
                        'comments',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    /**
     * Test that an authenticated user can view a specific post.
     */
    public function test_authenticated_user_can_view_specific_post()
    {
        // Create a user
        $user = User::factory()->create();

        // Create a category
        $category = Category::factory()->create();

        // Create a post
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Specific Post Title',
            'body' => 'This is the body of the specific post.',
        ]);

        // Add comments to the post
        Comment::factory()->count(3)->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'body' => 'Sample comment.',
        ]);

        // Authenticate and make the GET request
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/posts/{$post->id}");

        // Assert response status and structure
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $post->id,
                    'title' => 'Specific Post Title',
                    'body' => 'This is the body of the specific post.',
                    'author' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                    ],
                    // 'comments' => [
                    //     // Array of comments
                    // ],
                ],
            ]);
    }

    /**
     * Test that an authenticated user can update their own post.
     */
    public function test_authenticated_user_can_update_their_own_post()
    {
        // Fake mail if needed
        Mail::fake();

        // Create a user
        $user = User::factory()->create();

        // Create a category
        $category = Category::factory()->create();

        // Create a post
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Original Title',
            'body' => 'Original body.',
        ]);

        // Define updated data
        $updatedData = [
            'title' => 'Updated Title',
            'body' => 'Updated body.',
            'category_id' => $category->id,
        ];

        // Authenticate and make the PUT request
        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/posts/{$post->id}", $updatedData);

        // Assert response status and structure
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $post->id,
                    'title' => 'Updated Title',
                    'body' => 'Updated body.',
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                    ],
                ],
            ]);

        // Assert the database has updated data
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
            'body' => 'Updated body.',
        ]);
    }

    /**
     * Test that an authenticated user cannot update others' posts.
     */
    public function test_authenticated_user_cannot_update_others_post()
    {
        // Create two users
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Create a category
        $category = Category::factory()->create();

        // Create a post by the other user
        $post = Post::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
            'title' => 'Other User Post',
            'body' => 'Body by other user.',
        ]);

        // Define updated data
        $updatedData = [
            'title' => 'Malicious Update',
            'body' => 'Attempted unauthorized update.',
        ];

        // Authenticate as the first user and attempt to update the other user's post
        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/posts/{$post->id}", $updatedData);

        // Assert forbidden response
        $response->assertStatus(403);

        // Assert the database does not have the updated data
        $this->assertDatabaseMissing('posts', [
            'id' => $post->id,
            'title' => 'Malicious Update',
            'body' => 'Attempted unauthorized update.',
        ]);
    }

    /**
     * Test that an authenticated user can delete their own post.
     */
    public function test_authenticated_user_can_delete_their_own_post()
    {
        // Create a user
        $user = User::factory()->create();

        // Create a category
        $category = Category::factory()->create();

        // Create a post
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        // Authenticate and make the DELETE request
        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/posts/{$post->id}");

        // Assert no content response
        $response->assertStatus(204);

        // Assert the post is deleted from the database
        $this->assertDatabaseMissing('posts', [
            'id' => $post->id,
        ]);
    }

    /**
     * Test that an authenticated user cannot delete others' posts.
     */
    public function test_authenticated_user_cannot_delete_others_post()
    {
        // Create two users
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Create a category
        $category = Category::factory()->create();

        // Create a post by the other user
        $post = Post::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
        ]);

        // Authenticate as the first user and attempt to delete the other user's post
        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/posts/{$post->id}");

        // Assert forbidden response
        $response->assertStatus(403);

        // Assert the post still exists in the database
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
        ]);
    }

    /**
     * Test that an authenticated user can view their own posts.
     */
    public function test_authenticated_user_can_view_their_own_posts()
    {
        // Create a user
        $user = User::factory()->create();

        // Create categories
        $categories = Category::factory()->count(2)->create();

        // Create posts by the user
        $posts = Post::factory()->count(3)->create([
            'user_id' => $user->id,
            'category_id' => $categories->first()->id,
        ]);

        // Authenticate and make the GET request
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/posts');

        // Assert response status and structure
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'author',
                        'category',
                        'title',
                        'body',
                        'comments',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    /**
     * Test that an authenticated user can view a post along with its comments.
     */
    public function test_authenticated_user_can_view_post_with_comments()
    {
        // Create a user
        $user = User::factory()->create();

        // Create a category
        $category = Category::factory()->create();

        // Create a post
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post with Comments',
            'body' => 'This post has comments.',
        ]);

        // Create comments
        $comments = Comment::factory()->count(2)->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'body' => 'Sample comment.',
        ]);

        // Authenticate and make the GET request
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/posts/{$post->id}");

        // Assert response status and structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'author',
                    'category',
                    'title',
                    'body',
                    'comments' => [
                        '*' => [
                            'id',
                            'author',
                            'body',
                            'created_at',
                        ],
                    ],
                    'created_at',
                    'updated_at',
                ],
            ]);
    }
}

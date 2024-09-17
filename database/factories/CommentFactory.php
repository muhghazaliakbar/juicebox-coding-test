<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(), // Creates a new Post and associates the comment
            'user_id' => User::factory(), // Creates a new User and associates the comment
            'body' => $this->faker->sentence(), // Generates a random sentence for the comment body
        ];
    }
}

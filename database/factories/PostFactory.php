<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // Creates a new User and associates the post
            'category_id' => Category::factory(), // Creates a new Category and associates the post
            'title' => $this->faker->sentence(), // Generates a random sentence for the title
            'body' => $this->faker->paragraphs(3, true), // Generates a random paragraph for the body
        ];
    }
}

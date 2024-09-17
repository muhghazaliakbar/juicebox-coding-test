<?php

namespace Tests\Feature;

use App\Jobs\SendWelcomeEmailJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Get token for current user helper.
     */
    public function getTokenForUser(User $user): string
    {
        return $user->createToken('auth_token')->plainTextToken;
    }

    /**
     * Test user registration with valid data.
     *
     * @return void
     */
    public function test_user_can_register_with_valid_data()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'access_token',
                'token_type',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'testuser@example.com',
        ]);
    }

    /**
     * Test user registration with invalid data.
     *
     * @return void
     */
    public function test_user_registration_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/register', [
            'name' => '', // Missing name
            'email' => 'invalid-email', // Invalid email format
            'password' => 'pass', // Password too shorts
            'password_confirmation' => 'different', // Password confirmation does not match
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /**
     * Test user can log in with the correct credentials.
     *
     * @return void
     */
    public function test_user_can_login_with_correct_credentials()
    {
        // Create a user
        User::factory()->create([
            'email' => 'loginuser@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'loginuser@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
            ]);
    }

    /**
     * Test user login fails with incorrect credentials.
     *
     * @return void
     */
    public function test_user_login_fails_with_incorrect_credentials()
    {
        // Create a user
        User::factory()->create([
            'email' => 'wronglogin@example.com',
            'password' => bcrypt('correctpassword'),
        ]);

        // Attempt to log in with the wrong password
        $response = $this->postJson('/api/login', [
            'email' => 'wronglogin@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test user login with rate limited.
     *
     * @return void
     */
    public function test_login_rate_limiting()
    {
        // Create a user
        User::factory()->create([
            'email' => 'ratelimit@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Attempt to log in multiple times with the wrong password
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/login', [
                'email' => 'ratelimit@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        // The next attempt should be rate limited
        $response = $this->postJson('/api/login', [
            'email' => 'ratelimit@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429); // 429 Too Many Requests
    }

    /**
     * Test expired token.
     *
     * @return void
     */
    public function test_expired_token_cannot_access_protected_routes()
    {
        // Create a user
        $user = User::factory()->create();

        // Create a token with an expired date
        $token = $this->getTokenForUser($user);

        // Manually expire the token
        $user->tokens()->update(['expires_at' => now()->subDay()]);

        // Attempt to access a protected route
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/user');

        $response->assertStatus(401); // Unauthorized
    }

    /**
     * Test authenticated user can fetch user info.
     *
     * @return void
     */
    public function test_authenticated_user_can_fetch_user_info()
    {
        // Create a user
        $user = User::factory()->create();

        // Authenticate the user and get the token
        $token = $this->getTokenForUser($user);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
    }

    /**
     * Test authenticated user can fetch user info.
     *
     * @return void
     */
    public function test_authenticated_user_can_fetch_user_info_using_actingAs_method()
    {
        // Create a user
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
    }

    /**
     * Test authenticated admin can fetch another user's information.
     *
     * @return void
     */
    public function test_can_fetch_other_users_info()
    {
        // Create an admin user
        $user = User::factory()->create();

        // Create another user
        $otherUser = User::factory()->create();

        // Authenticate the admin and get the token
        $token = $this->getTokenForUser($user);

        // Make the request to fetch the other user's info
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson("/api/users/{$otherUser->id}");

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'email' => $otherUser->email,
                ],
            ]);
    }

    /**
     * Test unauthenticated user cannot fetch user info.
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_fetch_user_info()
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    /**
     * Test authenticated user can logout successfully.
     *
     * @return void
     */
    public function test_authenticated_user_can_logout()
    {
        // Create a user
        $user = User::factory()->create();

        // Authenticate the user and get the token
        $token = $this->getTokenForUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Successfully logged out',
            ]);

        // Refresh user to get updated tokens
        $user->refresh();

        // Ensure the token is revoked
        $this->assertCount(0, $user->tokens);
    }

    /**
     * Test that SendWelcomeEmailJob is dispatched upon registration.
     *
     * @return void
     */
    public function test_send_welcome_email_job_is_dispatched_upon_registration()
    {
        // Fake the queue to intercept dispatched jobs
        Queue::fake();

        // Make the registration request
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // Assert successful registration
        $response->assertStatus(201)
            ->assertJsonStructure([
                'access_token',
                'token_type',
            ]);

        // Assert that the SendWelcomeEmailJob was dispatched with the correct user
        Queue::assertPushed(SendWelcomeEmailJob::class, function ($job) {
            return $job->user->email === 'testuser@example.com';
        });
    }
}

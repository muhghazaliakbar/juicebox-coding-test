<?php

namespace Tests\Feature;

use App\Jobs\SendWelcomeEmailJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use App\Models\User;

class SendWelcomeEmailCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the command dispatches the SendWelcomeEmailJob when using --id.
     *
     * @return void
     */
    public function test_command_dispatches_job_with_user_id()
    {
        // Fake the queue to intercept dispatched jobs
        Queue::fake();

        // Create a user
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
        ]);

        // Run the Artisan command with --id option
        $this->artisan('send:welcome-email', ['--id' => $user->id])
            ->expectsOutput("SendWelcomeEmailJob dispatched for user ID {$user->id} ({$user->email}).")
            ->assertExitCode(0);

        // Assert that the job was dispatched with the correct user
        Queue::assertPushed(SendWelcomeEmailJob::class, function ($job) use ($user) {
            return $job->user->id === $user->id;
        });
    }

    /**
     * Test that the command dispatches the SendWelcomeEmailJob when using --email.
     *
     * @return void
     */
    public function test_command_dispatches_job_with_user_email()
    {
        // Fake the queue to intercept dispatched jobs
        Queue::fake();

        // Create a user
        $user = User::factory()->create([
            'email' => 'jane.doe@example.com',
        ]);

        // Run the Artisan command with --email option
        $this->artisan('send:welcome-email', ['--email' => $user->email])
            ->expectsOutput("SendWelcomeEmailJob dispatched for user ID {$user->id} ({$user->email}).")
            ->assertExitCode(0);

        // Assert that the job was dispatched with the correct user
        Queue::assertPushed(SendWelcomeEmailJob::class, function ($job) use ($user) {
            return $job->user->id === $user->id;
        });
    }

    /**
     * Test that the command fails when no options are provided.
     *
     * @return void
     */
    public function test_command_fails_without_options()
    {
        Queue::fake();
        // Run the Artisan command without any options
        $this->artisan('send:welcome-email')
            ->expectsOutput('Please provide either --id or --email option.')
            ->assertExitCode(1);

        // Assert that no job was dispatched
        Queue::assertNothingPushed();
    }

    /**
     * Test that the command fails when ID does not find a user.
     *
     * @return void
     */
    public function test_command_fails_when_user_not_found_by_id()
    {
        // Fake the queue to intercept dispatched jobs
        Queue::fake();

        // Define a non-existing user ID
        $nonExistingId = 9999;

        // Run the Artisan command with --id option
        $this->artisan('send:welcome-email', ['--id' => $nonExistingId])
            ->expectsOutput("No user found with ID {$nonExistingId}.")
            ->assertExitCode(1);

        // Assert that no job was dispatched
        Queue::assertNothingPushed();
    }

    /**
     * Test that the command fails when user is not found by email.
     *
     * @return void
     */
    public function test_command_fails_when_user_not_found_by_email()
    {
        // Fake the queue to intercept dispatched jobs
        Queue::fake();

        // Define a non-existing user email
        $nonExistingEmail = 'nonexistent@example.com';

        // Run the Artisan command with --email option
        $this->artisan('send:welcome-email', ['--email' => $nonExistingEmail])
            ->expectsOutput("No user found with email {$nonExistingEmail}.")
            ->assertExitCode(1);

        // Assert that no job was dispatched
        Queue::assertNothingPushed();
    }
}

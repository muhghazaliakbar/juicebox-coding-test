<?php

namespace App\Console\Commands;

use App\Jobs\SendWelcomeEmailJob;
use App\Models\User;
use Illuminate\Console\Command;

class SendWelcomeEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:welcome-email
                        {--id= : The ID of the user}
                        {--email= : The email of the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch the SendWelcomeEmailJob for a specified user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Retrieve options
        $userId = $this->option('id');
        $email = $this->option('email');

        // Validate input: either id or email must be provided
        if (! $userId && ! $email) {
            $this->error('Please provide either --id or --email option.');

            return 1; // Non-zero exit code indicates failure
        }

        // Retrieve the user based on a provided option
        if ($userId) {
            $user = User::query()->find($userId);
            if (! $user) {
                $this->error("No user found with ID {$userId}.");

                return 1;
            }
        } else {
            $user = User::query()->where('email', $email)->first();
            if (! $user) {
                $this->error("No user found with email {$email}.");

                return 1;
            }
        }

        // Dispatch the SendWelcomeEmailJob
        SendWelcomeEmailJob::dispatch($user);

        $this->info("SendWelcomeEmailJob dispatched for user ID {$user->id} ({$user->email}).");

        return 0; // Zero exit code indicates success
    }
}

## How to install

This repo is using the latest Laravel version, so it will also need to follow [Laravel system requirements](https://laravel.com/docs/11.x/deployment#server-requirements)

### Steps:
- Clone the repository
- Install the composer packages using `composer install`
- Setup `.env` variables
  1. `cp .env.example .env`
  2. `php artisan key:generate`
  3. Setup database connection
- Refresh the database: `php artisan migrate:fresh`
- Run the queue worker `php artisan queue:work`

### Manual dispatch welcome email job

- Run the `seeder` first or create a new user using artisan `tinker` to create a new user before dispatching the job
- Execute the welcome email job using: `php artisan send:welcome-email --id={user_id}` to send the email by User ID or `php artisan send:welcome-email --email={email}` to send the email by registered user email. Example: `php artisan send:welcome-email --email=test@example.com`
- If the job is executed, the terminal response should be like this: `SendWelcomeEmailJob dispatched for user ID 1 (test@example.com).`

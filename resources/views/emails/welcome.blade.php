<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Welcome to {{ config('app.name') }}</title>
</head>
<body>
<h1>Hello, {{ $user->name }}!</h1>
<p>Welcome to {{ config('app.name') }}. We're excited to have you on board.</p>
<p>If you have any questions, feel free to reach out to our support team.</p>
<p>Best Regards,<br>{{ config('app.name') }} Team</p>
</body>
</html>

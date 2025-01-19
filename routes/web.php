<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/reset-password/{token}', function ($token) {
    // Return a simple HTML page for resetting the password
    return response('
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Reset Password</title>
        </head>
        <body>
            <h1>Reset Your Password</h1>
            <form method="POST" action="/api/reset-password">
                <input type="hidden" name="token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
                <label for="password">New Password:</label>
                <input type="password" id="password" name="password" required>
                <label for="password_confirmation">Confirm Password:</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
                <button type="submit">Reset Password</button>
            </form>
        </body>
        </html>
    ', 200, ['Content-Type' => 'text/html']);
})->middleware('guest')->name('password.reset');

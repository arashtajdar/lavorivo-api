<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
<div class="bg-white shadow-lg rounded-lg p-8 w-full max-w-md">
    <h1 class="text-2xl font-semibold text-center mb-4">Reset Your Password</h1>
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
            </div>
        </body>
        </html>
    ', 200, ['Content-Type' => 'text/html']);
})->middleware('guest')->name('password.reset');

Route::get('/login', function () {
    return '<form method="POST" action="/login">
                ' . csrf_field() . '
                <input type="email" name="email" placeholder="Email">
                <input type="password" name="password" placeholder="Password">
                <button type="submit">Login</button>
            </form>';
});

Route::post('/login', function (Request $request) {
    $user = User::where('email', $request->email)->first();

    if ($user && Hash::check($request->password, $user->password)) {
        Auth::login($user);
        return redirect('/log-viewer');
    }

    return 'Invalid credentials';
});


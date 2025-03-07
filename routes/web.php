<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use Illuminate\Support\Facades\View;

Route::get('/reset-password/{token}', function ($token) {
    return View::make('auth.reset-password', ['token' => $token]);
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


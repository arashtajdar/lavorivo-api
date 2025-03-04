<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        LogViewer::auth(function ($request) {
            $authorizedEmails = [];
            array_push($authorizedEmails, 'arash.tajdar@gmail.com');
            return $request->user('sanctum') && in_array($request->user('sanctum')->email, $authorizedEmails);
        });
    }
}

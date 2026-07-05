<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;

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
        // Ensure all JSON responses are UTF-8 encoded
        Response::macro('jsonUtf8', function ($data, $status = 200, array $headers = [], $options = 0) {
            return response()->json($data, $status, $headers, $options | JSON_UNESCAPED_UNICODE);
        });
    }
}
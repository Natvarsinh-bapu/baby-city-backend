<?php

namespace App\Providers;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

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
        Response::macro('customJson', function ($success = true, $data = [], $other_data = [], $message = null, $last_page = null, $status_code = 200) {
            $message = (is_array($message)) ? implode(', ', $message) : $message;

            $response_data = array_merge([
                'success' => $success,
                'data' => $data,
                'message' => $message,
                'last_page' => $last_page,
            ], $other_data);

            return response()->json($response_data, $status_code);
        });

        RateLimiter::for('api-custom', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->customJson(false, [], [], 'Too many requests. Please try again later.', '', 429);
                });
        });
    }
}

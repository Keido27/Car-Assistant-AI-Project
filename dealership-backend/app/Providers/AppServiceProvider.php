<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\Gemini\GeminiClient::class, function () {
            return new \App\Services\Gemini\GeminiClient(
                apiKey: config('services.gemini.api_key'),
                model: config('services.gemini.model'),
                baseUrl: config('services.gemini.base_url'),
            );
        });
        $this->app->singleton(\App\Services\Gemini\ToolCallingConversation::class, function ($app) {
            return (new \App\Services\Gemini\ToolCallingConversation(
                $app->make(\App\Services\Gemini\GeminiClient::class)
            ))->registerTool($app->make(\App\Services\Gemini\Tools\SearchInventoryTool::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}

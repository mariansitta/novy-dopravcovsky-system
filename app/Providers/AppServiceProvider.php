<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Mail\MailManager;
use App\Services\GmailApiTransport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->make(MailManager::class)->extend('gmailapi', function (array $config) {
            return new GmailApiTransport(
                app(\App\Services\GmailService::class),
                $config['impersonate'] ?? null
            );
        });
    }
}

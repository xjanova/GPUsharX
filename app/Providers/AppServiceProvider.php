<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Force file-based session and cache if app is not installed
        // This must happen before session is started
        if (!File::exists(storage_path('installed'))) {
            $this->app['config']->set('session.driver', 'file');
            $this->app['config']->set('cache.default', 'file');
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

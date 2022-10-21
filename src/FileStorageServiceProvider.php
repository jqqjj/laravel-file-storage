<?php

namespace Jqqjj\LaravelFileStorage;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class FileStorageServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }

    public function register()
    {
        $this->app->singleton('laravel.file_storage', FileStorage::class);
    }

    public function provides()
    {
        return ['laravel.file_storage'];
    }
}

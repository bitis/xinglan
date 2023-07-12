<?php

namespace App\Providers;

use AlphaSnow\Flysystem\Aliyun\AliyunFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('admin') ? true : null;
        });

        if ($this->app->environment('local', 'test')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Storage::extend('oss', function (Application $app, array $config) {

            $driver = (new AliyunFactory())->createFilesystem($config);
            $adapter = (new AliyunFactory())->createAdapter($config);

            return new FilesystemAdapter(
                $driver,
                $adapter,
                $config
            );
        });

    }
}

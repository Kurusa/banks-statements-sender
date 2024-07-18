<?php

namespace App\Providers;

use App\Services\MonoApiService;
use App\Services\PrivatApiService;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MonoApiService::class, function () {
            return new MonoApiService(new Client());
        });

        $this->app->singleton(PrivatApiService::class, function () {
            return new PrivatApiService(new Client());
        });
    }

    public function boot(): void
    {
    }
}

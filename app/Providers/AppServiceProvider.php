<?php

namespace App\Providers;

use App\Services\MonoApiService;
use App\Services\PrivatApiService;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Client as TelegramBotClient;

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

        $this->app->singleton(TelegramBotClient::class, function () {
            return new BotApi(config('telegram.token'));
        });

        $this->app->singleton(BotApi::class, function () {
            return new BotApi(config('telegram.token'));
        });
    }

    public function boot(): void
    {
    }
}

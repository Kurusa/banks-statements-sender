<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Commands\MainMenu;
use TelegramBot\Api\Client;
use TelegramBot\Api\Types\Update;

class WebhookController
{
    public function handle(): void
    {
        $client = new Client(env('TELEGRAM_BOT_TOKEN'));

        $client->on(function (Update $update) {
            (new MainMenu($update))->handle();
        }, function (Update $update) {
            return $update->getMessage() !== null;
        });

        $client->run();
    }
}

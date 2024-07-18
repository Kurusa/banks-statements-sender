<?php

namespace App\Services;

use TelegramBot\Api\Client;
use TelegramBot\Api\Types\Update;
use App\Http\Controllers\Commands\MainMenu;
use Illuminate\Support\Facades\Log;
use Exception;

class TelegramService
{
    public function __construct(readonly protected Client $client)
    {
    }

    public function processWebhook(): void
    {
        try {
            $this->client->on(function (Update $update) {
                (new MainMenu($update))->handle();
            }, function (Update $update) {
                return $update->getMessage() !== null;
            });

            $this->client->run();
        } catch (Exception $e) {
            Log::error('Error processing telegram webhook: ' . $e->getMessage());
        }
    }
}

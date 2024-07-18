<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;

class WebhookController
{
    public function handle(TelegramService $telegramService): void
    {
        $telegramService->processWebhook();
    }
}
